<?php

/**
 *
 * This file is part of the "lending" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2025 C. Gogolin <service@cylancer.net>
 *
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Lending of objects',
    'description' => 'You can organize lending of objects',
    'category' => 'plugin',
    'author' => 'Clemens Gogolin',
    'author_email' => 'service@cylancer.net',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '4.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'bootstrap_package' => '15.0.0-15.0.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];


/**
 ** CHANGELOG:
 *
 *  4.1.0 :: UPD : UI form: The max quantities are updated, if the object, from or until field is changed (AJAX) 
 *  4.0.0 :: UPD : Update to TYPO3 13 and Bootstrap 15.
 *  3.3.0 :: ADD : After the lean request, you have an button for a new lean request 
 *  3.2.3 :: ADD : Add hyphens in the calendar detail view.
 *  3.2.2 :: Fix : Lendings and availability request with a start date in the past are no longer displayed under “My lendings or “My availability requests”. 
 *  3.2.1 :: Fix : Replace "appointment symbole" with "appointment symbol".
 *  3.2.0 :: ADD : You can set an individual appointment symbol.
 *  3.1.0 :: ADD : You can cancel your availability request.
 *  3.0.1 :: FIX : Calculation of all lending observer receiver is fixed.
 *  3.0.0 :: UPD : Update to TYPO3 12 and Bootstrap 5.
 *  2.1.1 :: FIX : Fix the separation between the group with high priority rights and the approver group.
 *  2.1.0 :: UPD : Separate the configuration for high priority lendings authorization per object.
 *  2.0.2 :: FIX : Small fix automatic approve function and max quantity setting of the group elements.
 *  2.0.1 :: FIX : The automatic approve function works now.
 *  1.4.0 :: UPD : Add sub lendings.
 *  1.3.1 :: CHG : Add small border bottom to an event in the calendar. 
 *  1.3.0 :: UPD : Add ignore time overlapping function
 *  1.2.9 :: CHG : The fix reduces unused detail times.
 *  1.2.8 :: CHG : Detail show displays the times better.
 *  1.2.7 :: UPD : High priority is persistent now.
 *  1.2.6 :: FIX : Fix the time rendering in the calendar.
 *  1.2.5 :: UPD : Approvers can create lendings with high priority. (Overrides exists lendings.)
 *  1.2.4 :: FIX : Prevent two double requests (UI).
 *  1.2.3 :: FIX : Better handling with deleted requests.
 *  1.2.2 :: FIX : Prevent two different permits.
 *  1.2.1 :: ADD : Make the configuration parser stable. 
 *  1.2.0 :: ADD : Add an reasons for prevention request API. 
 *  1.1.7 :: UPD : Release the flexform select tree level. 
 *  1.1.6 :: CHG : Change the storage uids strategy.
 *  1.1.5 :: FIX : The approver tab displays data. 
 *  1.1.4 :: FIX : Not approved lendings are displayed stripped.
 *  1.1.3 :: ADD : Add licence entry in the composer file.
 *  1.1.2 :: FIX : Remove debug outputs.
 *  1.1.1 :: FIX : Repaire the date format.
 *  1.1.0 :: ADD : Add ajax connect for dynamic reloading of the events (lendings).
 *  1.0.0 :: FIX : Fix the plugin registration/configuration.
 *  0.9.8 :: FIX : Remove debug output
 *  0.9.7 :: ADD : Make TypoScript available (always load)
 *  0.9.6 :: FIX : Automatic approve
 *  0.9.5 :: FIX : Object descriptions (translations)
 *  0.9.4 :: ADD : Adds a limit of the next and the previous month button.
 *  0.9.3 :: Initial beta version
 */