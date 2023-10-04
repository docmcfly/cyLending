<?php
use Cylancer\CyLending\Controller\AjaxConnectController;
use Cylancer\CyLending\Controller\LendingController;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {

    ExtensionUtility::configurePlugin(
        'CyLending',
        'Lending',
        [
            LendingController::class => 'show, reserve, approve, reject',
            AjaxConnectController::class => 'getEvents'
        ],
        // non-cacheable actions
        [
            LendingController::class => 'show, reserve, approve, reject',
            AjaxConnectController::class => 'getEvents'
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

    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon('cylending-plugin-lending', \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class, [
        'source' => 'EXT:cy_lending/Resources/Public/Icons/PluginLending.svg'
    ]);

});