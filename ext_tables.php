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
?>