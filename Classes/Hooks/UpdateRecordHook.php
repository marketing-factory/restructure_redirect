<?php
namespace Mfc\RestructureRedirect\Hooks;

use Mfc\RestructureRedirect\Utility\LinkCreator;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class UpdateRecordHook
{

    /**
     * hook_processDatamap_afterDatabaseOperations using the hook_processDatamap_afterDatabaseOperations hook
     * on moving a page in the pagetree redirects will be added automatically
     *
     * @param string $status
     * @param string $table
     * @param string $id
     * @param string $fieldArray
     *
     * @return void
     */
    public function processDatamap_postProcessFieldArray($status, $table, $id, $fieldArray, $pObj)
    {
        if ($status == 'update' && isset($fieldArray['title'])) {
            if ($table == 'pages' || $table == 'pages_language_overlay') {
                if ($table == 'pages_language_overlay') {
                    $pid = $this->getParentPageEntry($id);
                } else {
                    $pid = $id;
                }

                /** @var LinkCreator $linkCreator */
                $linkCreator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Mfc\\RestructureRedirect\\Utility\\LinkCreator', $pid);

                $parent = $this->getPidFromPageID($id);
                $uid = $id;
                $language = (int) $pObj->datamap[$table][$id]['sys_language_uid'];
                if ($language) {
                    $uid = $this->getParentPageEntry($id);
                    $linkCreator->setParent(0);
                }
                $this->createRedirectsForSourceWithLanguage($uid, $parent, $linkCreator, $language);
            }
        }
    }
    /**
     * hook_processDatamap_afterDatabaseOperations using the hook_processDatamap_afterDatabaseOperations hook
     * on moving a page in the pagetree redirects will be added automatically
     *
     * @param string $status
     * @param string $table
     * @param string $id
     * @param string $fieldArray
     *
     * @return void
     */
    public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $pObj)
    {
        if ($status == 'update' && isset($fieldArray['title'])) {
            if ($table == 'pages') {
                /** @var LinkCreator $linkCreator */
                $linkCreator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Mfc\\RestructureRedirect\\Utility\\LinkCreator', $id);
                if ($linkCreator->removeRedirect()) {
                    $parent = $this->getPidFromPageID($id);
                    $this->removeRedirectsForTarget($id, $parent, $linkCreator);
                }
            }
        }
    }

    /**
     * getLangParams
     * getting the url parameters for differents languagges of the page
     *
     * @param integer $pid
     *
     * @return array
     */
    private function getLangParams($pid)
    {
        $paramArr = array();
        $paramArr[$pid] = 0;
        $table = 'pages_language_overlay';
        $where = 'pid = ' . $pid . BackendUtility::BEenableFields($table);
        $res = $this->getDatabaseConnection()->exec_SELECTQuery('uid,sys_language_uid', $table, $where);
        while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            $paramArr[$row['uid']] = $row['sys_language_uid'];
        }

        return $paramArr;
    }

    /**
     * getSubpages

     *
*@param string $uid
     * @param array $subPages
     *
*@return array
     */
    private function getSubpages($uid, $subPages = array())
    {
        $table = 'pages';
        $enableFields = BackendUtility::BEenableFields($table);
        $where = 'pages.deleted = 0 AND pid = ' . $uid;
        $res = $this->getDatabaseConnection()->exec_SELECTQuery('*', $table, $where . $enableFields);
        while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            $subPages[] = $row;
            $subPages = $this->getSubpages($row['uid'], $subPages);
        }

        return $subPages;
    }

    /**
     * getPidFromPageID
     *
     * @param string $uid
     *
     * @return integer
     */
    private function getPidFromPageID($uid)
    {
        $table = 'pages';
        $enableFields = BackendUtility::BEenableFields($table);
        $where = 'uid = ' . $uid;
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('pid', $table, $where . $enableFields);
        if (is_array($row) && !empty($row)) {
            return $row['pid'];
        }

        return 0;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @param $id
     * @param $source
     * @param LinkCreator $linkCreator
     */
    protected function createRedirectsForSource($id, $source, $linkCreator)
    {
        $subPages = $this->getSubpages($id);
        $subPages[$id] = array('pid' => $source, 'uid' => $id);
        foreach ($subPages as $subPage) {
            $paramArr = $this->getLangParams($subPage['uid']);
            foreach ($paramArr as $id => $sysLanguageUid) {
                $params = array('L' => $sysLanguageUid);
                $link = $linkCreator->getLink($subPage['uid'], $params);
                if (!isset($linkCreator->settings['useLangParam'])
                    || !$linkCreator->settings['useLangParam']
                ) {
                    $link = $linkCreator->excludeLanguageParamFromUrl($link);
                }
                if ($link != '') {
                    $linkCreator->createRedirectEntry($subPage['uid'], $link, $sysLanguageUid);
                }
            }
        }
    }

    /**
     * @param $id
     * @param $source
     * @param LinkCreator $linkCreator
     * @param int $language
     */
    protected function createRedirectsForSourceWithLanguage($id, $source, $linkCreator, $language)
    {
        $subPages = $this->getSubpages($id);
        $subPages[$id] = array('pid' => $source, 'uid' => $id);
        foreach ($subPages as $subPage) {
            $params = array('L' => $language);
            $link = $linkCreator->getLink($subPage['uid'], $params);
            if (!isset($linkCreator->settings['useLangParam'])
                || !$linkCreator->settings['useLangParam']
            ) {
                $link = $linkCreator->excludeLanguageParamFromUrl($link);
            }
            if ($link != '') {
                $linkCreator->createRedirectEntry($subPage['uid'], $link, $language);
            }
        }
    }
    /**
     * @param $id
     * @param $target
     * @param LinkCreator $linkCreator
     */
    protected function removeRedirectsForTarget($id, $target, $linkCreator)
    {
        $subPages = $this->getSubpages($id);
        $subPages[$id] = array('pid' => $target, 'uid' => $id);
        foreach ($subPages as $subPage) {
            $paramArr = $this->getLangParams($subPage['uid']);
            foreach ($paramArr as $id => $sysLanguageUid) {
                $params = array('L' => $sysLanguageUid);
                $link = $linkCreator->getLink($subPage['uid'], $params);
                if (!isset($linkCreator->settings['useLangParam'])
                    || !$linkCreator->settings['useLangParam']
                ) {
                    $link = $linkCreator->excludeLanguageParamFromUrl($link);
                }
                if ($link != '') {
                    $linkCreator->removeRedirectEntry($subPage['uid'], $link, $sysLanguageUid);
                }
            }
        }
    }

    protected function getParentPageEntry($id)
    {
        $table = 'pages_language_overlay';
        $pid = 0;
        $where = 'uid = ' . $id . BackendUtility::BEenableFields($table);
        $res = $this->getDatabaseConnection()->exec_SELECTQuery('pid', $table, $where);
        while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            $pid = $row['pid'];
        }

        return $pid;
    }
}
