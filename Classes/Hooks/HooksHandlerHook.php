<?php
namespace Mfc\RestructureRedirect\Hooks;

use Mfc\RestructureRedirect\Utility\LinkCreator;
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

        $requestHost = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
        $requestDomain = GeneralUtility::getIndpEnv('HTTP_HOST');
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
        $where = '(url LIKE "' . $url . '.%" OR url LIKE "/' . $url . '.%" OR url = "' . $url . '/" OR url = "/' .
            $url . '/") AND (expire = 0 OR  expire > ' . time() . ')
            AND sys_language_uid = ' . $domainData['sys_language_uid'] . ' AND rootpage = ' . $domainData['pid'];

        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', $table, $where . $enableFields);
        if ($row['url'] && strlen($row['url']) > 0) {
            $redirectId = $row['pid'];
            $params = [
                'L' => $domainData['sys_language_uid'],
            ];

            $this->logRestructureUrlRequest($row['uid'], $row['hits_count']);

            /** @var LinkCreator $linkCreator */
            $linkCreator = GeneralUtility::makeInstance('Mfc\\RestructureRedirect\\Utility\\LinkCreator', $redirectId);
            $redirectUrl = ltrim($linkCreator->getLink($redirectId, $params), '/');
            if (!isset($linkCreator->settings['useLangParam']) || !$linkCreator->settings['useLangParam']) {
                $redirectUrl = ltrim($linkCreator->excludeLanguageParamFromUrl($redirectUrl), '/');
            }
            if ($redirectUrl == $hookParams['URL']) {
                $this->sendErrorMail($redirectUrl, $row, $linkCreator->settings['recipientMailAddress']);
                //deactivate redirect
                $this->getDatabaseConnection()->exec_UPDATEquery(
                    $table,
                    'uid = ' . $row['uid'],
                    array (
                        'hidden' => 1,
                        'tstamp' => time()
                    )
                );
                return;
            }

            if ($requestDomain && isset($linkCreator->settings['useRequestDomain'])
                && $linkCreator->settings['useRequestDomain']
            ) {
                $domain = $domainData['redirectTo'] ?: $requestHost;
                if (strpos($domain, 'https://') !== false) {
                    $domain = 'https://' . ltrim(ltrim(rtrim($domain, '/') . '/', 'https'), '://');
                } elseif (strpos($domain, 'http://') !== false) {
                    $domain = 'http://' . ltrim(ltrim(rtrim($domain, '/') . '/', 'http'), '://');
                } else {
                    $domain = 'http://' . ltrim(rtrim($domain, '/') . '/', '://');
                }
            } elseif (isset($GLOBALS['TSFE']->config['config']['baseURL'])
                && $GLOBALS['TSFE']->config['config']['baseURL'] != ''
            ) {
                $domain = rtrim($GLOBALS['TSFE']->config['config']['baseURL'], '/') . '/';
            } else {
                $domain = rtrim(BackendUtility::getViewDomain($redirectId), '/') . '/';
            }

            if (isset($linkCreator->settings['forceSSLDomain']) && $linkCreator->settings['forceSSLDomain']) {
                $domain = 'https://' . ltrim(ltrim($domain, 'https'), '://');
            }

            $additionalPathSegment = '';
            if (isset($linkCreator->settings['additionalPathSegment']) &&
                !empty($linkCreator->settings['additionalPathSegment'])
            ) {
                $additionalPathSegment = $linkCreator->settings['additionalPathSegment'];
            }

            $redirectUrl = $domain . $additionalPathSegment. $redirectUrl;
            \TYPO3\CMS\Core\Utility\HttpUtility::redirect(
                GeneralUtility::locationHeaderUrl($redirectUrl),
                \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_301
            );
        }
    }

    /**
     * send error mail with url, if redirect doesn't work because
     * url and redirect url are the same
     *
     * @param string $foundUrl
     * @param array $row
     * @param string $mailAdress
     *
     * @return void
     */
    private function sendErrorMail($foundUrl, $row, $mailAdress = '')
    {
        $subject = 'The TYPO3 extension restructure_redirect detects a problem on host ' .
            $GLOBALS['_ENV']['HTTP_HOST'] . ' with url ' . $foundUrl;
        $recipient = 'kontroll.heimwerker@marketing-factory.de';
        if (!empty($mailAdress)) {
            $recipient = $mailAdress;
        }

        mail(
            $recipient,
            $subject,
            'Please test the url ' . $GLOBALS['TSFE']->config['config']['baseURL'] . $foundUrl . ' on page with id '
            . $row['pid'] . ' for host ' . $GLOBALS['_ENV']['HTTP_HOST'] .
            '. The redirect was deactivated to prevend redirect circles.'
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

    protected function logRestructureUrlRequest($redirectEntryUid, $hits)
    {
        $referer = $_SERVER['HTTP_REFERER'];
        $time = time();

        $values = array(
            'last_called' => $time,
            'hits_count' => $hits + 1,
            'last_referer' => 'direct request'
        );

        if (!empty($referer)) {
            $values['last_referer'] = $referer;
        }

        $update = $this->getDatabaseConnection()->exec_UPDATEquery(
            'tx_restructureredirect_redirects',
            'uid = ' . $redirectEntryUid,
            $values
        );

        if ($update) {
            $this->getDatabaseConnection()->exec_INSERTquery(
                'tx_restructureredirect_redirects_log',
                array(
                    'redirectUid' => $redirectEntryUid,
                    'tstamp' => $time,
                    'crdate' => $time,
                    'referer' => $values['last_referer']
                )
            );
        }
    }
}
