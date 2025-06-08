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
            'title' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.lending',
            'label' => 'object',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'delete' => 'deleted',
            'default_sortby' => 'from DESC, purpose ASC ',
            'iconfile' => 'EXT:cy_lending/Resources/Public/Icons/Lending.svg',
            'searchFields' => 'object, from, borrower, purpose, approver, state, purpose, high_priority',
        ],
        'types' => [
            '1' => [
                'showitem' => 'object, quantity, from, until, borrower, purpose, high_priority, state, borrower_comment, public_borrower_comment, approver, approver_comment, public_approver_comment'
            ]
        ],
        'columns' => [

            'object' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.object',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_cylending_domain_model_lendingobject',
                    'minitems' => 1,
                    'maxitems' => 1,
                    'required' => true,
                ]
            ],
            'from' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.from',
                'config' => [
                    'type' => 'datetime',
                    'dbType' => 'datetime',
                    'format' => 'datetime',
                    'required' => true,
                    'default' => time(),
                ]
            ],
            'until' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.until',
                'config' => [
                    'type' => 'datetime',
                    'dbType' => 'datetime',
                    'format' => 'datetime',
                    'required' => true,
                ]
            ],
            'borrower' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.borrower',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'fe_users',
                    'minitems' => 1,
                    'maxitems' => 1,
                    'required' => true,
                ]
            ],
            'purpose' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.purpose',
                'config' => [
                    'type' => 'input',
                    'required' => true,
                    'readOnly' => false,
                ]
            ],
            'high_priority' => [
                'exclude' => false,
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.highPriority',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'items' => [
                        [
                            'label' => '',
                        ]
                    ],
                ]
            ],
            'state' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.state',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.state.unknown',
                            'value' => 0
                        ],
                        [
                            'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.state.availabilityRequest',
                            'value' => 1
                        ],
                        [
                            'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.state.approved',
                            'value' => 2
                        ],
                        [
                            'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.state.rejected',
                            'value' => 3
                        ],
                        [
                            'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.state.canceled',
                            'value' => 4
                        ],
                    ],
                    'minitems' => 1,
                    'maxitems' => 1,
                    'required' => true,
                    //  'readOnly' => true,
                    'exclude' => true,
                ]
            ],
            'approver' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.approver',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'fe_users',
                    'minitems' => 1,
                    'maxitems' => 1,
                    'required' => true,
                ]
            ],
            'quantity' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.quantity',
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

