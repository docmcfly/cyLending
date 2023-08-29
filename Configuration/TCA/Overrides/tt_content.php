<?php
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['cylending_lending'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    // plugin signature: <extension key without underscores> '_' <plugin name in lowercase>
    'cylending_lending', 
    // Flexform configuration schema file
    'FILE:EXT:cy_lending/Configuration/FlexForms/Lending.xml');