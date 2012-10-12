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
			//debug1($uid);

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
			foreach ($paramArr as $params ) {
				foreach ( $subpages as $subpage ) {
					debug1($its_link->getLink($subpage[uid],$params));
				}
			}
		}

	}

	public function processCmdmap_preProcess ($command, $table, $id, $value, $obj) {
		debug1(array('command',$command));
		debug1(array('$table',$table));
		debug1(array('$id',$id));
		debug1(array('$value',$value));
		if ($command == 'move') {
			if ($table == 'pages') {
				$its_link = t3lib_div::makeInstance('tx_its_linkcreator',$id);
				if ($value < 0 ) {
					$target = $this->getPidFromPageID($value*-1);
					debug1(array('getPidFromPageID',$target));
				} else {
					$target= $value;
				}
				$source = $this->getPidFromPageID($id);
				debug1('-----------');
				debug1($source);
				debug1($target);
				debug1('-----------');
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
		//debug1(array('$obj',$obj));
	}

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