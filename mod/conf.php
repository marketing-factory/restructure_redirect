<?php
define('TYPO3_MOD_PATH', '../typo3conf/ext/restructure_redirect/mod/');
$BACK_PATH='../../../../typo3/';

$MLANG['default']['tabs_images']['tab'] = 'redirect.gif';
if (class_exists(t3lib_utility_VersionNumber) && t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) >= 4006000) {
	$MLANG['default']['ll_ref']='LLL:EXT:restructure_redirect/mod/locallang_mod.xlf';
} else {
	$MLANG['default']['ll_ref']='LLL:EXT:restructure_redirect/mod/locallang_mod.xml';
}
$MCONF['script']='_DISPATCH';
$MCONF['access']='admin';
$MCONF['name']='tools_redirect';


?>