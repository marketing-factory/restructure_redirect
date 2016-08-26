<?php
namespace MFC\RestructureRedirect\Hooks;

use MFC\RestructureRedirect\Utility\LinkCreator;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class tx_realurl_hooksHandler
 */
class HooksHandlerHook
{
    /**
     * user_decodeSpURL_preProc
     * hook for realurl to redirect if neccessary
     *
     * @param array $hookParams
     *
     * @return void
     */
    public function user_decodeSpURL_preProc($hookParams)
    {
        if (TYPO3_MODE == 'BE') {
            return;
        }

        $requestDomain = $GLOBALS['_ENV']['HTTP_HOST'] ?: $_SERVER['HTTP_HOST'];
        $domainData = $this->getRootPageAndLanguageForRequestDomain($requestDomain);
        if (!$domainData) {
            return;
        }

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

        $url = pathinfo($hookParams['URL'], PATHINFO_DIRNAME) . '/' . pathinfo($hookParams['URL'], PATHINFO_FILENAME);
        $url = $this->getDatabaseConnection()->quoteStr($url, $table);
        $where = '(url LIKE "' . $url . '%" OR url LIKE "/' . $url . '%") AND (expire = 0 OR  expire > ' . time() . ')
            AND sys_language_uid = ' . $domainData['sys_language_uid'] . ' AND rootpage = ' . $domainData['pid'];

        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', $table, $where . $enableFields);
        if ($row['url'] && strlen($row['url']) > 0) {
            $redirectId = $row['pid'];
            $params = $this->getUrlParams($hookParams['URL']);
            unset($params['id']);
            $params['L'] = $domainData['sys_language_uid'];

            /** @var LinkCreator $linkCreator */
            $linkCreator = GeneralUtility::makeInstance(LinkCreator::class, $redirectId);
            $redirectUrl = ltrim($linkCreator->getLink($redirectId, $params), '/');
            if (!isset($linkCreator->settings['useLangParam']) || !$linkCreator->settings['useLangParam']) {
                $redirectUrl = ltrim($linkCreator->excludeLanguageParamFromUrl($redirectUrl), '/');
            }
            if ($redirectUrl == $hookParams['URL']) {
                $this->sendErrorMail($redirectUrl, $row);

                return;
            }

            if ($requestDomain && isset($linkCreator->settings['useRequestDomain'])
                && $linkCreator->settings['useRequestDomain']
            ) {
                $domain = $domainData['redirectTo'] ?: $requestDomain;
                $domain = 'http://' . ltrim(rtrim($domain, '/') . '/', 'http://');
            } elseif (isset($GLOBALS['TSFE']->config['config']['baseURL'])
                && $GLOBALS['TSFE']->config['config']['baseURL'] != ''
            ) {
                $domain = rtrim($GLOBALS['TSFE']->config['config']['baseURL'], '/') . '/';
            } else {
                $domain = rtrim(BackendUtility::getViewDomain($redirectId), '/') . '/';
            }

            $redirectUrl = $domain . $redirectUrl;
            \TYPO3\CMS\Core\Utility\HttpUtility::redirect(
                GeneralUtility::locationHeaderUrl($redirectUrl),
                \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_301
            );
        }
    }

    /**
     * getUrlParams
     * getparams from realurl
     *
     * @param string $url
     *
     * @return array
     */
    private function getUrlParams($url)
    {
        $where = 'content = "' . $url . '"';

        $res = $this->getDatabaseConnection()->exec_SELECTQuery('*', 'tx_realurl_urlencodecache', $where, '', '', 1);
        if ($res) {
            $row = $this->getDatabaseConnection()->sql_fetch_assoc($res);
            $origParams = GeneralUtility::trimExplode('|', $row['origparams']);
            if ($origParams[1]) {
                $params = GeneralUtility::explodeUrl2Array($origParams[1]);

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
     *
     * @return void
     */
    private function sendErrorMail($foundUrl, $row)
    {
        $subject = 'problems on host ' . $GLOBALS['_ENV']['HTTP_HOST'] . ' with url ' . $foundUrl;

        mail(
            'kontroll.heimwerker@marketing-factory.de',
            $subject,
            'Please test the url ' . $GLOBALS['TSFE']->config['config']['baseURL'] . $foundUrl . ' on page with id '
            . $row['pid'] . ' for host ' . $GLOBALS['_ENV']['HTTP_HOST']
        );
    }

    /**
     * @param string $domain
     *
     * @return array
     */
    protected function getRootPageAndLanguageForRequestDomain($domain)
    {
        return $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'sys_language_uid, pid, redirectTo',
            'sys_domain',
            'domainName = ' . $this->getDatabaseConnection()->fullQuoteStr($domain, 'sys_domain')
            . BackendUtility::BEenableFields('sys_domain')
        );
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
