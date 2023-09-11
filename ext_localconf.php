<?php
use Cylancer\CyLending\Controller\AjaxConnectController;
use Cylancer\CyLending\Controller\LendingController;

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'CyLending',
        'Lending',
        [
            LendingController::class => 'show, reserve, approve, reject'
        ],
        // non-cacheable actions
        [
            LendingController::class => 'show, reserve, approve, reject'
        ]
    );

    // wizards
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
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


    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'CyLenduing',
        'AjaxConnect',
        [AjaxConnectController::class => 'getEvents'],
        [AjaxConnectController::class => 'getEvents']
     );

    /*
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'cylending',
        'setup',
        "@import 'EXT:cy_lending/Configuration/TypoScript/setup.typoscript'"
     );
     */

});