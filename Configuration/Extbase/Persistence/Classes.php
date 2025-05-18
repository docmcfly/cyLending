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

return [
    \Cylancer\CyLending\Domain\Model\FrontendUser::class => [
        'tableName' => 'fe_users'
    ],
    \Cylancer\CyLending\Domain\Model\FrontendUserGroup::class => [
        'tableName' => 'fe_groups'
    ],
    \Cylancer\CyLending\Domain\Model\ContentElement::class => [
        'tableName' => 'tt_content'
    ]

];