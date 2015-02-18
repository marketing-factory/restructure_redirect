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

	public $settings;

	protected $parent;

	public function __construct($uid) {
		$rootline = t3lib_BEfunc::BEgetRootLine($uid);
		$rootpid = 1;

		$this->settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['restructure_redirect']);
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
		/** @var tslib_cObj $cObj */
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

	public function createRedirectEntry ($pid,$link, $sys_language_uid) {
		$table =  "tx_restructureredirect_redirects";
		if (!$sys_language_uid) {
			$this->parent = 0;
		}
		if (!$this->linkExists($link, $sys_language_uid)) {
			$field_values=array(
				'url' => $link,
				'pid' => $pid,
				'rootpage' => $this->getRootlinePage($pid),
				'sys_language_uid' => $sys_language_uid,
				'l10n_parent' => $this->parent,
				'tstamp' => time(),
				'crdate' => time(),
				'expire' => mktime(date('H'), date('i'), 0, date('m') + $this->settings['expireRange'], date('d'), date('Y')),
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery( $table , $field_values );
			if (!$sys_language_uid) {
				$this->linkExists($link, $sys_language_uid);
			}
		}
	}

	/**
	 * linkExists
	 *
	 * @param string $link
	 * @param integer $sysLanguageUid
	 * @return boolean
	 *
	 */
	protected function linkExists ($link, $sysLanguageUid) {

		$table = "tx_restructureredirect_redirects";
		$enableFields = t3lib_BEfunc::BEenableFields($table);
		$where = 'sys_language_uid = ' . $sysLanguageUid .' AND url='. $GLOBALS['TYPO3_DB']->fullQuoteStr($link,$table);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTQuery('*', $table,$where . $enableFields);
		if ($res) {
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if ($row['sys_language_uid'] == 0) {
					$this->parent = $row['uid'];
				}
				return TRUE;
			}

		}
		return FALSE;
	}

	/**
	 * Exclude language parameter from url
	 *
	 * @param string $link
	 * @return string
	 */
	public function excludeLanguageParamFromUrl($link) {

		$excludeParams = t3lib_div::explodeUrl2Array('L', TRUE);
		$parts = parse_url($link);
		parse_str($parts['query'], $queryParts);
		$parts['query'] = $queryParts;
		$parts['query'] = t3lib_div::arrayDiffAssocRecursive($parts['query'], $excludeParams);

		$link = $parts['path'];

		if ($parts['query'] != array()) {
			foreach ($parts['query'] as $key => $value) {
				$parts['query'][$key] = $key . '=' . $value;
			}
			$link .= '?' . implode ('&', $parts['qeury']);
		}

		return $link;
	}

	protected function getRootlinePage ($uid) {
		if ($uid == $GLOBALS['TSFE']->id) {
			$rootPage = $GLOBALS['TSFE']->rootLine[0]['uid'];
		} else {
			$rootPage = $this->getRecursivePid($uid);
		}

		return $rootPage;
	}

	protected function getRecursivePid($uid) {
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'uid, pid',
			'pages',
			'uid =' . $uid
		);

		if ($result['pid'] > 0) {
			$rootPage = $this->getRecursivePid($result['pid']);
		} elseif ($result['pid'] == 0) {
			$rootPage = $uid;
		}

		return $rootPage;
	}
}
?>