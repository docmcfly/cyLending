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

declare(strict_types=1);

defined('TYPO3') || die('Access denied.');

// automatic garbage collention of stored messages
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask']['options']['tables']['tx_cylending_domain_model_lending'] = [
    'dateField' => 'tstamp',
    'expirePeriod' => 45
];