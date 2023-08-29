<?php
return
    [
        'ctrl' => [
            'title' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lendingobject.object',
            'label' => 'title',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'delete' => 'deleted',
            'default_sortby' => 'title',
            'iconfile' => 'EXT:cy_lending/Resources/Public/Icons/LendingObject.svg',
            'searchFields' => 'title',
        ],
        'types' => [
            '1' => [
                'showitem' => 'group_name, title, color, approver_group, observer_group'
            ]
        ],
        'columns' => [
            'group_name' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lendingobject.groupName',
                'config' => [
                    'type' => 'input',
                    'nullable' => false,
                    'eval' => 'trim',
                    'max' => 255,
                    'min' => 0,
                    'required' => false,
                ]
            ],
            'title' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lendingobject.objectTitle',
                'config' => [
                    'type' => 'input',
                    'nullable' => false,
                    'eval' => 'trim',
                    'max' => 255,
                    'min' => 3,
                    'required' => true,
                ]
            ],
            'color' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lendingobject.objectColor',
                'config' => [
                    'type' => 'input',
                    'renderType' => 'colorpicker',
                    'required' => true,
                ]
            ],
            'approver_group' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lendingobject.approverGroup',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingleBox',
                    'foreign_table' => 'fe_groups',
                    'maxitems' => 1,
                    'minitems' => 0,
                ]
            ],
            'observer_group' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lendingobject.observerGroup',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingleBox',
                    'foreign_table' => 'fe_groups',
                    'maxitems' => 1,
                    'minitems' => 0,
                ]
            ],
        ]

    ];

