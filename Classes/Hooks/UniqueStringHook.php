<?php
namespace Mfc\RestructureRedirect\Hooks;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;

class UniqueStringHook
{
    /**
     * evaluateFieldValue tca eval function for checking on unique entries
     *
     * @param mixed $value
     * @param $is_in
     * @param bool $set
     *
     * @return string
     * @throws \TYPO3\CMS\Core\Exception
     */
    function evaluateFieldValue($value, $is_in, &$set)
    {
        $set = true;

        $datas = $_POST['data']['tx_restructureredirect_redirects'];
        $keys = array_keys($datas);
        $myUid = intval($keys[0]);
        if ($myUid > 0) {
            $result = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'url',
                'tx_restructureredirect_redirects',
                'NOT uid =' . $myUid . ' AND  deleted = 0 AND url = "' . $value . '"'
            );
        } else {
            $result = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'url',
                'tx_restructureredirect_redirects',
                'deleted = 0 and url = "' . $value . '"'
            );
        }

        // existiert schon ein Eintrag, dann den neuen NICHT speichern und Fehlermeldung werfen
        if (count($result) > 0) {
            $set = false;
            $message = GeneralUtility::makeInstance(
                'FlashMessage',
                'URL "' . $value . '" schon vorhanden',
                'URL nicht geändert!',
                FlashMessage::ERROR
            );
            /** @var FlashMessageQueue $messageQueue */
            $messageQueue = GeneralUtility::makeInstance('FlashMessageQueue');
            $messageQueue->enqueue($message);
        }

        return $value;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
