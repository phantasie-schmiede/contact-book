#
# Table structure for table 'tx_psbcontacts_log'
#
CREATE TABLE tx_psbcontacts_log
(
    uid int(11) NOT NULL auto_increment,
    request_id varchar(13) DEFAULT '' NOT NULL,
    time_micro double(16, 4) NOT NULL default '0',
    component varchar(255) DEFAULT '' NOT NULL,
    level tinyint(1) unsigned DEFAULT '0' NOT NULL,
    message text,
    data text,

    PRIMARY KEY uid (uid),
    KEY request (request_id)
);
