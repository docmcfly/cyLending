<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Lending of objects',
    'description' => 'You can organize lending of objects',
    'category' => 'plugin',
    'author' => 'Clemens Gogolin',
    'author_email' => 'service@cylancer.net',
    'state' => 'beta',
    'clearCacheOnLoad' => 1,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.99.99',
            'bootstrap_package' => '13.0.1-13.0.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];


/**
 *  0.9.3 :: Initial beta version
 *  0.9.4 :: ADD : Adds a limit of the next and the previous month button.
 *  0.9.5 :: FIX : Object descriptions (translations)
 *  0.9.6 :: FIX : Automatic approve
 *  0.9.7 :: ADD : Make TypoScript available (always load)
 *  0.9.8 :: FIX : Remove debug output
 *  1.0.0 :: FIX : Fix the plugin registration/configuration.
 * 
 */