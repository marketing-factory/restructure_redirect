#
# Table structure for table 'tx_restructureredirect_redirects'
#
CREATE TABLE tx_restructureredirect_redirects (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	rootpage int(11) DEFAULT '1' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	url varchar(255) DEFAULT '' NOT NULL,
	target_domain varchar(255) DEFAULT '' NOT NULL,
	target_url varchar(255) DEFAULT '' NOT NULL,
	expire int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
) ENGINE=InnoDB;

#
# Table structure for table 'sys_domain'
#
CREATE TABLE sys_domain (
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
);