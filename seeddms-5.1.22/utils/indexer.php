<?php
if(isset($_SERVER['SEEDDMS_HOME'])) {
	ini_set('include_path', $_SERVER['SEEDDMS_HOME'].'/utils'. PATH_SEPARATOR .ini_get('include_path'));
	$myincpath = $_SERVER['SEEDDMS_HOME'];
} else {
	ini_set('include_path', dirname($argv[0]). PATH_SEPARATOR .ini_get('include_path'));
	$myincpath = dirname($argv[0]);
}

function usage() { /* {{{ */
	echo "Usage:".PHP_EOL;
	echo "  seeddms-indexer [-h] [-v] [-c] [--config <file>]".PHP_EOL;
	echo PHP_EOL;
	echo "Description:".PHP_EOL;
	echo "  This program recreates the full text index of SeedDMS.".PHP_EOL;
	echo PHP_EOL;
	echo "Options:".PHP_EOL;
	echo "  -h, --help: print usage information and exit.".PHP_EOL;
	echo "  -v, --version: print version and exit.".PHP_EOL;
	echo "  -c: recreate index.".PHP_EOL;
	echo "  --config: set alternative config file.".PHP_EOL;
} /* }}} */

$version = "0.0.2";
$shortoptions = "hvc";
$longoptions = array('help', 'version', 'config:');
if(false === ($options = getopt($shortoptions, $longoptions))) {
	usage();
	exit(0);
}

/* Print help and exit */
if(isset($options['h']) || isset($options['help'])) {
	usage();
	exit(0);
}

/* Print version and exit */
if(isset($options['v']) || isset($options['verѕion'])) {
	echo $version.PHP_EOL;
	exit(0);
}

/* Set alternative config file */
if(isset($options['config'])) {
	define('SEEDDMS_CONFIG_FILE', $options['config']);
} elseif(isset($_SERVER['SEEDDMS_CONFIG_FILE'])) {
	define('SEEDDMS_CONFIG_FILE', $_SERVER['SEEDDMS_CONFIG_FILE']);
}

/* recreate index */
$recreate = false;
if(isset($options['c'])) {
	$recreate = true;
}

include($myincpath."/inc/inc.Settings.php");
include($myincpath."/inc/inc.Init.php");
include($myincpath."/inc/inc.Extension.php");
include($myincpath."/inc/inc.DBInit.php");

function tree($dms, $fulltextservice, $folder, $indent='', $numdocs) { /* {{{ */
	global $settings, $themes;

	$index = $fulltextservice->Indexer();
	$lucenesearch = $fulltextservice->Search();

//	echo $themes->black($indent."D ".$folder->getName()).PHP_EOL;
	echo $themes->black($indent."D ".$folder->getId().":".$folder->getName()." ");
	if(($numdocs == 0) || !($hit = $lucenesearch->getFolder($folder->getId()))) {
		try {
			$idoc = $fulltextservice->IndexedDocument($folder, true);
			if(isset($GLOBALS['SEEDDMS_HOOKS']['indexFolder'])) {
				foreach($GLOBALS['SEEDDMS_HOOKS']['indexFolder'] as $hookObj) {
					if (method_exists($hookObj, 'preIndexFolder')) {
						$hookObj->preIndexDocument(null, $folder, $idoc);
					}
				}
			}
			$index->addDocument($idoc);
			echo $themes->green(" (Folder added)").PHP_EOL;
		} catch(Exception $e) {
			echo $themes->error(" (Timeout)").PHP_EOL;
		}
	} else {
		try {
			$created = (int) $hit->getDocument()->getFieldValue('created');
		} catch (Exception $e) {
			$created = 0;
		}
		if($created >= $folder->getDate()) {
			echo $themes->italic(" (Folder unchanged)").PHP_EOL;
		} else {
			$index->delete($hit->id);
			try {
				$idoc = $fulltextservice->IndexedDocument($folder, true);
				if(isset($GLOBALS['SEEDDMS_HOOKS']['indexDocument'])) {
					foreach($GLOBALS['SEEDDMS_HOOKS']['indexDocument'] as $hookObj) {
						if (method_exists($hookObj, 'preIndexDocument')) {
							$hookObj->preIndexDocument(null, $folder, $idoc);
						}
					}
				}
				$index->addDocument($idoc);
				echo $themes->green(" (Folder updated)").PHP_EOL;
			} catch(Exception $e) {
				echo $themes->error(" (Timeout)").PHP_EOL;
			}
		}
	}

	$subfolders = $folder->getSubFolders();
	foreach($subfolders as $subfolder) {
		tree($dms, $fulltextservice, $subfolder, $indent.'  ', $numdocs);
	}

	$documents = $folder->getDocuments();
	foreach($documents as $document) {
		echo $themes->black($indent."  ".$document->getId().":".$document->getName()." ");
		if(($numdocs == 0) || !($hit = $lucenesearch->getDocument($document->getId()))) {
			try {
				$idoc = $fulltextservice->IndexedDocument($document, true);
				if(isset($GLOBALS['SEEDDMS_HOOKS']['indexDocument'])) {
					foreach($GLOBALS['SEEDDMS_HOOKS']['indexDocument'] as $hookObj) {
						if (method_exists($hookObj, 'preIndexDocument')) {
							$hookObj->preIndexDocument(null, $document, $idoc);
						}
					}
				}
				$index->addDocument($idoc);
				echo $themes->green(" (Document added)").PHP_EOL;
			} catch(Exception $e) {
				echo $themes->error(" (Timeout)").PHP_EOL;
			}
		} else {
			try {
				$created = (int) $hit->getDocument()->getFieldValue('created');
			} catch (Exception $e) {
				$created = 0;
			}
			$content = $document->getLatestContent();
			if($created >= $content->getDate()) {
				echo $themes->italic(" (Document unchanged)").PHP_EOL;
			} else {
				$index->delete($hit->id);
				try {
					$idoc = $fulltextservice->IndexedDocument($document, true);
					if(isset($GLOBALS['SEEDDMS_HOOKS']['indexDocument'])) {
						foreach($GLOBALS['SEEDDMS_HOOKS']['indexDocument'] as $hookObj) {
							if (method_exists($hookObj, 'preIndexDocument')) {
								$hookObj->preIndexDocument(null, $document, $idoc);
							}
						}
					}
					$index->addDocument($idoc);
					echo $themes->green(" (Document updated)").PHP_EOL;
				} catch(Exception $e) {
					echo $themes->error(" (Timeout)").PHP_EOL;
				}
			}
		}
	}
} /* }}} */

$themes = new \AlecRabbit\ConsoleColour\Themes();

$index = $fulltextservice->Indexer($recreate);
if(!$index) {
	echo $themes->error("Could not create index.").PHP_EOL;
	exit(1);
}

$numdocs = $fulltextservice->Indexer()->count();
$folder = $dms->getFolder($settings->_rootFolderID);
/* if numdocs is 0, then there is no need to check if a document/folder is already
 * indexed. That speeds up the indexing.
 */
tree($dms, $fulltextservice, $folder,'', $numdocs);

$index->commit();
$index->optimize();
