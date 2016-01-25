<?php
defined('TYPO3_MODE') or die('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
    options.saveDocNew.tx_restructureredirect_redirects = 1
');

# register userFunc
$TYPO3_CONF_VARS['EXTCONF']['realurl']['decodeSpURL_preProc']['restructure_redirect'] =
    \MFC\RestructureRedirect\Hooks\HooksHandlerHook::class . '->user_decodeSpURL_preProc';

$TYPO3_CONF_VARS['SC_OPTIONS']['tce']['formevals']['tx_restructure_redirect_uniquestring'] =
    \MFC\RestructureRedirect\Hooks\UniqueStringHook::class;

$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['restructure_redirect'] =
    \MFC\RestructureRedirect\Hooks\MoveRecordHook::class;
