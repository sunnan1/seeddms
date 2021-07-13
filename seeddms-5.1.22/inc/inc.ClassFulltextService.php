<?php
/**
 * Implementation of fulltext service
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2016 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Implementation of fulltext service
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2016 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_FulltextService {
	/**
	 * List of services for searching fulltext
	 */
	protected $services;

	/**
	 * List of converters
	 */
	protected $converters;

	/**
	 * Max file size for imediate indexing
	 */
	protected $maxsize;

	private $index;

	private $search;

	public function __construct() {
		$this->services = array();
		$this->converters = array();
		$this->maxsize = 0;
		$this->index = null;
		$this->search = null;
		$this->cmdtimeout = 5;
	}

	public function addService($name, $service) {
		$this->services[] = $service;
	}

	public function setConverters($converters) {
		$this->converters = $converters;
	}

	public function setMaxSize($maxsize) {
		$this->maxsize = $maxsize;
	}

	public function setCmdTimeout($timeout) {
		$this->cmdtimeout = $timeout;
	}

	/**
	 * Return an indexable document from the given document or folder
	 *
	 * @param SeedDMS_Core_Document|SeedDMS_Core_Folder $object document or folder
	 * to be indexed
	 * @param boolean $forceupdate set to true if the document shall be updated no
	 * matter how large the content is. Setting this to false will only update the
	 * document if its content is below the configured size.
	 * @return object indexed Document ready for passing to the indexer
	 */
	public function IndexedDocument($object, $forceupdate=false) {
		if($object->isType('document'))
			$nocontent = ($object->getLatestContent()->getFileSize() > $this->maxsize) && !$forceupdate;
		else
			$nocontent = true;
		return new $this->services[0]['IndexedDocument']($object->getDMS(), $object, $this->converters, $nocontent, $this->cmdtimeout);
	}

	/**
	 * Returns an instance of the indexer
	 *
	 * The indexer provides access to fulltext index. It allows to add and
	 * get documents.
	 *
	 * @return object instance of class specified in 'Indexer'
	 */
	public function Indexer($recreate=false) {
		if($this->index)
			return $this->index;

		if($this->services[0]) {
			if($recreate)
				$this->index = $this->services[0]['Indexer']::create($this->services[0]['Conf']);
			else
				$this->index = $this->services[0]['Indexer']::open($this->services[0]['Conf']);
			return $this->index;
		} else
			return null;
	}

	public function Search() {
		if($this->search)
			return $this->search;
		if($this->services[0]) {
			$this->search = new $this->services[0]['Search']($this->index);
			return $this->search;
		} else {
			return null;
		}
	}
}


