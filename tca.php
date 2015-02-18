<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_restructureredirect_redirects'] = array(
	'ctrl' => $TCA['tx_restructureredirect_redirects']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'hidden,starttime,endtime,fe_group,url,expire,sys_language_uid,l10n_parent'
	),
	'feInterface' => $TCA['tx_restructureredirect_redirects']['feInterface'],
	'columns' => array(
		'hidden' => array(
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array(
				'type'    => 'check',
				'default' => '0'
			)
		),
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
		'l10n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_restructureredirect_redirects',
				'foreign_table_where' => 'AND tx_restructureredirect_redirects.pid=###CURRENT_PID### AND tx_restructureredirect_redirects.sys_language_uid IN (-1,0)',
			)
		),
		'starttime' => array(
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config'  => array(
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'default'  => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => array(
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config'  => array(
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0',
				'range'    => array(
					'upper' => mktime(3, 14, 7, 1, 19, 2038),
					'lower' => mktime(0, 0, 0, date('m')-1, date('d'), date('Y'))
				)
			)
		),
		'fe_group' => array(
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.fe_group',
			'config'  => array(
				'type'  => 'select',
				'items' => array(
					array('', 0),
					array('LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.any_login', -2),
					array('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups'
			)
		),
		'url' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:restructure_redirect/locallang_db.xml:tx_restructureredirect_redirects.url',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'eval' => 'required,trim,nospace,tx_restructure_redirect_uniquestring',
			)
		),
		'expire' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:restructure_redirect/locallang_db.xml:tx_restructureredirect_redirects.expire',
			'config' => array(
				'type'     => 'input',
				'size'     => '12',
				'max'      => '20',
				'eval'     => 'datetime',
				'checkbox' => '0',
				'default'  => mktime(date('H'), date('i'), 0, date('m') + 3, date('d'), date('Y')),
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'hidden, sys_language_uid, l10n_parent;;1;;1-1-1, url, expire')
	),
	'palettes' => array(
		'1' => array('showitem' => 'starttime, endtime, fe_group')
	)
);

$TCA['tx_restructureredirect_redirects']['ctrl']['languageField'] = 'sys_language_uid';
$TCA['tx_restructureredirect_redirects']['ctrl']['transOrigPointerField'] = 'l10n_parent';
?>