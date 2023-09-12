<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Lending of objects',
    'description' => 'You can organize lending of objects',
    'category' => 'plugin',
    'author' => 'Clemens Gogolin',
    'author_email' => 'service@cylancer.net',
    'state' => 'beta',
    'clearCacheOnLoad' => 1,
    'version' => '1.1.3',
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
 *  1.1.0 :: ADD : Add ajax connect for dynamic reloading of the events (lendings).
 *  1.1.1 :: FIX : Repaire the date format.
 *  1.1.2 :: FIX : Remove debug outputs.
 *  1.1.3 :: ADD : Add licence entry in the composer file.
 * 
 */