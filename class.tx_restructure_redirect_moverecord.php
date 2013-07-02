<?php
require_once(t3lib_extMgm::extPath('restructure_redirect').'class.tx_restructure_linkcreator.php');
require_once(PATH_t3lib.'class.t3lib_div.php');
class tx_restructure_linkcreator_tcemain {


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
				$restructure_linkcreator = t3lib_div::makeInstance('tx_restructure_linkcreator',$id);
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
						$link = $restructure_linkcreator->getLink($subpage[uid],$params);
						$restructure_linkcreator->createRedirectEntry($subpage['uid'],$link);
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