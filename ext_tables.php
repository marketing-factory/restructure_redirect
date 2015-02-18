<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::allowTableOnStandardPages('tx_restructureredirect_redirects');

$TCA['tx_restructureredirect_redirects'] = array(
	'ctrl' => array(
		'title'     => 'LLL:EXT:restructure_redirect/locallang_db.xml:tx_restructureredirect_redirects',
		'label'     => 'url',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_restructureredirect_redirects.gif',
	),
);

if (TYPO3_MODE=='BE')   {
	t3lib_extMgm::addModule('tools','redirect','',t3lib_extMgm::extPath($_EXTKEY).'mod/');
}

$tempColumns = array (
	'sys_language_uid' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:cms/locallang_ttc.xml:sys_language_uid_formlabel',
		'config' => array(
			'type' => 'select',
			'foreign_table' => 'sys_language',
			'foreign_table_where' => 'ORDER BY sys_language.title',
			'items' => array(
				array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
				array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
			)
		)
	),
);

t3lib_div::loadTCA('sys_domain');
t3lib_extMgm::addTCAcolumns('sys_domain', $tempColumns, 1);
t3lib_extMgm::addToAllTCAtypes('sys_domain', 'sys_language_uid', '', 'before:domainName');
?>