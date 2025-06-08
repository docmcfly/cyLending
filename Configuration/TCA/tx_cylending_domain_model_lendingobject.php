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
                'showitem' => 'group_name, title, approver_group, observer_group, high_priority_group, quantity, color'
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
                    'type' => 'color',
                    'required' => true,
                ]
            ],
            'approver_group' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lendingobject.approverGroup',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'fe_groups',
                    'maxitems' => 1,
                    'minitems' => 0,
                    'items' => [
                        [
                            'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lendingobject.approverGroup.none',
                            'value' =>  0
                        ]
                    ],
                ]
            ],
            'observer_group' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lendingobject.observerGroup',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'fe_groups',
                    'maxitems' => 1,
                    'minitems' => 0,
                    'items' => [
                        [
                            'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lendingobject.observerGroup.none',
                            'value' =>  0
                        ]
                    ],
                ]
            ],
            'high_priority_group' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lendingobject.highPriorityGroup',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'fe_groups',
                    'maxitems' => 1,
                    'minitems' => 0,
                    'items' => [
                        [
                            'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lendingobject.highPriorityGroup.none',
                            'value' =>  0
                        ]
                    ],
                ]
            ],
            'quantity' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lendingobject.quantity',
                'config' => [
                    'type' => 'number',
                    'required' => true,
                    'default' => 1,
                    'range' => [
                        'lower' => 1,
                        'upper' => 1000
                    ],
                ],
            ],
        ]

    ];