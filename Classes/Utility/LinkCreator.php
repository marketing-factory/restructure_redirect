<?php
namespace Mfc\RestructureRedirect\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class LinkCreator
 */
class LinkCreator
{
    /**
     * @var object|\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected $localTSFE;

    /**
     * @var array
     */
    public $settings;

    /**
     * @var int
     */
    protected $parent;

    public function __construct($uid)
    {
        $rootline = BackendUtility::BEgetRootLine($uid);
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
        /** @var TypoScriptFrontendController localTSFE */
        $this->localTSFE = GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
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
        /** @var ContentObjectRenderer $cObj */
        $cObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
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
        if ($this->pageIsActive($pid, $sysLanguageUid)) {
            $table = "tx_restructureredirect_redirects";
            $this->getParentPageId($pid, $link, $sysLanguageUid);
            if (!$this->linkExists($link, $sysLanguageUid)) {
                $field_values = array(
                    'url' => $link,
                    'pid' => $pid,
                    'rootpage' => $this->getRootlinePage($pid),
                    'sys_language_uid' => $sysLanguageUid,
                    'l10n_parent' => $this->parent,
                    'tstamp' => time(),
                    'crdate' => time(),
                    'expire' => mktime(
                        date('H'),
                        date('i'),
                        0,
                        date('m') + $this->settings['expireRange'],
                        date('d'),
                        date('Y')
                    ),
                );
                $this->getDatabaseConnection()->exec_INSERTquery($table, $field_values);
            }
        }
    }

    /**
     * @param int $pid
     * @param string $link
     * @param int $sysLanguageUid
     */
    public function removeRedirectEntry($pid, $link, $sysLanguageUid)
    {
        $table = "tx_restructureredirect_redirects";
        $this->getParentPageId($pid, $link, $sysLanguageUid);
        if ($this->linkExists($link, $sysLanguageUid)) {
            $where = 'pid = ' . $pid . ' AND sys_language_uid =' . $sysLanguageUid .
                ' AND url =' . $this->getDatabaseConnection()->fullQuoteStr($link, $table);
            $this->getDatabaseConnection()->exec_DELETEquery($table, $where);
        }
    }

    protected function getParentPageId($pid, $link, $sysLanguageUid)
    {
        if (!$sysLanguageUid) {
            $this->setParent(0);
        } else {
            if ($this->linkExists($link, $sysLanguageUid)) {
                $this->setParent($pid);
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
            return true;
        }

        return false;
    }

    public function pageIsActive($pageId, $sys_language_uid)
    {
        if ($sys_language_uid) {
            $result = $this->getDatabaseConnection()->exec_SELECTcountRows(
                '*',
                'pages_language_overlay',
                'pid =' . $pageId .' AND sys_language_uid=' . $sys_language_uid .
                BackendUtility::BEenableFields('pages_language_overlay')
            );
        } else {
            $result = $this->getDatabaseConnection()->exec_SELECTcountRows(
                '*',
                'pages',
                'uid =' . $pageId . BackendUtility::BEenableFields('pages')
            );
        }

        return $result;
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
            $link .= '?' . implode('&', $parts['query']);
        }

        return $link;
    }

    /**
     * @param int $parentValue
     */
    public function setParent($parentValue)
    {
        $this->parent = $parentValue;
    }

    /**
     * @param int $uid
     * @return int
     */
    protected function getRootlinePage($uid)
    {
        if ($uid == $GLOBALS['TSFE']->id) {
            $rootPage = $GLOBALS['TSFE']->rootLine[0]['uid'];
        } else {
            $rootPage = $this->getRecursivePid($uid);
        }

        return $rootPage;
    }

    /**
     * @param int $uid
     * @return int
     */
    protected function getRecursivePid($uid)
    {
        $result = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid, pid, is_siteroot',
            'pages',
            'uid =' . $uid
        );

        $rootPage = 0;
        if ($result['pid'] > 0 && $result['is_siteroot'] == 0) {
            $rootPage = $this->getRecursivePid($result['pid']);
        } elseif ($result['pid'] == 0 || $result['is_siteroot'] > 0) {
            $rootPage = $uid;
        }

        return $rootPage;
    }

    public function removeRedirect()
    {
        return (bool) $this->settings['deleteRedirect'];
    }

    /**
     * Deletes the entire cache
     *
     * @param int $pageId
     * @return void
     */
    public function clearPageCache($pageId)
    {
        /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce */
        $tce = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
        $tce->admin = 1;
        $tce->clear_cacheCmd($pageId);
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
