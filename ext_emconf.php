<?php

########################################################################
# Extension Manager/Repository config file for ext "restructure_redirect".
#
# Auto generated 06-11-2012 15:09
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
	'author_email' => 'typo3<add>its-hofmann.de',
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
	'version' => '0.1.2',
	'constraints' => array(
		'depends' => array(
			'realurl' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:28:{s:9:"ChangeLog";s:4:"628b";s:36:"class.tx_restructure_linkcreator.php";s:4:"dc5f";s:44:"class.tx_restructure_redirect_moverecord.php";s:4:"110d";s:46:"class.tx_restructure_redirect_uniquestring.php";s:4:"f1b3";s:12:"ext_icon.gif";s:4:"3527";s:17:"ext_localconf.php";s:4:"33bc";s:14:"ext_tables.php";s:4:"7b42";s:14:"ext_tables.sql";s:4:"53c4";s:41:"icon_tx_restructureredirect_redirects.gif";s:4:"475a";s:16:"locallang_db.xml";s:4:"85a8";s:10:"README.txt";s:4:"ee2d";s:7:"tca.php";s:4:"1006";s:19:"doc/wizard_form.dat";s:4:"0635";s:20:"doc/wizard_form.html";s:4:"53a1";s:13:"mod/clear.gif";s:4:"cc11";s:12:"mod/conf.php";s:4:"424a";s:13:"mod/index.php";s:4:"216c";s:17:"mod/locallang.xlf";s:4:"d98c";s:17:"mod/locallang.xml";s:4:"e1e6";s:21:"mod/locallang_mod.xlf";s:4:"ee94";s:21:"mod/locallang_mod.xml";s:4:"a41b";s:16:"mod/redirect.gif";s:4:"4d07";s:18:"mod/redirects.html";s:4:"429a";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"75b5";s:14:"mod1/index.php";s:4:"fd7d";s:13:"mod1/list.gif";s:4:"2225";s:46:"realurl_hook/class.tx_realurl_hooksHandler.php";s:4:"64b4";}',
	'suggests' => array(
	),
);

?>