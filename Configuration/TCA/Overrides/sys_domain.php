<?php

$tempColumns = array(
    'sys_language_uid' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:cms/locallang_ttc.xml:sys_language_uid_formlabel',
        'config' => array(
            'type' => 'select',
            'foreign_table' => 'sys_language',
            'foreign_table_where' => 'ORDER BY sys_language.title',
            'items' => array(
                array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
                array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
            ),
        ),
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_domain', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'sys_domain',
    'sys_language_uid',
    '',
    'before:domainName'
);
