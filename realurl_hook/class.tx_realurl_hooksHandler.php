<?php
require_once(t3lib_extMgm::extPath('restructure_redirect' , 'class.tx_restructure_linkcreator.php'));

/**
 * Class tx_realurl_hooksHandler
 */
class tx_realurl_hooksHandler {

	/** @var  t3lib_db $database */
	public $database;

	/**
	 * user_decodeSpURL_preProc
	 * hook for realurl to redirect if neccessary
	 *
	 * @param array $hookParams
	 * @return void
	 */
	public function user_decodeSpURL_preProc($hookParams) {
		if (TYPO3_MODE == 'BE') {
			return;
		}
		/** @var t3lib_db $database */
		$this->database = $GLOBALS['TYPO3_DB'];
		$table = "tx_restructureredirect_redirects";
		$enableFields = "
		AND tx_restructureredirect_redirects.deleted=0
		AND tx_restructureredirect_redirects.hidden=0
		AND tx_restructureredirect_redirects.starttime<=" . time() . "
		AND (tx_restructureredirect_redirects.endtime=0 OR tx_restructureredirect_redirects.endtime>" . time() . ")
		AND (
			tx_restructureredirect_redirects.fe_group=''
			OR tx_restructureredirect_redirects.fe_group IS NULL
			OR tx_restructureredirect_redirects.fe_group='0'
			OR FIND_IN_SET('0',tx_restructureredirect_redirects.fe_group)
			OR FIND_IN_SET('-1',tx_restructureredirect_redirects.fe_group
		))";

		$where = "(url='" . $this->database->quoteStr(parse_url($hookParams['URL'], PHP_URL_PATH), $table) . "'";
		$where .= " OR url='/" . $this->database->quoteStr(parse_url($hookParams['URL'], PHP_URL_PATH), $table) . "')";
		$where .= ' AND (expire=0 OR  expire>' . time() . ') ';
		$res = $this->database->exec_SELECTQuery('*', $table, $where . $enableFields, '', '', 1);

		if ($res) {
			$row = $this->database->sql_fetch_assoc($res);
			if ($row['url'] && strlen($row['url']) > 0) {
				$redirectId = $row['pid'];
				$params = $this->getUrlParams($hookParams['URL']);

				unset ($params['id']);
				/** @var tx_restructure_linkcreator $itsLink */
				$itsLink = t3lib_div::makeInstance('tx_restructure_linkcreator', $redirectId);
				$redirectUrl = $itsLink->getLink($redirectId, $params);
				if ($redirectUrl == $hookParams['URL']) {
					$this->sendErrorMail($redirectUrl, $row);
					return;
				}
				if (isset($GLOBALS['TSFE']->config['config']['baseURL']) &&  $GLOBALS['TSFE']->config['config']['baseURL'] != '') {
					$domain = rtrim($GLOBALS['TSFE']->config['config']['baseURL'], '/') . '/';
				} else {
					$domain = t3lib_befunc::getViewDomain($redirectId) . '/';
				}
				$redirectUrl = $domain . $redirectUrl;
				t3lib_utility_Http::redirect(t3lib_div::locationHeaderUrl($redirectUrl), t3lib_utility_Http::HTTP_STATUS_301);
			}
		}
	}

	/**
	 * getUrlParams
	 * getparams from realurl
	 *
	 * @param string $url
	 * @return array
	 *
	 */

	private function getUrlParams($url) {
		$table = 'tx_realurl_urlencodecache';
		$where = "content ='" . $url . "'";
		$res = $this->database->exec_SELECTQuery('*', $table, $where, '', '', 1);
		if ($res) {
			$row = $this->database->sql_fetch_assoc($res);
			$origparams = t3lib_div::trimExplode('|', $row['origparams']);
			if ($origparams[1]) {
				$params = t3lib_div::explodeUrl2Array($origparams[1]);
				return $params;
			}
		}
		return array();
	}

	/**
	 * send error mail with url, if redirect doesn't work because
	 * url and redirect url are the same
	 *
	 * @param string $foundUrl
	 * @param array $row
	 * @return void
	 */
	private function sendErrorMail($foundUrl, $row) {
		$subject = 'problems on host ' . $GLOBALS['_ENV']['HTTP_HOST'] . ' with url ' . $foundUrl;

		mail('kontroll.heimwerker@marketing-factory.de',
			$subject,
			'Please test the url ' . $GLOBALS['TSFE']->config['config']['baseURL'] . $foundUrl .
			' on page with id ' . $row['pid'] . ' for host ' . $GLOBALS['_ENV']['HTTP_HOST']
		);

	}

}

?>