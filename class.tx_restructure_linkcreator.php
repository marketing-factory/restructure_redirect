<?php
/**
 * class for creating fe link from be
 *
 *
 */

/**
 * Class tx_restructure_linkcreator
 */
class tx_restructure_linkcreator {
	protected $localTSFE;

	public function __construct($uid) {
		$rootline = t3lib_BEfunc::BEgetRootLine($uid);
		$rootpid = 1;
		foreach ($rootline as $line ) {
			if ($line['uid'] > 0) {
				$rootpid = $line['uid'];
			}
			if ($line['is_siteroot'] == 1 ) {
				break;
			}
		}

		if (!is_object($GLOBALS['TT'])) {
			$GLOBALS['TT'] = new t3lib_timeTrack;
			$GLOBALS['TT']->start();
		}
	// Create the TSFE class.
		/** @var tslib_fe localTSFE */
		$this->localTSFE = t3lib_div::makeInstance('tslib_fe',$GLOBALS['TYPO3_CONF_VARS'],$rootpid,'0',1,'','','','');
		$GLOBALS['TSFE'] = $this->localTSFE;
		$this->localTSFE->connectToDB();
		$this->localTSFE->initFEuser();
		$this->localTSFE->fetch_the_id();
		$this->localTSFE->getPageAndRootline();
		$this->localTSFE->initTemplate();
		$this->localTSFE->tmpl->getFileName_backPath = PATH_site;
		$this->localTSFE->forceTemplateParsing = 1;
		$this->localTSFE->getConfigArray();
		$GLOBALS['TSFE']->includeTCA();
	}

	/**
	 * getLink
	 * @param integer $uid siteid
	 * @param array $urlParameters
	 * @return string
	 *
	 */

	public function getLink($uid, $urlParameters = array()) {
		$cObj = t3lib_div::makeInstance('tslib_cObj');
		$cObj->start(array(),'');
		$linkURL = $cObj->getTypoLink_URL($uid,$urlParameters);
		return $linkURL;
	}

	/**
	 * createRedirectEntry
	 * @param integer $pid
	 * @param string $link
	 * @return void
	 *
	 */

	public function createRedirectEntry ($pid,$link) {
		$table =  "tx_restructureredirect_redirects";
		if (!$this->linkExists($link)) {
			$field_values=array(
				'url' => $link,
				'pid' => $pid,
				'tstamp' => time(),
				'crdate' => time()
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery( $table , $field_values );
		}
	}

	/**
	 * linkExists
	 *
	 * @param string $link
	 * @return boolean
	 *
	 */
	protected function linkExists ($link) {

		$table = "tx_restructureredirect_redirects";
		$enableFields = t3lib_BEfunc::BEenableFields($table);
		$where = "url=". $GLOBALS['TYPO3_DB']->fullQuoteStr($link,$table);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTQuery('*', $table,$where . $enableFields);
		if ($res) {
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				return TRUE;
			}

		}
		return FALSE;
	}
}
?>