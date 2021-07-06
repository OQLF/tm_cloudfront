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
    'title' => 'CloudFront cache',
    'description' => 'This extension is designed to clear  the Amazon CloudFront cache based on the speaking path of a page.',
    'category' => 'be',
    'author' => 'Simon Ouellet',
    'author_email' => '',
    'state' => 'alpha',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.6',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-10.4.99',
            'typo3db_legacy' => '*',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
