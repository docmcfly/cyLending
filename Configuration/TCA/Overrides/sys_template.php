<?php
declare(strict_types=1);

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

call_user_func(function()
{
     \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('cy_lending', 'Configuration/TypoScript', 'Lending');
});
