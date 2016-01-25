<?php
namespace MFC\RestructureRedirect\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LinkCreator
 */
class LinkCreator
{
    protected $localTSFE;

    public $settings;

    protected $parent;

    public function __construct($uid)
    {
        $rootline = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($uid);
        $rootpid = 1;

        $this->settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['restructure_redirect']);
        foreach ($rootline as $line) {
            if ($line['uid'] > 0) {
                $rootpid = $line['uid'];
            }
            if ($line['is_siteroot'] == 1) {
                break;
            }
        }

        if (!is_object($GLOBALS['TT'])) {
            $GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\TimeTracker;
            $GLOBALS['TT']->start();
        }
        // Create the TSFE class.
        /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController localTSFE */
        $this->localTSFE = GeneralUtility::makeInstance(
            \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class,
            $GLOBALS['TYPO3_CONF_VARS'],
            $rootpid,
            0,
            1,
            '',
            '',
            '',
            ''
        );
        $GLOBALS['TSFE'] = $this->localTSFE;
        $this->localTSFE->connectToDB();
        $this->localTSFE->initFEuser();
        $this->localTSFE->fetch_the_id();
        $this->localTSFE->getPageAndRootline();
        $this->localTSFE->initTemplate();
        $this->localTSFE->forceTemplateParsing = 1;
        $this->localTSFE->getConfigArray();
    }

    /**
     * getLink
     *
     * @param integer $uid siteid
     * @param array $urlParameters
     *
     * @return string
     */
    public function getLink($uid, $urlParameters = array())
    {
        /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj */
        $cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $cObj->start(array(), '');
        $linkURL = $cObj->getTypoLink_URL($uid, $urlParameters);

        return $linkURL;
    }

    /**
     * createRedirectEntry
     *
     * @param integer $pid
     * @param string $link
     * @param int $sysLanguageUid
     *
     * @return void
     */
    public function createRedirectEntry($pid, $link, $sysLanguageUid)
    {
        $table = "tx_restructureredirect_redirects";
        if (!$sysLanguageUid) {
            $this->parent = 0;
        }
        if (!$this->linkExists($link, $sysLanguageUid)) {
            $field_values = array(
                'url' => $link,
                'pid' => $pid,
                'rootpage' => $this->getRootlinePage($pid),
                'sys_language_uid' => $sysLanguageUid,
                'l10n_parent' => $this->parent,
                'tstamp' => time(),
                'crdate' => time(),
                'expire' => mktime(date('H'), date('i'), 0, date('m') + $this->settings['expireRange'], date('d'),
                    date('Y')),
            );
            $this->getDatabaseConnection()->exec_INSERTquery($table, $field_values);
            if (!$sysLanguageUid) {
                $this->linkExists($link, $sysLanguageUid);
            }
        }
    }

    /**
     * linkExists
     *
     * @param string $link
     * @param integer $sysLanguageUid
     *
     * @return boolean
     */
    protected function linkExists($link, $sysLanguageUid)
    {
        $table = 'tx_restructureredirect_redirects';
        $enableFields = BackendUtility::BEenableFields($table);
        $where = 'sys_language_uid = ' . $sysLanguageUid . ' AND url = ' .
            $this->getDatabaseConnection()->fullQuoteStr($link, $table);
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', $table, $where . $enableFields);
        if ($row) {
            if ($row['sys_language_uid'] == 0) {
                $this->parent = $row['uid'];
            }

            return true;
        }

        return false;
    }

    /**
     * Exclude language parameter from url
     *
     * @param string $link
     *
     * @return string
     */
    public function excludeLanguageParamFromUrl($link)
    {

        $excludeParams = GeneralUtility::explodeUrl2Array('L', true);
        $parts = parse_url($link);
        parse_str($parts['query'], $queryParts);
        $parts['query'] = $queryParts;
        $parts['query'] = GeneralUtility::arrayDiffAssocRecursive($parts['query'], $excludeParams);

        $link = $parts['path'];

        if ($parts['query'] != array()) {
            foreach ($parts['query'] as $key => $value) {
                $parts['query'][$key] = $key . '=' . $value;
            }
            $link .= '?' . implode('&', $parts['qeury']);
        }

        return $link;
    }

    protected function getRootlinePage($uid)
    {
        if ($uid == $GLOBALS['TSFE']->id) {
            $rootPage = $GLOBALS['TSFE']->rootLine[0]['uid'];
        } else {
            $rootPage = $this->getRecursivePid($uid);
        }

        return $rootPage;
    }

    protected function getRecursivePid($uid)
    {
        $result = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid, pid', 'pages', 'uid =' . $uid);

        $rootPage = 0;
        if ($result['pid'] > 0) {
            $rootPage = $this->getRecursivePid($result['pid']);
        } elseif ($result['pid'] == 0) {
            $rootPage = $uid;
        }

        return $rootPage;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
