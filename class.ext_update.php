<?php

/**
 * Class ext_update
 *
 * Performs update tasks for extension Fluidcontent
 */
class ext_update {

	/**
	 * @return boolean
	 */
	public function access() {
		return TRUE;
	}

	/**
	 * @return string
	 */
	public function main() {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('CType,uid', 'tt_content', "CType='fed_fce'");
		foreach($rows as $row) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', "uid='" . intval($row['uid']) . "'", array('CType' => 'fluidcontent_content'));
		}

		return count($rows) . " rows has been updated";
	}
}
