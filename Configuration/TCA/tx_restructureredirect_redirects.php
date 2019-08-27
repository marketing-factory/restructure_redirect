<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_restructureredirect_redirects');

$languageFile = 'LLL:EXT:restructure_redirect/Resources/Private/Language/locallang_db.xml:';

return array(
    'ctrl' => array(
        'title' => $languageFile . 'tx_restructureredirect_redirects',
        'label' => 'url',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ),
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',

        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('restructure_redirect') .
            'Resources/Public/Icon/tx_restructureredirect_redirects.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'hidden,starttime,endtime,fe_group,url,expire,sys_language_uid,l10n_parent'
    ),
    'feInterface' => $GLOBALS['TCA']['tx_restructureredirect_redirects']['feInterface'],
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xml:LGL.hidden',
            'config'  => array(
                'type'    => 'check',
                'default' => '0'
            )
        ),
        'sys_language_uid' => $GLOBALS['TCA']['tt_content']['columns']['sys_language_uid'],

        'l10n_parent' => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xml:LGL.l18n_parent',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('', 0),
                ),
                'foreign_table' => 'tx_restructureredirect_redirects',
                'foreign_table_where' => 'AND tx_restructureredirect_redirects.pid=###CURRENT_PID###
                    AND tx_restructureredirect_redirects.sys_language_uid IN (-1,0)',
            )
        ),
        'starttime' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xml:LGL.starttime',
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
            'label'   => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config'  => array(
                'type'     => 'input',
                'size'     => '8',
                'max'      => '20',
                'eval'     => 'date',
                'checkbox' => '0',
                'default'  => '0',
                'range'    => array(
                    'upper' => mktime(3, 14, 7, 1, 19, date('Y')+10),
                    'lower' => mktime(0, 0, 0, date('m')-1, date('d'), date('Y'))
                )
            )
        ),
        'fe_group' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xml:LGL.fe_group',
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
            'label' => $languageFile . 'tx_restructureredirect_redirects.url',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'eval' => 'required,trim,nospace,tx_restructure_redirect_uniquestring',
            )
        ),
        'expire' => array(
            'exclude' => 0,
            'label' => $languageFile . 'tx_restructureredirect_redirects.expire',
            'config' => array(
                'type'     => 'input',
                'size'     => '12',
                'max'      => '20',
                'eval'     => 'datetime',
                'checkbox' => '0',
                // @todo change date in future to 6 month for relaunch. must be resetted to 3 later
                'default'  => mktime(date('H'), date('i'), 0, date('m') + 6, date('d'), date('Y')),
            )
        ),
        'last_called' => array(
            'exclude' => 1,
            'label' => $languageFile . 'tx_restructureredirect_redirects.last_called',
            'config'  => array(
                'type'     => 'input',
                'size'     => '8',
                'max'      => '20',
                'eval'     => 'datetime',
                'checkbox' => '0',
                'default'  => '0',
                'readOnly' => 1,
            )
        ),
        'last_referer' => array(
            'exclude' => 1,
            'label' => $languageFile . 'tx_restructureredirect_redirects.last_referer',
            'config'  => array(
                'type'     => 'input',
                'size'     => '60',
                'checkbox' => '0',
                'default'  => '',
                'readOnly' => 1,
            )
        ),
        'hits_count' => array(
            'exclude' => 1,
            'label' => $languageFile . 'tx_restructureredirect_redirects.hits_count',
            'config'  => array(
                'type'     => 'input',
                'size'     => '8',
                'max'      => '20',
                'checkbox' => '0',
                'default'  => 0,
                'readOnly' => 1,
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden, sys_language_uid, l10n_parent;;1;;1-1-1, url, expire,
         last_called, last_referer, hits_count')
    ),
    'palettes' => array(
        '1' => array('showitem' => 'starttime, endtime, fe_group')
    )
);
