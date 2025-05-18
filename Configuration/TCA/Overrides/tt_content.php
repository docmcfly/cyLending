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

defined('TYPO3') || die('Access denied.');

$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups']['cyLending']
    = 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_be.xlf:plugins.group.cyLending.name';