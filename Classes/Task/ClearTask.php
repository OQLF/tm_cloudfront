<?php

namespace Toumoro\TmCloudfront\Task;


/***
 *
 * This file is part of the "CloudFront cache" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2022 Toumoro
 *
 ***/


use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use Doctrine\DBAL\Driver\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;


class ClearTask extends AbstractTask {

    /**
     * @return bool
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function execute() {
        $this->cloudFrontConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('tm_cloudfront')['cloudfront'];
        $distributionIds = $this->cloudFrontConfiguration['distributionIds'];
        $distributionIds = explode(',',$distributionIds);
        
        foreach ($distributionIds as $key => $distId) {
            //clean duplicate values
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable("tx_tmcloudfront_domain_model_invalidation")
                ->prepare("delete inv from tx_tmcloudfront_domain_model_invalidation inv inner join tx_tmcloudfront_domain_model_invalidation jt on inv.pathsegment = jt.pathsegment and inv.distributionId = jt.distributionId and jt.uid > inv.uid")
                ->executeStatement();

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_tmcloudfront_domain_model_invalidation');
            $count = $queryBuilder
                ->count('uid')
                ->from('tx_tmcloudfront_domain_model_invalidation')
                ->where(
                    $queryBuilder->expr()->eq('distributionId', $queryBuilder->createNamedParameter($distId))
                )
                ->execute()
                ->fetchOne();
    
            if ($count > 0) {
                $options = [
                    'version' => $this->cloudFrontConfiguration['version'],
                    'region' => $this->cloudFrontConfiguration['region'],
                    'credentials' => [
                        'key' => $this->cloudFrontConfiguration['apikey'],
                        'secret' => $this->cloudFrontConfiguration['apisecret'],
                    ]
                ];
                $cloudFront = GeneralUtility::makeInstance('Aws\CloudFront\CloudFrontClient', $options);
    
                $list = $cloudFront->listInvalidations([
                    'DistributionId' => $distId,
                    'MaxItems' => '30',
                ]);
    
                /* the max is 15 but we let 5 spot available for manual clear cache in the backend */
                $availableInvalidations = 10;
                $items = $list->get('InvalidationList')['Items'];
                if (!empty($items)) {
                    foreach ($items as $k => $value) {
                        if ($value["Status"] != 'Completed') {
                            $availableInvalidations--;
                        }
                    }
                }
      
                if ($availableInvalidations > 0) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_tmcloudfront_domain_model_invalidation');
                    $rows = $queryBuilder
                        ->select('*')
                        ->from('tx_tmcloudfront_domain_model_invalidation')
                        ->setMaxResults($availableInvalidations)
                        ->where(
                            $queryBuilder->expr()->eq('distributionId', $queryBuilder->createNamedParameter($distId))
                        )
                        ->execute()
                        ->fetchAllAssociative();

                    foreach ($rows as $k => $value) {
                        
                        $GLOBALS['BE_USER']->writelog(4,0,0,0,$value['pathsegment']. ' ('.$distId.')', "tm_cloudfront");
                        $result = $cloudFront->createInvalidation([
                            'DistributionId' => $distId, // REQUIRED
                            'InvalidationBatch' => [// REQUIRED
                                'CallerReference' => $this->generateRandomString(16), // REQUIRED
                                'Paths' => [// REQUIRED
                                    'Items' => array($value['pathsegment']), // items or paths to invalidate
                                    'Quantity' => 1, // REQUIRED (must be equal to the number of 'Items' in the previus line)
                                ]
                            ]
                        ]);

                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_tmcloudfront_domain_model_invalidation');
                        $affectedRows = $queryBuilder
                            ->delete('tx_tmcloudfront_domain_model_invalidation')
                            ->where(
                                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($value['uid']))
                            )
                            ->execute();
                    }
                }
            }
        }
        return true;
    }

    /**
     * Generate a random string
     * @param int $length length of the string
     * @return string
     */
    protected function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
