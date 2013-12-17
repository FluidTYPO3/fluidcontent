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
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', 'CType = \'fed_fce\'', array('CType' => 'fluidcontent_content'));
		return $GLOBALS['TYPO3_DB']->sql_affected_rows() . ' rows have been updated';
	}
}
