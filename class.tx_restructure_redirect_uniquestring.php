<?php
class tx_restructure_redirect_uniquestring {
	/**
	 * evaluateFieldValue tca eval function for cheking on unique entries
	 *
	 */
	function evaluateFieldValue($value, $is_in, &$set) {
		$set=true;

		//hole PID aus Post Variable (nicht sehr schön, aber scheinbar der einzige Weg?!)
		$mypid = t3lib_div::_GP('popViewId');
		$datas = ($_POST['data']['tx_restructureredirect_redirects']);
		$keys = array_keys($datas);
		$myUid = intval($keys[0]);
		if ($myUid > 0) {
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('url','tx_restructureredirect_redirects','NOT uid ='.$myUid. ' AND  deleted = 0 AND url="'.$value.'"' );
		} else {
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('url','tx_restructureredirect_redirects','deleted = 0 and url="'.$value.'"' );
		}




		//existiert schon ein Eintrag, dann den neuen NICHT speichern und Fehlermeldung werfen
		if (count($result)>0 ) {
			$set = false;
			$message = t3lib_div::makeInstance('t3lib_FlashMessage', 'URL "'.$value.'" schon vorhanden', 'URL nicht geändert!',t3lib_FlashMessage::ERROR);
			t3lib_FlashMessageQueue::addMessage($message);
		}
		return $value;
	}
}
?>