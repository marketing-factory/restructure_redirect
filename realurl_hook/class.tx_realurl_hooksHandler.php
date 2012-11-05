<?php
require_once(t3lib_extMgm::extPath('restructure_redirect').'class.tx_restructure_linkcreator.php');
class tx_realurl_hooksHandler  {

	/**
	 *
	 * user_decodeSpURL_preProc
	 * hook for realurl to redirect if neccessary
	 *
	 * @param array $hookParams
	 * @param object $pObj
	 *
	 */
	function user_decodeSpURL_preProc ( $hookParams, $pObj ) {
		$table = "tx_restructureredirect_redirects";
		//$enableFields = $GLOBALS['TSFE']->sys_page->enableFields($table);
		$enableFields = "
		AND tx_restructureredirect_redirects.deleted=0
		AND tx_restructureredirect_redirects.hidden=0
		AND tx_restructureredirect_redirects.starttime<=".time()."
		AND (tx_restructureredirect_redirects.endtime=0 OR tx_restructureredirect_redirects.endtime>".time().")
		AND (
			tx_restructureredirect_redirects.fe_group=''
			OR tx_restructureredirect_redirects.fe_group IS NULL
			OR tx_restructureredirect_redirects.fe_group='0'
			OR FIND_IN_SET('0',tx_restructureredirect_redirects.fe_group)
			OR FIND_IN_SET('-1',tx_restructureredirect_redirects.fe_group
		))";

		$where = "url='" . $GLOBALS['TYPO3_DB']->quoteStr($hookParams['URL'],$table)."'";
		$where .= ' AND (expire=0 OR  expire>'.time().') ';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTQuery('*', $table,$where . $enableFields, '','',1);

		if ($res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			if ($row['url'] && strlen($row['url'])> 0) {
				$redirectId = $row['pid'];
				$params = $this->getUrlParams($hookParams[URL]);

				unset ($params['id']);
				$its_link = t3lib_div::makeInstance('tx_restructure_linkcreator',$redirectId);
				$redirectUrl = $its_link->getLink($redirectId,$params);
				if ($redirectUrl == $hookParams[URL]  ) {
					return;
				}

				header('HTTP/1.1 301 Moved Permanently');
				//header('Location: ' . t3lib_div::locationHeaderUrl($redirectUrl));
				header('Location: ' . 'http://www.heimwerker.de/' . ltrim($redirectUrl, '/'));
				exit();
			}
		}
	}

	/**
	 * getUrlParams
	 * getparams from realurl
	 * @param string $url
	 * @return array
	 *
	 */

	private function getUrlParams ($url) {
		$table = 'tx_realurl_urlencodecache';
		$where = "content ='" .$url."'";
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTQuery('*', $table,$where , '','',1);
		if ($res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$origparams  = t3lib_div::trimExplode('|', $row['origparams']);
			if ($origparams[1]) {
				$params = t3lib_div::explodeUrl2Array($origparams[1]);
				return $params;
			}
		}
		return array();
	}



}
?>