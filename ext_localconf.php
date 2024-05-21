<?php
use Cylancer\CyLending\Controller\AjaxConnectController;
use Cylancer\CyLending\Controller\LendingController;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die('Access denied.');

call_user_func(function () {

    ExtensionUtility::configurePlugin(
        'CyLending',
        'Lending',
        [
            LendingController::class => 'show, reserve, approve, reject, cancel',
            AjaxConnectController::class => 'getEvents, existsEventOverlapping'
        ],
        // non-cacheable actions
        [
            LendingController::class => 'show, reserve, approve, reject, cancel',
            AjaxConnectController::class => 'getEvents, existsEventOverlapping'
        ]
    );


    // wizards
    ExtensionManagementUtility::addPageTSConfig('
        mod {
            wizards.newContentElement.wizardItems.plugins {
                elements {
                    lending {
                        iconIdentifier = cylending-plugin-lending
                        title = LLL:EXT:cy_lending/Resources/Private/Language/locallang_be_lending.xlf:plugin.name 
                        description = LLL:EXT:cy_lending/Resources/Private/Language/locallang_be_lending.xlf:plugin.description
                        tt_content_defValues {
                            CType = list
                            list_type = cylending_lending
                        }
                    }
                }
                show = *
            }
       }');

    $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
    $iconRegistry->registerIcon('cylending-plugin-lending', SvgIconProvider::class, [
        'source' => 'EXT:cy_lending/Resources/Public/Icons/PluginLending.svg'
    ]);

});


// E-Mail-Templates
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']['cyLending-ApproverMessageMail']    = 'EXT:cy_lending/Resources/Private/Templates/ApproverMessageMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths']['cyLending-ApproverMessageMail']    = 'EXT:cy_lending/Resources/Private/Layouts/ApproverMessageMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths']['cyLending-ApproverMessageMail']    = 'EXT:cy_lending/Resources/Private/Partials/ApproverMessageMail/';

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']['cyLending-AvailabilityRequestResultMail']    = 'EXT:cy_lending/Resources/Private/Templates/AvailabilityRequestResultMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths']['cyLending-AvailabilityRequestResultMail']    = 'EXT:cy_lending/Resources/Private/Layouts/AvailabilityRequestResultMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths']['cyLending-AvailabilityRequestResultMail']    = 'EXT:cy_lending/Resources/Private/Partials/AvailabilityRequestResultMail/';

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']['cyLending-InformCancelLendingMail']    = 'EXT:cy_lending/Resources/Private/Templates/InformCancelLendingMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths']['cyLending-InformCancelLendingMail']    = 'EXT:cy_lending/Resources/Private/Layouts/InformCancelLendingMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths']['cyLending-InformCancelLendingMail']    = 'EXT:cy_lending/Resources/Private/Partials/InformCancelLendingMail/';

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']['cyLending-InformPreviousBorrowerMail']    = 'EXT:cy_lending/Resources/Private/Templates/InformPreviousBorrowerMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths']['cyLending-InformPreviousBorrowerMail']    = 'EXT:cy_lending/Resources/Private/Layouts/InformPreviousBorrowerMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths']['cyLending-InformPreviousBorrowerMail']    = 'EXT:cy_lending/Resources/Private/Partials/InformPreviousBorrowerMail/';

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']['cyLending-ObserverMessageMail']    = 'EXT:cy_lending/Resources/Private/Templates/ObserverMessageMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths']['cyLending-ObserverMessageMail']    = 'EXT:cy_lending/Resources/Private/Layouts/ObserverMessageMail/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths']['cyLending-ObserverMessageMail']    = 'EXT:cy_lending/Resources/Private/Partials/ObserverMessageMail/';


