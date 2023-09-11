<?php
return
    [
        'ctrl' => [
            'title' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.lending',
            'label' => 'object',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'delete' => 'deleted',
            'default_sortby' => 'from',
            'iconfile' => 'EXT:cy_lending/Resources/Public/Icons/LendingObject.svg',
            'searchFields' => 'object, from, borrower, approver, state, purpose',
        ],
        'types' => [
            '1' => [
                'showitem' => 'object, from, until, borrower, purpose, state, borrower_comment, public_borrower_comment, approver, approver_comment, public_approver_comment'
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
                    'type' => 'input',
                    'dbType' => 'datetime',
                    'renderType' => 'inputDateTime',
                    'eval' => 'datetime',
                    'required' => true,
                    'default' => date('Y-m-d H:i:s', time()),
                ]
            ],
            'until' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.until',
                'config' => [
                    'type' => 'input',
                    'dbType' => 'datetime',
                    'renderType' => 'inputDateTime',
                    'eval' => 'datetime',
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
            'state' => [
                'label' => 'LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.state',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.state.unknown', 0],
                        ['LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.state.availabilityRequest', 1],
                        ['LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.state.approved', 2],
                        ['LLL:EXT:cy_lending/Resources/Private/Language/locallang_db.xlf:tx_cylending_domain_model_product_lending.state.rejected', 3],
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
        ]

    ];

