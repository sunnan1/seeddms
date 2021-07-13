<?php
/**
 * Implementation of Download controller
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which does the busines logic for downloading a document
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_Download extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$type = $this->params['type'];

		switch($type) {
			case "version":
				if(empty($this->params['content'])) {
					$version = $this->params['version'];
					$document = $this->params['document'];
					if($version < 1) {
						$content = $this->callHook('documentLatestContent', $document);
						if($content === null)
							$content = $document->getLatestContent();
					} else {
						$content = $this->callHook('documentContent', $document, $version);
						if($content === null)
							$content = $document->getContentByVersion($version);
					}
					if (!is_object($content)) {
						$this->errormsg = 'invalid_version';
						return false;
					}
					/* set params['content'] for compatiblity with older extensions which
					 * expect the content in the controller
					 */
					$this->params['content'] = $content;
				} else {
					$content = $this->params['content'];
				}
				if(null === $this->callHook('version')) {
					if(file_exists($dms->contentDir . $content->getPath())) {
						header("Content-Transfer-Encoding: binary");
						$efilename = rawurlencode($content->getOriginalFileName());
						header("Content-Disposition: attachment; filename=\"" . $efilename . "\"; filename*=UTF-8''".$efilename);
						header("Content-Type: " . $content->getMimeType());
						header("Cache-Control: must-revalidate");

						sendFile($dms->contentDir.$content->getPath());
					}
				}
				break;
		}
		return true;
	}
}
