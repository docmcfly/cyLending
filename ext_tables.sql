

CREATE TABLE tx_cylending_domain_model_lendingobject (
    `title`                      varchar(255)                DEFAULT ''  NOT NULL,
    `color`                      varchar(7)                  DEFAULT '#123456'  NOT NULL,
    approver_group               int (11) UNSIGNED           DEFAULT '0' NOT NULL,
    observer_group               int (11) UNSIGNED           DEFAULT '0' NOT NULL,
    group_name                   varchar(255)                DEFAULT ''  NOT NULL,
    quantity                     int (11) UNSIGNED           DEFAULT '1' NOT NULL,
);

CREATE TABLE tx_cylending_domain_model_lending (
    `object`                     int (11) UNSIGNED           DEFAULT '0' NOT NULL,
    `from`                       datetime                    NOT NULL,
    until                        datetime                    NOT NULL,
    borrower                     int(11) UNSIGNED            DEFAULT '0' NOT NULL,
    purpose                      text                        DEFAULT '',
    `state`                      SMALLINT (5) UNSIGNED       DEFAULT '0' NOT NULL,
    approver                     int(11) UNSIGNED            DEFAULT '0' NOT NULL,
    high_priority                SMALLINT (5) UNSIGNED       DEFAULT '0' NOT NULL,
    quantity                     int (11) UNSIGNED           DEFAULT '1' NOT NULL,
);       

ALTER TABLE `tx_cylending_domain_model_lending` ADD INDEX `IDX_UNTIL` (`until`); 