<?php
/**
 * Implementation of SQLiteFTS index
 *
 * @category   DMS
 * @package    SeedDMS_Lucene
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: 1.0.15
 */


/**
 * Class for managing a SQLiteFTS index.
 *
 * @category   DMS
 * @package    SeedDMS_Lucene
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: 1.0.15
 */
class SeedDMS_SQLiteFTS_Indexer {
	/**
	 * @var object $index sqlite index
	 * @access protected
	 */
	protected $_conn;

	/**
	 * Constructor
	 *
	 */
	function __construct($indexerDir) { /* {{{ */
		$this->_conn = new PDO('sqlite:'.$indexerDir.'/index.db');
	} /* }}} */

	/**
	 * Open an existing index
	 *
	 * @param string $indexerDir directory on disk containing the index
	 */
	static function open($conf) { /* {{{ */
		if(file_exists($conf['indexdir'].'/index.db')) {
			return new SeedDMS_SQLiteFTS_Indexer($conf['indexdir']);
		} else
			return self::create($conf);
	} /* }}} */

	/**
	 * Create a new index
	 *
	 * @param array $conf $conf['indexdir'] is the directory on disk containing the index
	 */
	static function create($conf) { /* {{{ */
		if(file_exists($conf['indexdir'].'/index.db'))
			unlink($conf['indexdir'].'/index.db');
		$index =  new SeedDMS_SQLiteFTS_Indexer($conf['indexdir']);
		/* Make sure the sequence of fields is identical to the field list
		 * in SeedDMS_SQLiteFTS_Term
		 */
		$version = SQLite3::version();
		if($version['versionNumber'] >= 3008000)
			$sql = 'CREATE VIRTUAL TABLE docs USING fts4(documentid, title, comment, keywords, category, mimetype, origfilename, owner, content, created, users, status, path, notindexed=created, matchinfo=fts3)';
		else
			$sql = 'CREATE VIRTUAL TABLE docs USING fts4(documentid, title, comment, keywords, category, mimetype, origfilename, owner, content, created, users, status, path, matchinfo=fts3)';
		$res = $index->_conn->exec($sql);
		if($res === false) {
			return null;
		}
		$sql = 'CREATE VIRTUAL TABLE docs_terms USING fts4aux(docs);';
		$res = $index->_conn->exec($sql);
		if($res === false) {
			return null;
		}
		return($index);
	} /* }}} */

	/**
	 * Do some initialization
	 *
	 */
	static function init($stopWordsFile='') { /* {{{ */
	} /* }}} */

	/**
	 * Add document to index
	 *
	 * @param object $doc indexed document of class 
	 * SeedDMS_SQLiteFTS_IndexedDocument
	 * @return boolean false in case of an error, otherwise true
	 */
	function addDocument($doc) { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "INSERT INTO docs (documentid, title, comment, keywords, category, owner, content, mimetype, origfilename, created, users, status, path) VALUES (".$this->_conn->quote($doc->getFieldValue('document_id')).", ".$this->_conn->quote($doc->getFieldValue('title')).", ".$this->_conn->quote($doc->getFieldValue('comment')).", ".$this->_conn->quote($doc->getFieldValue('keywords')).", ".$this->_conn->quote($doc->getFieldValue('category')).", ".$this->_conn->quote($doc->getFieldValue('owner')).", ".$this->_conn->quote($doc->getFieldValue('content')).", ".$this->_conn->quote($doc->getFieldValue('mimetype')).", ".$this->_conn->quote($doc->getFieldValue('origfilename')).", ".(int)$doc->getFieldValue('created').", ".$this->_conn->quote($doc->getFieldValue('users')).", ".$this->_conn->quote($doc->getFieldValue('status')).", ".$this->_conn->quote($doc->getFieldValue('path'))/*time()*/.")";
		$res = $this->_conn->exec($sql);
		if($res === false) {
			return false;
			var_dump($this->_conn->errorInfo());
		}
		return $res;
	} /* }}} */

	/**
	 * Remove document from index
	 *
	 * @param object $doc indexed document of class 
	 * SeedDMS_SQLiteFTS_IndexedDocument
	 * @return boolean false in case of an error, otherwise true
	 */
	public function delete($id) { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "DELETE FROM docs WHERE docid=".(int) $id;
		$res = $this->_conn->exec($sql);
		return $res;
	} /* }}} */

	/**
	 * Check if document was deleted
	 *
	 * Just for compatibility with lucene.
	 *
	 * @return boolean always false
	 */
	public function isDeleted($id) { /* {{{ */
		return false;
	} /* }}} */

	/**
	 * Find documents in index
	 *
	 * @param string $query 
	 * @param array $limit array with elements 'limit' and 'offset'
	 * @return boolean false in case of an error, otherwise array with elements
	 * 'count', 'hits', 'facets'
	 */
	public function find($query, $limit=array()) { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "SELECT count(*) AS `c` FROM `docs`";
		if($query)
			$sql .= " WHERE docs MATCH ".$this->_conn->quote($query);
		$res = $this->_conn->query($sql);
		$row = $res->fetch();

		$sql = "SELECT docid, documentid FROM docs";
		if($query)
			$sql .= " WHERE docs MATCH ".$this->_conn->quote($query);
		$res = $this->_conn->query($sql);
		if(!empty($limit['limit']))
			$sql .= " LIMIT ".(int) $limit['limit'];
		if(!empty($limit['offset']))
			$sql .= " OFFSET ".(int) $limit['offset'];
		$res = $this->_conn->query($sql);
		$hits = array();
		if($res) {
			foreach($res as $rec) {
				$hit = new SeedDMS_SQLiteFTS_QueryHit($this);
				$hit->id = $rec['docid'];
				$hit->documentid = $rec['documentid'];
				$hits[] = $hit;
			}
		}
		return array('count'=>$row['c'], 'hits'=>$hits);
	} /* }}} */

	/**
	 * Get a single document from index
	 *
	 * @param integer $id id of document
	 * @return boolean false in case of an error, otherwise true
	 */
	public function findById($id) { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "SELECT docid FROM docs WHERE docid=".(int) $id;
		$res = $this->_conn->query($sql);
		$hits = array();
		if($res) {
			while($rec = $res->fetch(PDO::FETCH_ASSOC)) {
				$hit = new SeedDMS_SQLiteFTS_QueryHit($this);
				$hit->id = $rec['docid'];
				$hits[] = $hit;
			}
		}
		return $hits;
	} /* }}} */

	/**
	 * Get a single document from index
	 *
	 * @param integer $id id of index record
	 * @return boolean false in case of an error, otherwise true
	 */
	public function getDocument($id) { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "SELECT docid, documentid, title, comment, owner, keywords, category, mimetype, origfilename, created, users, status, path FROM docs WHERE docid=".$id;
		$res = $this->_conn->query($sql);
		$doc = false;
		if($res) {
			$rec = $res->fetch(PDO::FETCH_ASSOC);
			$doc = new SeedDMS_SQLiteFTS_Document();
			$doc->addField('docid', $rec['docid']);
			$doc->addField('document_id', $rec['documentid']);
			$doc->addField('title', $rec['title']);
			$doc->addField('comment', $rec['comment']);
			$doc->addField('keywords', $rec['keywords']);
			$doc->addField('category', $rec['category']);
			$doc->addField('mimetype', $rec['mimetype']);
			$doc->addField('origfilename', $rec['origfilename']);
			$doc->addField('owner', $rec['owner']);
			$doc->addField('created', $rec['created']);
			$doc->addField('users', $rec['users']);
			$doc->addField('status', $rec['status']);
			$doc->addField('path', $rec['path']);
		}
		return $doc;
	} /* }}} */

	/**
	 * Get a single folder from index
	 *
	 * @param integer $id id of folder
	 * @return boolean false in case of an error, otherwise true
	 */
	public function getFolder($id) { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "SELECT docid, documentid, title, comment, owner, keywords, category, mimetype, origfilename, created, users, status, path FROM docs WHERE documentid='F".$id."'";
		$res = $this->_conn->query($sql);
		$doc = false;
		if($res) {
			$rec = $res->fetch(PDO::FETCH_ASSOC);
			$doc = new SeedDMS_SQLiteFTS_Document();
			$doc->addField('docid', $rec['docid']);
			$doc->addField('document_id', $rec['documentid']);
			$doc->addField('title', $rec['title']);
			$doc->addField('comment', $rec['comment']);
			$doc->addField('owner', $rec['owner']);
			$doc->addField('created', $rec['created']);
			$doc->addField('users', $rec['users']);
			$doc->addField('path', $rec['path']);
		}
		return $doc;
	} /* }}} */

	/**
	 * Return list of terms in index
	 *
	 * This function does nothing!
	 */
	public function terms() { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "SELECT term, col, occurrences FROM docs_terms WHERE col!='*' ORDER BY col";
		$res = $this->_conn->query($sql);
		$terms = array();
		if($res) {
			while($rec = $res->fetch(PDO::FETCH_ASSOC)) {
				$term = new SeedDMS_SQLiteFTS_Term($rec['term'], $rec['col'], $rec['occurrences']);
				$terms[] = $term;
			}
		}
		return $terms;
	} /* }}} */

	/**
	 * Return list of documents in index
	 *
	 */
	public function count() { /* {{{ */
		$sql = "SELECT count(*) c FROM docs";
		$res = $this->_conn->query($sql);
		if($res) {
			$rec = $res->fetch(PDO::FETCH_ASSOC);
			return $rec['c'];
		}
		return 0;
	} /* }}} */

	/**
	 * Commit changes
	 *
	 * This function does nothing!
	 */
	function commit() { /* {{{ */
	} /* }}} */

	/**
	 * Optimize index
	 *
	 * This function does nothing!
	 */
	function optimize() { /* {{{ */
	} /* }}} */
}
?>
