<?php
namespace Mfc\RestructureRedirect\Hooks;

use Mfc\RestructureRedirect\Utility\LinkCreator;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class MoveRecordHook
{
    /**
     * processCmdmap_preProcess using the processCmdmap_preProcess hook
     * on moving a page in the pagetree redirects will be added automatically
     *
     * @param string $command
     * @param string $table
     * @param string $id
     * @param string $value
     *
     * @return void
     */
    public function processCmdmap_preProcess($command, $table, $id, $value)
    {
        if ($command == 'move') {
            if ($table == 'pages') {
                /** @var LinkCreator $linkCreator */
                $linkCreator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Mfc\\RestructureRedirect\\Utility\\LinkCreator', $id);
                if ($value < 0) {
                    $target = $this->getPidFromPageID($value * -1);
                } else {
                    $target = $value;
                }
                $source = $this->getPidFromPageID($id);

                if ($source == $target) {
                    return;
                }
                $this->createRedirectsForSource($id, $source, $linkCreator);
            }
        }
    }

    /**
     * processCmdmap_postProcess using the processCmdmap_postProcess hook
     * on moving a page in the pagetree redirects will be added automatically
     *
     * @param string $command
     * @param string $table
     * @param string $id
     * @param string $value
     *
     * @return void
     */
    public function processCmdmap_postProcess($command, $table, $id, $value)
    {
        if ($command == 'move') {
            if ($table == 'pages') {
                /** @var LinkCreator $linkCreator */
                $linkCreator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Mfc\\RestructureRedirect\\Utility\\LinkCreator', $id);
                if ($linkCreator->removeRedirect()) {
                    if ($value < 0) {
                        $target = $this->getPidFromPageID($value * -1);
                    } else {
                        $target = $value;
                    }

                    $this->removeRedirectsForTarget($id, $target, $linkCreator);
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
        $where = 'pages.deleted = 0 and pid = ' . $uid;
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
        $parentRecordId = 0;
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
                    $parentRecordId = $linkCreator->createRedirectEntry($subPage['uid'], $link, $sysLanguageUid);
                    $linkCreator->clearPageCache($subPage['uid']);
                }
                if ($parentRecordId > 0) {
                    $linkCreator->setParent($parentRecordId);
                }
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
}
