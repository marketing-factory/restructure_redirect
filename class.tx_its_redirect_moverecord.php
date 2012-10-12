<?php
require_once(t3lib_extMgm::extPath('restructure_redirect').'class.tx_its_linkcreator.php');
require_once(PATH_t3lib.'class.t3lib_div.php');
class tx_its_tcemain {
	function moveRecord1 ($table, $uid, $destPid, $propArr, $moveRec, $resolvedPid, $recordWasMoved, $obj) {

		if ($table == 'pages') {
			/* $uid page uid
			 * $destPid pid of the element the position before
			 * $proparray['pid'] source pid
			 * $resolvedPid destination pid
			 */


			/**
			 * get all sub pages
			 * get all urls from sub pages
			 *
			 */
		//	$obj->cObj->enableFields('pages' );
			$subpages = $this->getSubpages($uid);
	//		$link = $obj->pi_getPageLink($uid);
			$its_link = t3lib_div::makeInstance('tx_its_linkcreator',$uid);
			$link = $its_link->getLink($uid);
			$subpages [$uid] = array('pid'=>$resolvedPid , 'uid'=>$uid);
			//$paramArr = array();
			$paramArr = $this->getLangParams();
			$paramArr [0] = array();

		}

	}
	/**
	 * processCmdmap_preProcess using the processCmdmap_preProcess hook
	 * on moving a page in the pagetree redirects will be added automatically
	 * @param string $command
	 * @param string $table
	 * @param string $id
	 * @param string $value
	 * @param object $obj
	 * @return void
	 */

	public function processCmdmap_preProcess ($command, $table, $id, $value, $obj) {

		if ($command == 'move') {
			if ($table == 'pages') {
				$its_link = t3lib_div::makeInstance('tx_its_linkcreator',$id);
				if ($value < 0 ) {
					$target = $this->getPidFromPageID($value*-1);
				} else {
					$target= $value;
				}
				$source = $this->getPidFromPageID($id);

				if ($source == $target) {
					return;
				}
				$subpages = $this->getSubpages($id);
				$subpages [$id] = array('pid'=>$source , 'uid'=>$id);
				$paramArr = $this->getLangParams();
				$paramArr [0] = array();
				foreach ($paramArr as $params ) {
					foreach ( $subpages as $subpage ) {
						$link = $its_link->getLink($subpage[uid],$params);
						$its_link->createRedirectEntry($subpage['uid'],$link);
					}
				}


			}
		}

	}

	/**
	 * getLangParams
	 * getting the url parameters for differents languagges of the page
	 * return array
	 */

	private function getLangParams () {
		$table = "sys_language";
		$enableFields =   t3lib_BEfunc::BEenableFields($table);
		$where = "1=1";
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTQuery('*', $table,$where . $enableFields);
		$paramArr = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$paramArr[$row['uid']] = array('L' =>$row['uid']);
		}
		return $paramArr;
	}

	/**
	 * getSubpages
	 * @param string $uif
	 * @param array $subpages
	 * @return array
	 */

	private function getSubpages ($uid, $subpages = array()) {
		$table = "pages";
		$enableFields =   t3lib_BEfunc::BEenableFields($table);
		$where = "pid=". $uid;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTQuery('*', $table,$where . $enableFields);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$subpages[] = $row;
			$subpages = $this->getSubpages($row['uid'],$subpages);
		}
		return $subpages;
	}

	/**
	 * getPidFromPageID
	 * @param string $uid
	 * @return integer
	 */

	private function getPidFromPageID ($uid) {
		$table = "pages";
		$enableFields =   t3lib_BEfunc::BEenableFields($table);
		$where = "uid=".$uid;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTQuery('*', $table,$where . $enableFields);
		$paramArr = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			return $row['pid'] ;
		}
		return 0;
	}


}
?>