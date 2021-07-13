<?php
/**
 * Implementation of Preview controller
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
 * Class which does the busines logic for previewing a document
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_Preview extends SeedDMS_Controller_Common {

	public function run() {
		global $theme;
		$dms = $this->params['dms'];
		$type = $this->params['type'];
		$settings = $this->params['settings'];

		switch($type) {
			case "version":
				$version = $this->params['version'];
				$document = $this->params['document'];
				$width = $this->params['width'];
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
				if(null === $this->callHook('version')) {
					if($width)
						$previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir, $width, $settings->_cmdTimeout);
					else
						$previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir);
					$previewer->setConverters($settings->_converters['preview']);
					$previewer->setXsendfile($settings->_enableXsendfile);
					if(!$previewer->hasPreview($content)) {
						add_log_line("");
						if(!$previewer->createPreview($content)) {
							add_log_line("", PEAR_LOG_ERR);
						}
					}
					if(!$previewer->hasPreview($content)) {
						header('Content-Type: image/svg+xml');
						readfile('../views/'.$theme.'/images/empty.svg');
						exit;
					}
					header('Content-Type: image/png');
					$previewer->getPreview($content);
				}
				break;
			case "file":
				$object = $this->params['object'];
				$document = $this->params['document'];
				$width = $this->params['width'];
				if (!is_object($object)) {
					$this->errormsg = 'invalid_version';
					return false;
				}

				if(null === $this->callHook('file')) {
					if($width)
						$previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir, $width, $settings->_cmdTimeout);
					else
						$previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir);
					$previewer->setConverters($settings->_converters['preview']);
					$previewer->setXsendfile($settings->_enableXsendfile);

					if(!$previewer->hasPreview($object)) {
						add_log_line("");
						if(!$previewer->createPreview($object)) {
							add_log_line("", PEAR_LOG_ERR);
						}
					}
					if(!$previewer->hasPreview($object)) {
						header('Content-Type: image/svg+xml');
						readfile('../views/'.$theme.'/images/empty.svg');
						exit;
					}
					header('Content-Type: image/png');
					$previewer->getPreview($object);
				}
				break;
		}
		return true;
	}
}
