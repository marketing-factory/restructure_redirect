<?php
class tx_restructure_redirect_recursivecheck {
	/**
	 * evaluateFieldValue tca eval function for cheking on unique entries
	 *
	 */
	function evaluateFieldValue($value, $is_in, &$set) {
		$set=true;

		//hole PID aus Post Variable (nicht sehr schön, aber scheinbar der einzige Weg?!)
		$mypid = intval(t3lib_div::_GP('popViewId'));
		$datas = ($_POST['data']['tx_restructureredirect_redirects']);
		$keys = array_keys($datas);
		$myUid = intval($keys[0]);


		require_once(t3lib_extMgm::extPath('restructure_redirect').'class.tx_restructure_linkcreator.php');
		require_once(t3lib_extMgm::extPath('realurl').'class.tx_realurl.php');

		/**
		 * Fake TT for realurl
		 */
		require_once(PATH_t3lib.'class.t3lib_timetracknull.php');
		$GLOBALS['TT'] = new t3lib_timeTrackNull;
		$GLOBALS['TT']->start();
		$GLOBALS['TT']->push('','Init for ' . __FILE__ . ' Method ' . __METHOD__);
		$GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe',
			$GLOBALS['TYPO3_CONF_VARS'],
			t3lib_div::_GP('id'),
			t3lib_div::_GP('type'),
			t3lib_div::_GP('no_cache'),
			t3lib_div::_GP('cHash'),
			t3lib_div::_GP('jumpurl'),
			t3lib_div::_GP('MP'),
			t3lib_div::_GP('RDCT')
		);

		$GLOBALS['TSFE']->csConvObj = t3lib_div::makeInstance('t3lib_cs');

		$tx_realurl = t3lib_div::makeInstance('myRealUrl');
		unset($tx_realurl->extConf['redirects'][$speakingURIpath]);
		unset($tx_realurl->extConf['redirects_regex']);
		$params = array('pObj'	=> $GLOBALS['TSFE']);
		$params['pObj']->siteScript = $datas[$myUid]['url'];
		$tx_realurl->mydecodeSpURL($params);
		$targetPid = $params[pObj]->id;
		$table = 'tx_restructureredirect_redirects';
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
		// Pid der quell Seite
		$ownPid = $mypid;

		$where = 'pid='.$targetPid;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTQuery('*', $table, $where . $enableFields);
		$urls = array();
		// Finde alle Urls auf der Ziel Seite
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$urls[] = $row['url'];
			}
		}
		// Prüfe ob auf der Ziel Seite ein Verweiss auf die Quell-Seite vorhanden ist
		$recursive = FALSE;
		foreach ($urls as $url) {
			$params = array('pObj'	=> $GLOBALS['TSFE']);
			$params['pObj']->siteScript = $url;
			$tx_realurl->mydecodeSpURL($params);
			if ($ownPid == intval($params[pObj]->id)) {
				$recursive = TRUE;
			}
		}


		//erzeugt einen loop, dann den neuen NICHT speichern und Fehlermeldung werfen
		if ($recursive ) {
			$set = false;
			$message = t3lib_div::makeInstance('t3lib_FlashMessage', 'URL "'.$value.'" erzeugt eine Schleife', 'URL nicht geändert!',t3lib_FlashMessage::ERROR);
			t3lib_FlashMessageQueue::addMessage($message);
		}
		return $value;
	}

}

// Eigene realurl Klasse um keine Redirects auszulösen bei Abfrgae einer URL

class myRealUrl extends tx_realurl {

	/**
	 * Parse speaking URL and translate it to parameters understood by TYPO3
	 * Function is called from tslib_fe
	 * The overall format of a speaking URL is these five parts [TYPO3_SITE_URL] / [pre-var] / [page-identification] / [post-vars] / [file.ext]
	 * - "TYPO3_SITE_URL" is fixed value from the environment,
	 * - "pre-var" is any number of segments separated by "/" mapping to GETvars AND with a known lenght,
	 * - "page-identification" identifies the page id in TYPO3 possibly with multiple segments separated by "/" BUT with an UNKNOWN length,
	 * - "post-vars" is sets of segments offering the same features as "pre-var"
	 * - "file.ext" is any filename that might apply
	 *
	 * @param	array		Params for hook
	 * @return	void		Setting internal variables.
	 */
	public function mydecodeSpURL($params) {

		$this->devLog('Entering decodeSpURL');

		// Setting parent object reference (which is $GLOBALS['TSFE'])
		$this->pObj = &$params['pObj'];

		// Initializing config / request URL:
		$this->setConfig();
		$this->adjustConfigurationByHost('decode');
		$this->adjustRootPageId();

		// If there has been a redirect (basically; we arrived here otherwise than via "index.php" in the URL) this can happend either due to a CGI-script or because of reWrite rule. Earlier we used $GLOBALS['HTTP_SERVER_VARS']['REDIRECT_URL'] to check but...
		if ($this->pObj->siteScript && substr($this->pObj->siteScript, 0, 9) != 'index.php' && substr($this->pObj->siteScript, 0, 1) != '?') {

			// Getting the path which is above the current site url:
			// For instance "first/second/third/index.html?&param1=value1&param2=value2"
			// should be the result of the URL
			// "http://localhost/typo3/dev/dummy_1/first/second/third/index.html?&param1=value1&param2=value2"
			// Note: sometimes in fcgi installations it is absolute, so we have to make it
			// relative to work properly.
			$speakingURIpath = $this->pObj->siteScript{0} == '/' ? substr($this->pObj->siteScript, 1) : $this->pObj->siteScript;

			// Call hooks
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['decodeSpURL_preProc'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['decodeSpURL_preProc'] as $userFunc) {
					$hookParams = array(
						'pObj' => &$this,
						'params' => $params,
						'URL' => &$speakingURIpath,
					);
					t3lib_div::callUserFunction($userFunc, $hookParams, $this);
				}
			}

			// Append missing slash if configured for:
			if ($this->extConf['init']['appendMissingSlash']) {
				$regexp = '~^([^\?]*[^/])(\?.*)?$~';
				if (substr($speakingURIpath, -1, 1) == '?') {
					$speakingURIpath = substr($speakingURIpath, 0, -1);
				}
				if (preg_match($regexp, $speakingURIpath)) { // Only process if a slash is missing:
					$options = t3lib_div::trimExplode(',', $this->extConf['init']['appendMissingSlash'], true);
					if (in_array('ifNotFile', $options)) {
						if (!preg_match('/\/[^\/\?]+\.[^\/]+(\?.*)?$/', '/' . $speakingURIpath)) {
							$speakingURIpath = preg_replace($regexp, '\1/\2', $speakingURIpath);
							$this->appendedSlash = true;
						}
					}
					else {
						$speakingURIpath = preg_replace($regexp, '\1/\2', $speakingURIpath);
						$this->appendedSlash = true;
					}
					if ($this->appendedSlash && count($options) > 0) {
						foreach ($options as $option) {
							$matches = array();
							if (preg_match('/^redirect(\[(30[1237])\])?$/', $option, $matches)) {
								$code = count($matches) > 1 ? $matches[2] : 301;
								$status = 'HTTP/1.0 ' . $code . ' TYPO3 RealURL redirect';

								// Check path segment to be relative for the current site.
								// parse_url() does not work with relative URLs, so we use it to test
								/*
								if (!@parse_url($speakingURIpath, PHP_URL_HOST)) {
									@ob_end_clean();
									header($status);
									header('Location: ' . t3lib_div::locationHeaderUrl($speakingURIpath));
									exit;
								}
								 *
								 */
							}
						}
					}
				}
			}

			// If the URL is a single script like "123.1.html" it might be an "old" simulateStaticDocument request. If this is the case and support for this is configured, do NOT try and resolve it as a Speaking URL
			$fI = t3lib_div::split_fileref($speakingURIpath);
			if (!self::testInt($this->pObj->id) && $fI['path'] == '' && $this->extConf['fileName']['defaultToHTMLsuffixOnPrev'] && $this->extConf['init']['respectSimulateStaticURLs']) {
				// If page ID does not exist yet and page is on the root level and both
				// respectSimulateStaticURLs and defaultToHTMLsuffixOnPrev are set, than
				// ignore respectSimulateStaticURLs and attempt to resolve page id.
				// See http://bugs.typo3.org/view.php?id=1530
				$GLOBALS['TT']->setTSlogMessage('decodeSpURL: ignoring respectSimulateStaticURLs due defaultToHTMLsuffixOnPrev for the root level page!)', 2);
				$this->extConf['init']['respectSimulateStaticURLs'] = false;
			}
			if (!$this->extConf['init']['respectSimulateStaticURLs'] || $fI['path']) {
				$this->devLog('RealURL powered decoding (TM) starting!');

				// Parse path:
				$uParts = @parse_url($speakingURIpath);
				if (!is_array($uParts)) {
					//$this->decodeSpURL_throw404('Current URL is invalid');
					return;
				}
				$speakingURIpath = $this->speakingURIpath_procValue = $uParts['path'];

				// Redirecting if needed (exits if so).
				$this->decodeSpURL_checkRedirects($speakingURIpath);

				// Looking for cached information:
				$cachedInfo = $this->decodeSpURL_decodeCache($speakingURIpath);

				// If no cached info was found, create it:
				if (!is_array($cachedInfo)) {
					// Decode URL:
					$cachedInfo = $this->mydecodeSpURL_doDecode($speakingURIpath, $this->extConf['init']['enableCHashCache']);

					// Storing cached information:
					//$this->decodeSpURL_decodeCache($speakingURIpath, $cachedInfo);
				}

				// Re-create QUERY_STRING from Get vars for use with typoLink()
				$_SERVER['QUERY_STRING'] = $this->decodeSpURL_createQueryString($cachedInfo['GET_VARS']);

				// Jump-admin if configured:
				$this->decodeSpURL_jumpAdmin_goBackend($cachedInfo['id']);

				// Setting info in TSFE:
				$this->pObj->mergingWithGetVars($cachedInfo['GET_VARS']);
				$this->pObj->id = $cachedInfo['id'];

				if ($this->mimeType) {
					header('Content-type: ' . $this->mimeType);
					$this->mimeType = null;
				}
			}
		}
	}

	/**
	 * Decodes a speaking URL path into an array of GET parameters and a page id.
	 *
	 * @param	string		Speaking URL path (after the "root" path of the website!) but without query parameters
	 * @param	boolean		If cHash caching is enabled or not.
	 * @return	array		Array with id and GET parameters.
	 * @see decodeSpURL()
	 */
	protected function mydecodeSpURL_doDecode($speakingURIpath, $cHashCache = FALSE) {

		// Cached info:
		$cachedInfo = array();

		// Convert URL to segments
		$pathParts = explode('/', $speakingURIpath);
		array_walk($pathParts, create_function('&$value', '$value = urldecode($value);'));

		// Strip/process file name or extension first
		$file_GET_VARS = $this->mydecodeSpURL_decodeFileName($pathParts);

		// Setting original dir-parts:
		$this->dirParts = $pathParts;

		// Setting "preVars":
		$pre_GET_VARS = $this->decodeSpURL_settingPreVars($pathParts, $this->extConf['preVars']);
		if (isset($this->extConf['pagePath']['languageGetVar'])) {
			$languageGetVar = $this->extConf['pagePath']['languageGetVar'];
			if (isset($pre_GET_VARS[$languageGetVar]) && self::testInt($pre_GET_VARS[$languageGetVar])) {
				// Language from URL
				$this->detectedLanguage = $pre_GET_VARS[$languageGetVar];
			}
			elseif (isset($_GET[$languageGetVar]) && self::testInt($_GET[$languageGetVar])) {
				// This is for _DOMAINS feature
				$this->detectedLanguage = $_GET[$languageGetVar];
			}
		}

		// Setting page id:
		list($cachedInfo['id'], $id_GET_VARS, $cachedInfo['rootpage_id']) = $this->decodeSpURL_idFromPath($pathParts);

		// Fixed Post-vars:
		$fixedPostVarSetCfg = $this->getPostVarSetConfig($cachedInfo['id'], 'fixedPostVars');
		$fixedPost_GET_VARS = $this->decodeSpURL_settingPreVars($pathParts, $fixedPostVarSetCfg);

		// Setting "postVarSets":
		$postVarSetCfg = $this->getPostVarSetConfig($cachedInfo['id']);
		$post_GET_VARS = $this->decodeSpURL_settingPostVarSets($pathParts, $postVarSetCfg, $cachedInfo['id']);

		// Looking for remaining parts:
		if (count($pathParts)) {
			//$this->decodeSpURL_throw404('"' . $speakingURIpath . '" could not be found, closest page matching is ' . substr(implode('/', $this->dirParts), 0, -strlen(implode('/', $pathParts))) . '');
			return;
		}

		// Merge Get vars together:
		$cachedInfo['GET_VARS'] = array();
		if (is_array($pre_GET_VARS))
			$cachedInfo['GET_VARS'] = t3lib_div::array_merge_recursive_overrule($cachedInfo['GET_VARS'], $pre_GET_VARS);
		if (is_array($id_GET_VARS))
			$cachedInfo['GET_VARS'] = t3lib_div::array_merge_recursive_overrule($cachedInfo['GET_VARS'], $id_GET_VARS);
		if (is_array($fixedPost_GET_VARS))
			$cachedInfo['GET_VARS'] = t3lib_div::array_merge_recursive_overrule($cachedInfo['GET_VARS'], $fixedPost_GET_VARS);
		if (is_array($post_GET_VARS))
			$cachedInfo['GET_VARS'] = t3lib_div::array_merge_recursive_overrule($cachedInfo['GET_VARS'], $post_GET_VARS);
		if (is_array($file_GET_VARS))
			$cachedInfo['GET_VARS'] = t3lib_div::array_merge_recursive_overrule($cachedInfo['GET_VARS'], $file_GET_VARS);

		// cHash handling:
		if ($cHashCache && count($cachedInfo['GET_VARS']) > 0) {
			$cHash_value = $this->decodeSpURL_cHashCache($speakingURIpath);
			if ($cHash_value) {
				$cachedInfo['GET_VARS']['cHash'] = $cHash_value;
			}
		}

		// Return information found:
		return $cachedInfo;
	}

	/**
	 * Decodes the file name and adjusts file parts accordingly
	 *
	 * @param array $pathParts Path parts of the URLs (can be modified)
	 * @return array GET varaibles from the file name or empty array
	 */
	protected function mydecodeSpURL_decodeFileName(array &$pathParts) {
		$getVars = array();
		$fileName = array_pop($pathParts);
		$fileParts = t3lib_div::revExplode('.', $fileName, 2);
		if (count($fileParts) == 2 && !$fileParts[1]) {
			//$this->decodeSpURL_throw404('File "' . $fileName . '" was not found (2)!');
		}
		list($segment, $extension) = $fileParts;
		if ($extension) {
			$getVars = array();
			if (!$this->decodeSpURL_decodeFileName_lookupInIndex($fileName, $segment, $extension, $pathParts, $getVars)) {
				if (!$this->mydecodeSpURL_decodeFileName_checkHtmlSuffix($fileName, $segment, $extension, $pathParts)) {
					//$this->decodeSpURL_throw404('File "' . $fileName . '" was not found (1)!');
					return;
				}
			}
		}
		elseif ($fileName != '') {
			$pathParts[] = $fileName;
		}
		return $getVars;
	}

	/**
	 * Checks if the suffix matches to the configured one.
	 *
	 * @param string $fileName
	 * @param string $segment
	 * @param string $extension
	 * @param array $pathPartsCopy
	 * @see tx_realurl::decodeSpURL_decodeFileName()
	 */
	protected function mydecodeSpURL_decodeFileName_checkHtmlSuffix($fileName, $segment, $extension, array &$pathParts) {
		$handled = false;
		if (isset($this->extConf['fileName']['defaultToHTMLsuffixOnPrev']) && $this->extConf['fileName']['defaultToHTMLsuffixOnPrev']) {
			$suffix = $this->extConf['fileName']['defaultToHTMLsuffixOnPrev'];
			$suffix = (!$this->isString($suffix, 'defaultToHTMLsuffixOnPrev') ? '.html' : $suffix);
			if ($suffix == '.' . $extension) {
				$pathParts[] = $segment;
				$this->filePart = '.' . $extension;
				$handled = true;
			}
		}
		if (!$handled && isset($this->extConf['fileName']['acceptHTMLsuffix']) && $this->extConf['fileName']['acceptHTMLsuffix']) {
			$suffix = $this->extConf['fileName']['acceptHTMLsuffix'];
			$suffix = (!$this->isString($suffix, 'acceptHTMLsuffix') ? '.html' : $suffix);
			if (substr($fileName, -strlen($suffix)) == $suffix) {
				$pathParts[] = $segment;
				$this->filePart = $suffix;
				$handled = true;
			}
		}
		if (!$handled) {
			//$this->decodeSpURL_throw404('File "' . $fileName . '" was not found (2)!');
			return;
		}
		return $handled;
	}


}




?>