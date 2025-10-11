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

use Cylancer\CyLending\Controller\AjaxConnectController;
use Cylancer\CyLending\Controller\LendingController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die('Access denied.');


ExtensionUtility::configurePlugin(
    'CyLending',
    'Lending',
    [
        LendingController::class => [
            'show',
            'reserve',
        ],
        AjaxConnectController::class => [
            'getEvents',
            'getMaxQuantity',
            'getReserveForm',
            'reserve',
            'getMyLendings',
            'cancelMyLending',
            'getMyAvailabilityRequests',
            'cancelMyAvailabilityRequest',
            'isAvailabilityRequestsVisible',
            'getAvailabilityRequests',
            'approve',
            'reject',
        ]
    ],
    // non-cacheable actions
    [
        LendingController::class => [
            'show',
            'reserve',
        ],
        AjaxConnectController::class => [
            'getEvents',
            'getMaxQuantity',
            'getReserveForm',
            'reserve',
            'getMyLendings',
            'cancelMyLending',
            'getMyAvailabilityRequests',
            'cancelMyAvailabilityRequest',
            'isAvailabilityRequestsVisible',
            'getAvailabilityRequests',
            'approve',
            'reject',
        ]
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);



// E-Mail-Templates
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']['cyLending-ApproverMessageMail'] = 'EXT:cy_lending/Resources/Private/Templates/ApproverMessageMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths']['cyLending-ApproverMessageMail'] = 'EXT:cy_lending/Resources/Private/Layouts/ApproverMessageMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths']['cyLending-ApproverMessageMail'] = 'EXT:cy_lending/Resources/Private/Partials/ApproverMessageMail/';

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']['cyLending-AvailabilityRequestResultMail'] = 'EXT:cy_lending/Resources/Private/Templates/AvailabilityRequestResultMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths']['cyLending-AvailabilityRequestResultMail'] = 'EXT:cy_lending/Resources/Private/Layouts/AvailabilityRequestResultMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths']['cyLending-AvailabilityRequestResultMail'] = 'EXT:cy_lending/Resources/Private/Partials/AvailabilityRequestResultMail/';

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']['cyLending-InformCancelLendingMail'] = 'EXT:cy_lending/Resources/Private/Templates/InformCancelLendingMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths']['cyLending-InformCancelLendingMail'] = 'EXT:cy_lending/Resources/Private/Layouts/InformCancelLendingMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths']['cyLending-InformCancelLendingMail'] = 'EXT:cy_lending/Resources/Private/Partials/InformCancelLendingMail/';

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']['cyLending-InformCancelAvailabilityRequestMail'] = 'EXT:cy_lending/Resources/Private/Templates/InformCancelAvailabilityRequestMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths']['cyLending-InformCancelAvailabilityRequestMail'] = 'EXT:cy_lending/Resources/Private/Layouts/InformCancelAvailabilityRequestMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths']['cyLending-InformCancelAvailabilityRequestMail'] = 'EXT:cy_lending/Resources/Private/Partials/InformCancelAvailabilityRequestMail/';

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']['cyLending-InformPreviousBorrowerMail'] = 'EXT:cy_lending/Resources/Private/Templates/InformPreviousBorrowerMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths']['cyLending-InformPreviousBorrowerMail'] = 'EXT:cy_lending/Resources/Private/Layouts/InformPreviousBorrowerMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths']['cyLending-InformPreviousBorrowerMail'] = 'EXT:cy_lending/Resources/Private/Partials/InformPreviousBorrowerMail/';

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']['cyLending-ObserverMessageMail'] = 'EXT:cy_lending/Resources/Private/Templates/ObserverMessageMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths']['cyLending-ObserverMessageMail'] = 'EXT:cy_lending/Resources/Private/Layouts/ObserverMessageMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths']['cyLending-ObserverMessageMail'] = 'EXT:cy_lending/Resources/Private/Partials/ObserverMessageMail/';


