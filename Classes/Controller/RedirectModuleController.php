<?php
namespace MFC\RestructureRedirect\Controller;

/***************************************************************
 *  Copyright notice
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;

/**
 * Main script class
 *
 * @package restructure_redirect
 */
class RedirectModuleController extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    /**
     * @var array
     */
    public $MCONF = array();

    /**
     * @var array
     */
    public $MOD_MENU = array();

    /**
     * @var array
     */
    public $MOD_SETTINGS = array();

    /**
     * @var array
     */
    public $pageinfo = array();

    /**
     * document template object
     *
     * @var DocumentTemplate
     */
    public $doc;

    /**
     * @var string
     */
    public $content;

    /**
     * @var string
     */
    public $moduleName = 'tools_redirect';

    /**
     * @var ModuleTemplate
     */
    public $moduleTemplate;

    /**
     * @var IconFactory
     */
    public $iconFactory;

    /**
     * Max length of strings
     *
     * @var int
     */
    public $fixedL = 30;

    /**
     * @var array
     */
    protected $referenceCount = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->iconFactory = $this->moduleTemplate->getIconFactory();
        $this->getLanguageService()->includeLLFile('EXT:restructure_redirect/mod/locallang.xlf');

        $this->MCONF = array(
            'name' => $this->moduleName
        );
        $this->moduleTemplate->addJavaScriptCode(
            'jumpToUrl',
            '
            script_ended = 0;
            function jumpToUrl(URL) {
                window.location.href = URL;
                return false;
            }
            '
        );
    }

    /**
     * Basic initialization of the class
     *
     * @return    void
     */
    public function init()
    {
        parent::init();

        // **************************
        // Initializing
        // **************************
        /** @var \TYPO3\CMS\Backend\Template\DocumentTemplate::class doc */
        $this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
        $this->doc->setModuleTemplate('EXT:restructure_redirect/mod/redirects.html');

        $this->doc->form = '<form action="" method="post">';
    }

    /**
     * Initialization of the module menu configuration
     *
     * @return    void
     */
    public function menuConfig()
    {
        // MENU-ITEMS:
        // If array, then it's a selector box menu
        // If empty string it's just a variable, that'll be saved.
        // Values NOT in this array will not be saved in the settings-array for the module.
        $this->MOD_MENU = array(
            'function' => array(
                'listRedirects' => $this->getLanguageService()->getLL('listRedirects', true),
            ),
        );
        // CLEAN SETTINGS
        $this->MOD_SETTINGS = BackendUtility::getModuleData(
            $this->MOD_MENU,
            GeneralUtility::_GP('SET'),
            $this->MCONF['name'],
            'ses'
        );
    }

    /**
     * This functions builds the content of the page
     *
     * @return    void
     */
    public function main()
    {
        switch ($this->MOD_SETTINGS['function']) {
            case 'listRedirects':
                $this->content .= $this->listRedirects();
                break;
        }
        // Setting up the buttons and markers for docheader
        $this->getButtons();
    }

    /**
     * Injects the request object for the current request or subrequest
     * Then checks for module functions that have hooked in, and renders menu etc.
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();

        // Checking for first level external objects
        $this->checkExtObj();

        $this->main();

        $this->moduleTemplate->setContent($this->content);
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @return void
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($this->MCONF['name'])
            ->setGetVariables(['id', 'M']);
        $buttonBar->addButton($shortcutButton);
    }


    /***************************
     * OTHER FUNCTIONS:
     ***************************/

    /**
     * Builds a list of all links for a specific element (here: BE user) and returns it for print.
     *
     * @param string $table the db table that should be used
     * @param array $row the BE user record to use
     *
     * @return string a HTML formatted list of the link
     */
    protected function elementLinks($table, $row)
    {
        // "Info": (All records)
        $onClick = 'top.launchView(' . GeneralUtility::quoteJSvalue($table) . ', ' . (int)$row['uid'] .
            '); return false;';
        $cells[] = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '"
            title="' . $this->getLanguageService()->getLL('LLL:EXT:lang/locallang_common.xml:showInfo', true) . '">'
            . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '</a>';

        // Edit:
        $params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
        $iconIdentifier = 'actions-open';
        $cells[] = '<a class="btn btn-default" href="#" onclick="'
            . htmlspecialchars(BackendUtility::editOnClick($params, '', -1))
            . '" title="' . $this->getLanguageService()->getLL('LLL:EXT:lang/locallang_common.xml:edit', true) . '">'
            . $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render() . '</a>';

        // Hide:
        $hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
        if ($row[$hiddenField]) {
            $params = '&data[' . $table . '][' . $row['uid'] . '][' . $hiddenField . ']=0';
            $icon = 'unhide';
        } else {
            $params = '&data[' . $table . '][' . $row['uid'] . '][' . $hiddenField . ']=1';
            $icon = 'hide';
        }
        $hideTitle = $this->getLanguageService()->getLL('hide', true);
        $unhideTitle = $this->getLanguageService()->getLL('unHide', true);
        $cells[] = '<a class="btn btn-default t3js-record-hide" data-state="hidden" href="#"'
            . ' data-params="' . htmlspecialchars($params) . '"'
            . ' title="' . $unhideTitle . '"'
            . ' data-toggle-title="' . $hideTitle . '">'
            . $this->iconFactory->getIcon('actions-edit-' . $icon, Icon::SIZE_SMALL)->render() . '</a>';

        // Delete
        $refCountMsg = BackendUtility::referenceCount(
            $table,
            $row['uid'],
            ' ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.referencesToRecord'),
            $this->getReferenceCount($table, $row['uid'])) . BackendUtility::translationCount($table, $row['uid'],
            ' ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.translationsOfRecord')
        );

        $actionName = 'delete';
        $titleOrig = BackendUtility::getRecordTitle($table, $row, false, true);
        $title = GeneralUtility::slashJS(GeneralUtility::fixed_lgd_cs($titleOrig, $this->fixedL), true);
        $warningText = $this->getLanguageService()->getLL($actionName . 'Warning') . ' "' . $title . '" ' . '['
            . $table . ':' . $row['uid'] . ']' . $refCountMsg;

        $params = 'cmd[' . $table . '][' . $row['uid'] . '][delete]=1';
        $icon = $this->iconFactory->getIcon('actions-edit-' . $actionName, Icon::SIZE_SMALL)->render();
        $linkTitle = $this->getLanguageService()->getLL($actionName, true);
        $cells[] = '<a class="btn btn-default t3js-record-delete" href="#" '
            . ' data-l10parent="' . htmlspecialchars($row['l10n_parent']) . '"'
            . ' data-params="' . htmlspecialchars($params) . '" data-title="' . htmlspecialchars($titleOrig) . '"'
            . ' data-message="' . htmlspecialchars($warningText) . '" title="' . $linkTitle . '"'
            . '>' . $icon . '</a>';

        return implode('', $cells);
    }

    /**
     * Gets the number of records referencing the record with the UID $uid in
     * the table $tableName.
     *
     * @param string $tableName
     * @param int $uid
     * @return int The number of references to record $uid in table
     */
    protected function getReferenceCount($tableName, $uid)
    {
        $db = $this->getDatabaseConnection();
        if (!isset($this->referenceCount[$tableName][$uid])) {
            $where = 'ref_table = ' . $db->fullQuoteStr($tableName, 'sys_refindex')
                . ' AND ref_uid = ' . $uid . ' AND deleted = 0';
            $numberOfReferences = $db->exec_SELECTcountRows('*', 'sys_refindex', $where);
            $this->referenceCount[$tableName][$uid] = $numberOfReferences;
        }
        return $this->referenceCount[$tableName][$uid];
    }

    /**
     * Returns the local path for this string (removes the PATH_site if it is included)
     *
     * @param string $str the path that will be checked
     *
     * @return string the local path
     */
    protected function localPath($str)
    {
        if (substr($str, 0, strlen(PATH_site)) == PATH_site) {
            return substr($str, strlen(PATH_site));
        } else {
            return $str;
        }
    }

    /***************************
     * "List Redirects" FUNCTIONS:
     ***************************/

    /**
     * @author
     */
    protected function listRedirects()
    {
        $select_fields = '*';
        $from_table = 'tx_restructureredirect_redirects';
        $where_clause = 'deleted = 0 and hidden = 0';
        $orderBy = 'url';

        // Fetch active sessions of other users from storage:
        $redirects = $this->getDatabaseConnection()->exec_SELECTgetRows(
            $select_fields,
            $from_table,
            $where_clause,
            '',
            $orderBy
        );
        // Process and visualized each active session as a table row:
        $outTable = '';
        if (is_array($redirects)) {
            foreach ($redirects as $redirect) {
                $outTable .= '
                    <tr class="bgColor4" height="17" valign="top">' . '<td nowrap="nowrap" valign="top">&nbsp;'
                    . htmlspecialchars($redirect['url']) . '</td>' . '<td nowrap="nowrap">'
                    . date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' '
                        . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $redirect['crdate']) . '</td>'
                    . '<td nowrap="nowrap">';
                if ($redirect['expire'] > 0) {
                    $outTable .= date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' '
                        . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $redirect['expire']);
                } else {
                    $outTable .= $this->getLanguageService()->getLL('never', true);
                }
                $outTable .= '</td>' . '<td nowrap="nowrap">' . $redirect['pid'] . '&nbsp;&nbsp;</td>'
                    . '<td nowrap="nowrap">' . $this->elementLinks('tx_restructureredirect_redirects', $redirect)
                    . '</td>' .

                    '</tr>';
            }
        }
        // Wrap <table> tag around the rows:
        $outTable = '
		<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">
			<tr class="t3-row-header">
				<td>' . $this->getLanguageService()->getLL('url', true) . '</td>
				<td>' . $this->getLanguageService()->getLL('created', true) . '</td>
				<td >' . $this->getLanguageService()->getLL('expires', true) . '</td>
				<td colspan="2">' . $this->getLanguageService()->getLL('siteid', true) . '</td>
			</tr>' . $outTable . '
		</table>';

        $content = $this->doc->section($this->getLanguageService()->getLL('listRedirects', true), $outTable, 0, 1);

        return $content;
    }
}
