<?php
declare(strict_types = 1);
use Cylancer\CyLending\Domain\Model\FrontendUser;
use Cylancer\CyLending\Domain\Model\FrontendUserGroup;

return [
    FrontendUser::class => [
        'tableName' => 'fe_users'
    ],
    FrontendUserGroup::class => [
        'tableName' => 'fe_groups'
    ]
];
