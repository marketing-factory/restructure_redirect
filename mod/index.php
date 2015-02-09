<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Backend User Administration Module
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

if (class_exists(t3lib_utility_VersionNumber) && t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) >= 4006000) {
	$GLOBALS['LANG']->includeLLFile('EXT:restructure_redirect/mod/locallang.xlf');
} else {
	$GLOBALS['LANG']->includeLLFile('EXT:restructure_redirect/mod/locallang.xml');
}
$GLOBALS['BE_USER']->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.


/**
 * Main script class
 *
 * @package TYPO3
 */
class SC_mod_tools_redirect {
	var $MCONF=array();
	var $MOD_MENU=array();
	var $MOD_SETTINGS=array();

	/**
	 * document emplate object
	 *
	 * @var noDoc
	 */
	var $doc;

	var $include_once=array();
	var $content;


	/**
	 * Basic initialization of the class
	 *
	 * @return	void
	 */
	function init()	{
		$this->MCONF = $GLOBALS['MCONF'];

		$this->menuConfig();

		// **************************
		// Initializing
		// **************************
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('EXT:restructure_redirect/mod/redirects.html');

		$this->doc->form = '<form action="" method="post">';

				// JavaScript
		$this->doc->JScode = $this->doc->wrapScriptTags('
			script_ended = 0;
			function jumpToUrl(URL)	{	//
				window.location.href = URL;
			}
		' . $this->doc->redirectUrls());
	}

	/**
	 * Initialization of the module menu configuration
	 *
	 * @return	void
	 */
	function menuConfig()	{
		// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved.
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			'function' => array(
				'listRedirects' => $GLOBALS['LANG']->getLL('listRedirects', TRUE)
			)
		);
			// CLEAN SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name'], 'ses');
	}

	/**
	 * This functions builds the content of the page
	 *
	 * @return	void
	 */
	function main()	{

		switch($this->MOD_SETTINGS['function'])	{
			case 'listRedirects':
				$this->content.=$this->listRedirects();
			break;
		}
			// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		//$markers['CSH'] = $docHeaderButtons['csh'];
		$markers['FUNC_MENU'] = t3lib_BEfunc::getFuncMenu(0,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function']);
		$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
		$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
			// Renders the module page
		$this->content = $this->doc->render(
			'Backend User Administration',
			$this->content
		);
	}

	/**
	 * Prints the content of the page
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons()	{

		$buttons = array(
			'add' => '',
			'csh' => '',
			'shortcut' => '',
			'save' => ''
		);
			// CSH
		//$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']);
		return $buttons;
	}





	/***************************
	 *
	 * OTHER FUNCTIONS:
	 *
	 ***************************/



	/**
	 * Builds a list of all links for a specific element (here: BE user) and returns it for print.
	 *
	 * @param	string		the db table that should be used
	 * @param	array		the BE user record to use
	 * @return	string		a HTML formatted list of the link
	 */
	function elementLinks($table,$row)	{
			// Info:
		$cells[]='<a href="#" onclick="top.launchView(\'' . $table . '\', \'' . $row['uid'] . '\',\'' . $GLOBALS['BACK_PATH'] . '\'); return false;" title="' . $GLOBALS['LANG']->getLL('showInformation', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-info') .
			'</a>';

			// Edit:
		$params='&edit[' . $table . '][' . $row['uid'] . ']=edit';
		$cells[]='<a href="#" onclick="' . t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'],'') . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:edit', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-open') .
			'</a>';

			// Hide:
		$hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
		if ($row[$hiddenField])	{
			$params='&data[' . $table . '][' . $row['uid'] . '][' . $hiddenField . ']=0';
			$cells[]='<a href="' . $this->doc->issueCommand($params) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:enable', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-edit-unhide') .
			'</a>';
		} else {
			$params='&data[' . $table . '][' . $row['uid'] . '][' . $hiddenField . ']=1';
			$cells[]='<a href="' . $this->doc->issueCommand($params) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:disable', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-edit-hide') .
			'</a>';
		}

			// Delete
		$params='&cmd[' . $table . '][' . $row['uid'] . '][delete]=1';
		$cells[]='<a href="' . $this->doc->issueCommand($params) . '" onclick="return confirm(unescape(\'' . $GLOBALS['LANG']->getLL('sureToDelete', TRUE) . '\'));" title="' . $GLOBALS['LANG']->getLL('delete', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-edit-delete') .
			'</a>';

		return implode('',$cells);
	}

	/**
	 * Returns the local path for this string (removes the PATH_site if it is included)
	 *
	 * @param	string		the path that will be checked
	 * @return	string		the local path
	 */
	function localPath($str)	{
		if (substr($str,0,strlen(PATH_site))==PATH_site)	{
			return substr($str,strlen(PATH_site));
		} else {
			return $str;
		}
	}

	/***************************
	 *
	 * "List Redirects" FUNCTIONS:
	 *
	 ***************************/

	/**
	 * @author
	 */
function listRedirects()	{
		$select_fields = '*';
		$from_table = 'tx_restructureredirect_redirects';
		$where_clause = 'deleted = 0 and hidden = 0';
		$orderBy = 'url';


			// Fetch active sessions of other users from storage:
		$redirects = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($select_fields,$from_table,$where_clause,'',$orderBy);
			// Process and visualized each active session as a table row:
		if (is_array($redirects)) {
			foreach ($redirects as $redirect) {
				$outTable .= '
					<tr class="bgColor4" height="17" valign="top">' .
						'<td nowrap="nowrap" valign="top">&nbsp;'.htmlspecialchars($redirect['url']).'</td>' .
						'<td nowrap="nowrap">' .
							date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'].' '.$GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $redirect['crdate']) .
						'</td>' .
						'<td nowrap="nowrap">' ;
				if ($redirect['expire'] > 0) {
					$outTable .= date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'].' '.$GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $redirect['expire']) ;
				} else {
					$outTable .= $GLOBALS['LANG']->getLL('never', TRUE) ;
				}
				$outTable .=
						'</td>' .
						'<td nowrap="nowrap">'.$redirect['pid'].'&nbsp;&nbsp;</td>' .
						'<td nowrap="nowrap">'.$this->elementLinks('tx_restructureredirect_redirects',$redirect).'</td>' .


					'</tr>';
			}
		}
			// Wrap <table> tag around the rows:
		$outTable = '
		<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">
			<tr class="t3-row-header">
				<td>' . $GLOBALS['LANG']->getLL('url', TRUE) . '</td>
				<td>' . $GLOBALS['LANG']->getLL('created', TRUE) . '</td>
				<td >' . $GLOBALS['LANG']->getLL('expires', TRUE) . '</td>
				<td colspan="2">' . $GLOBALS['LANG']->getLL('siteid', TRUE) . '</td>
			</tr>' . $outTable . '
		</table>';

		$content.= $this->doc->section($GLOBALS['LANG']->getLL('listRedirects', TRUE),$outTable,0,1);
		return $content;
	}


}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/restructure_redirect/mod/index.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/restructure_redirect/mod/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_tools_redirect');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
