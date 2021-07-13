<?php
/**
 * Implementation of lucene index
 *
 * @category   DMS
 * @package    SeedDMS_Lucene
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: 1.1.16
 */


/**
 * Class for managing a lucene index.
 *
 * @category   DMS
 * @package    SeedDMS_Lucene
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: 1.1.16
 */
class SeedDMS_Lucene_Indexer {
	/**
	 * @var string $indexname name of lucene index
	 * @access protected
	 */
	protected $indexname;

	static function open($conf) { /* {{{ */
		try {
			$index = Zend_Search_Lucene::open($conf['indexdir']);
			return($index);
		} catch (Exception $e) {
			return null;
		}
	} /* }}} */

	static function create($conf) { /* {{{ */
		try {
			$index = Zend_Search_Lucene::create($conf['indexdir']);
			return($index);
		} catch (Exception $e) {
			return null;
		}
	} /* }}} */

	/**
	 * Do some initialization
	 *
	 */
	static function init($stopWordsFile='') { /* {{{ */
		$analyzer = new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8_CaseInsensitive();
		if($stopWordsFile && file_exists($stopWordsFile)) {
			$stopWordsFilter = new Zend_Search_Lucene_Analysis_TokenFilter_StopWords();
			$stopWordsFilter->loadFromFile($stopWordsFile);
			$analyzer->addFilter($stopWordsFilter);
		}
		 
		Zend_Search_Lucene_Analysis_Analyzer::setDefault($analyzer);
	} /* }}} */


}
?>
