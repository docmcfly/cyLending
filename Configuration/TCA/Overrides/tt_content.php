<?php
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die('Access denied.');

(static function (): void{

    ExtensionUtility::registerPlugin(
        'CyLending',
        'Lending',
        'LLL:EXT:cy_lending/Resources/Private/Language/locallang_be_lending.xlf:plugin.name',
        'EXT:cy_lending/Resources/Public/Icons/PluginLending.svg'
    );

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['cylending_lending'] = 'pi_flexform';
    ExtensionManagementUtility::addPiFlexFormValue(
        // plugin signature: <extension key without underscores> '_' <plugin name in lowercase>
        'cylending_lending',
        // Flexform configuration schema file
        'FILE:EXT:cy_lending/Configuration/FlexForms/Lending.xml'
    );

})();