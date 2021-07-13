<?php

$fulltextservice = null;
if($settings->_enableFullSearch) {
	require_once("inc.ClassFulltextService.php");
	$fulltextservice = new SeedDMS_FulltextService();

	if($settings->_fullSearchEngine == 'sqlitefts') {
		$indexconf = array(
			'Indexer' => 'SeedDMS_SQLiteFTS_Indexer',
			'Search' => 'SeedDMS_SQLiteFTS_Search',
			'IndexedDocument' => 'SeedDMS_SQLiteFTS_IndexedDocument',
			'Conf' => array('indexdir' => $settings->_luceneDir)
		);
		$fulltextservice->addService('sqlitefts', $indexconf);

		require_once('SeedDMS/SQLiteFTS.php');
	} elseif($settings->_fullSearchEngine == 'lucene') {
		$indexconf = array(
			'Indexer' => 'SeedDMS_Lucene_Indexer',
			'Search' => 'SeedDMS_Lucene_Search',
			'IndexedDocument' => 'SeedDMS_Lucene_IndexedDocument',
			'Conf' => array('indexdir' => $settings->_luceneDir)
		);
		$fulltextservice->addService('lucene', $indexconf);

		if(!empty($settings->_luceneClassDir))
			require_once($settings->_luceneClassDir.'/Lucene.php');
		else
			require_once('SeedDMS/Lucene.php');
	} else {
		$indexconf = null;
		if(isset($GLOBALS['SEEDDMS_HOOKS']['initFulltext'])) {
			foreach($GLOBALS['SEEDDMS_HOOKS']['initFulltext'] as $hookObj) {
				if (method_exists($hookObj, 'initFulltextService')) {
					$indexconf = $hookObj->initFulltextService(array('engine'=>$settings->_fullSearchEngine, 'dms'=>$dms, 'settings'=>$settings));
				}
			}
		}
		if($indexconf) {
			$fulltextservice->addService($settings->_fullSearchEngine, $indexconf);
		}
	}
	$fulltextservice->setConverters(isset($settings->_converters['fulltext']) ? $settings->_converters['fulltext'] : null);
	$fulltextservice->setMaxSize($settings->_maxSizeForFullText);
	$fulltextservice->setCmdTimeout($settings->_cmdTimeout);
}

