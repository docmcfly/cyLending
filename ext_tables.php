<?php
defined('TYPO3_MODE') || die('Access denied.');


call_user_func(
    function()
    {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'CyLending',
            'Lending',
            'LLL:EXT:cy_lending/Resources/Private/Language/locallang_be_lending.xlf:plugin.name',
            'EXT:cy_lending/Resources/Public/Icons/PluginLending.svg'
            );
        
}
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('cy_lending', 'Configuration/TypoScript', 'Lending');