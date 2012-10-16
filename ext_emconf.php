<?php

########################################################################
# Extension Manager/Repository config file for ext "restructure_redirect".
#
# Auto generated 16-10-2012 12:05
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Restructure Redirect',
	'description' => '',
	'category' => 'be',
	'author' => 'Christoph Hofmann',
	'author_email' => 'typo3<add>@its-hofmann.de',
	'shy' => '',
	'dependencies' => 'realurl',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.3',
	'constraints' => array(
		'depends' => array(
			'realurl' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:22:{s:9:"ChangeLog";s:4:"628b";s:10:"README.txt";s:4:"ee2d";s:36:"class.tx_restructure_linkcreator.php";s:4:"b4dd";s:44:"class.tx_restructure_redirect_moverecord.php";s:4:"110d";s:46:"class.tx_restructure_redirect_uniquestring.php";s:4:"f1b3";s:12:"ext_icon.gif";s:4:"3527";s:17:"ext_localconf.php";s:4:"6eb5";s:14:"ext_tables.php";s:4:"6cc0";s:14:"ext_tables.sql";s:4:"53c4";s:41:"icon_tx_restructureredirect_redirects.gif";s:4:"475a";s:16:"locallang_db.xml";s:4:"85a8";s:7:"tca.php";s:4:"1006";s:19:"doc/wizard_form.dat";s:4:"0635";s:20:"doc/wizard_form.html";s:4:"53a1";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"9185";s:14:"mod1/index.php";s:4:"4e53";s:18:"mod1/locallang.xlf";s:4:"d98c";s:22:"mod1/locallang_mod.xlf";s:4:"ee94";s:17:"mod1/redirect.gif";s:4:"4d07";s:19:"mod1/redirects.html";s:4:"429a";s:46:"realurl_hook/class.tx_realurl_hooksHandler.php";s:4:"61c8";}',
	'suggests' => array(
	),
);

?>