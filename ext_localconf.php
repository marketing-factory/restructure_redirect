<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_restructureredirect_redirects=1
');

require_once(t3lib_extMgm::extPath('restructure_redirect'). 'realurl_hook/class.tx_realurl_hooksHandler.php');
# userFunc registrieren
//$TYPO3_CONF_VARS['EXTCONF']['realurl']['decodeSpURL_preProc'][]= 'tx_realurl_hooksHandler->user_encodeSpURL_externalURL';
$TYPO3_CONF_VARS['EXTCONF']['realurl']['decodeSpURL_preProc']['restructure_redirect']= 'tx_realurl_hooksHandler->user_decodeSpURL_preProc';
$TYPO3_CONF_VARS['SC_OPTIONS']['tce']['formevals']['tx_restructure_redirect_uniquestring'] =
	'EXT:restructure_redirect/class.tx_restructure_redirect_uniquestring.php';

$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass']['restructure_redirect'] =
'EXT:restructure_redirect/class.tx_its_redirect_moverecord.php:&tx_its_tcemain';
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['restructure_redirect'] =
'EXT:restructure_redirect/class.tx_its_redirect_moverecord.php:&tx_its_tcemain';

?>