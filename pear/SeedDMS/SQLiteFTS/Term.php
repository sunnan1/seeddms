<?php
/**
 * Implementation of a term
 *
 * @category   DMS
 * @package    SeedDMS_SQLiteFTS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: 1.0.15
 */


/**
 * Class for managing a term.
 *
 * @category   DMS
 * @package    SeedDMS_SQLiteFTS
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: 1.0.15
 */
class SeedDMS_SQLiteFTS_Term {

	/**
	 * @var string $text
	 * @access public
	 */
	public $text;

	/**
	 * @var string $field
	 * @access public
	 */
	public $field;

	/**
	 * @var integer $occurrence 
	 * @access public
	 */
	public $_occurrence;

	/**
	 *
	 */
	public function __construct($term, $col, $occurrence) { /* {{{ */
		$this->text = $term;
		$fields = array(
			0 => 'documentid',
			1 => 'title',
			2 => 'comment',
			3 => 'keywords',
			4 => 'category',
			5 => 'mimetype',
			6 => 'origfilename',
			7 => 'owner',
			8 => 'content',
			9 => 'created',
			10 => 'user',
			11 => 'status',
			12 => 'path'
		);
		$this->field = $fields[$col];
		$this->_occurrence = $occurrence;
	} /* }}} */

}
?>
