<?php
defined('TYPO3_MODE') or die('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
    options.saveDocNew.tx_restructureredirect_redirects = 1
');

# register userFunc
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['decodeSpURL_preProc']['restructure_redirect'] =
    'Mfc\\RestructureRedirect\\Hooks\\HooksHandlerHook->user_decodeSpURL_preProc';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['tx_restructure_redirect_uniquestring'] =
    'Mfc\\RestructureRedirect\\Hooks\\UniqueStringHook';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['restructure_redirectMove'] =
    'Mfc\\RestructureRedirect\\Hooks\\MoveRecordHook';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['restructure_redirectUpdate'] =
    'Mfc\\RestructureRedirect\\Hooks\\UpdateRecordHook';
