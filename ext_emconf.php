<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "tm_cloudfront"
 *
 * Auto generated by Extension Builder 2018-05-31
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'AWS CloudFront cache',
    'description' => 'This extension clears the AWS CloudFront cache based on the speaking path of a page by creating an AWS CloudFront invalidation queue based on clearCacheCmd.',
    'category' => 'be',
    'author' => 'Simon Ouellet',
    'author_email' => 'simon.ouellet@toumoro.com',
    'state' => 'alpha',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-8.7.99',
            'aws' => '*',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
