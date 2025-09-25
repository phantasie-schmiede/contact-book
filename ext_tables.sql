#
# Table structure for table 'tx_contactbook_bank_data'
#
CREATE TABLE tx_contactbook_bank_data
(
    bank_code varchar(10) NOT NULL,
    bic varchar(20) DEFAULT '' NOT NULL,
    city varchar(40) DEFAULT '' NOT NULL,
    name varchar(60) DEFAULT '' NOT NULL,
    short_name varchar(30) DEFAULT '' NOT NULL,
    zip_code varchar(10) DEFAULT '' NOT NULL,

    PRIMARY KEY uid (bank_code)
);

#
# Table structure for table 'tx_contactbook_log'
#
CREATE TABLE tx_contactbook_log
(
    component varchar(255) DEFAULT '' NOT NULL,
    data text,
    level tinyint(1) unsigned DEFAULT '0' NOT NULL,
    message text,
    request_id varchar(13) DEFAULT '' NOT NULL,
    time_micro double(16, 4) NOT NULL default '0',
    uid int(11) NOT NULL auto_increment,

    PRIMARY KEY uid (uid),
    KEY request (request_id)
);
