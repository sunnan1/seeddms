<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010-2016 Uwe Steinmann
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

if(!isset($settings))
	require_once("../inc/inc.Settings.php");
require_once("inc/inc.LogInit.php");
require_once("inc/inc.Utils.php");
require_once("inc/inc.Language.php");
require_once("inc/inc.Init.php");
require_once("inc/inc.Extension.php");
require_once("inc/inc.DBInit.php");
require_once("inc/inc.ClassUI.php");
require_once("inc/inc.ClassAccessOperation.php");
require_once("inc/inc.Authentication.php");

function getTime() {
	if (function_exists('microtime')) {
		$tm = microtime();
		$tm = explode(' ', $tm);
		return (float) sprintf('%f', $tm[1] + $tm[0]);
	}
	return time();
}

// Redirect to the search page if the navigation search button has been
// selected without supplying any search terms.
if (isset($_GET["navBar"])) {
	if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
		$folderid=$settings->_rootFolderID;
	} else {
		$folderid = $_GET["folderid"];
	}
}

$accessop = new SeedDMS_AccessOperation($dms, null, $user, $settings);

if(((!isset($_GET["fullsearch"]) && $settings->_defaultSearchMethod == 'fulltext') || !empty($_GET["fullsearch"])) && $settings->_enableFullSearch) {
// Search in Fulltext {{{
	if (isset($_GET["query"]) && is_string($_GET["query"])) {
		$query = $_GET["query"];
	}
	else {
		$query = "";
	}

	// category
	$categories = array();
	$categorynames = array();
	if(isset($_GET['category']) && $_GET['category']) {
		foreach($_GET['category'] as $catname) {
			if($catname) {
				$cat = $dms->getDocumentCategoryByName($catname);
				$categories[] = $cat;
				$categorynames[] = $cat->getName();
			}
		}
	} elseif(isset($_GET['categoryids']) && $_GET['categoryids']) {
		foreach($_GET['categoryids'] as $catid) {
			if($catid) {
				$cat = $dms->getDocumentCategory($catid);
				$categories[] = $cat;
				$categorynames[] = $cat->getName();
			}
		}
	}

	//
	// Get the page number to display. If the result set contains more than
	// 25 entries, it is displayed across multiple pages.
	//
	// This requires that a page number variable be used to track which page the
	// user is interested in, and an extra clause on the select statement.
	//
	// Default page to display is always one.
	$pageNumber=1;
	if (isset($_GET["pg"])) {
		if (is_numeric($_GET["pg"]) && $_GET["pg"]>0) {
			$pageNumber = (integer)$_GET["pg"];
		}
		else if (!strcasecmp($_GET["pg"], "all")) {
			$pageNumber = "all";
		}
	}

	// --------------- Suche starten --------------------------------------------

	// Check to see if the search has been restricted to a particular
	// document owner.
	$owner = [];
	if (isset($_GET["owner"])) {
		if (!is_array($_GET['owner'])) {
			if(!empty($_GET['owner']) && $o = $dms->getUserByLogin($_GET['owner']))
				$owner[] = $o->getLogin();
			else
				UI::exitError(getMLText("search"),getMLText("unknown_owner"));
		} else {
			foreach($_GET["owner"] as $l) {
				if($l && $o = $dms->getUserByLogin($l))
					$owner[] = $o->getLogin();
			}
		}
	}

	// Check to see if the search has been restricted to a particular
	// mimetype.
	$mimetype = [];
	if (isset($_GET["mimetype"])) {
		if (!is_array($_GET['mimetype'])) {
			if(!empty($_GET['mimetype']))
				$mimetype[] = $_GET['mimetype'];
		} else {
			foreach($_GET["mimetype"] as $l) {
				if($l)
					$mimetype[] = $l;
			}
		}
	}

	// status
	$status = array();
	if (isset($_GET["pendingReview"])){
		$status[] = S_DRAFT_REV;
	}
	if (isset($_GET["pendingApproval"])){
		$status[] = S_DRAFT_APP;
	}
	if (isset($_GET["inWorkflow"])){
		$status[] = S_IN_WORKFLOW;
	}
	if (isset($_GET["released"])){
		$status[] = S_RELEASED;
	}
	if (isset($_GET["rejected"])){
		$status[] = S_REJECTED;
	}
	if (isset($_GET["obsolete"])){
		$status[] = S_OBSOLETE;
	}
	if (isset($_GET["expired"])){
		$status[] = S_EXPIRED;
	}

	// Check to see if the search has been restricted to a particular sub-tree in
	// the folder hierarchy.
	$startFolder = null;
	if (isset($_GET["targetid"]) && is_numeric($_GET["targetid"]) && $_GET["targetid"]>0) {
		$targetid = $_GET["targetid"];
		$startFolder = $dms->getFolder($targetid);
		if (!is_object($startFolder)) {
			UI::exitError(getMLText("search"),getMLText("invalid_folder_id"));
		}
	}

	$rootFolder = $dms->getFolder($settings->_rootFolderID);

	$startTime = getTime();
	if($settings->_fullSearchEngine == 'lucene') {
		Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');
	}

	if(strlen($query) < 4 && strpos($query, '*')) {
		$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('splash_invalid_searchterm')));
		$dcount = 0;
		$totalPages = 0;
		$entries = array();
		$searchTime = 0;
	} else {
		$startTime = getTime();
		$limit = 20;
		$total = 0;
		$index = $fulltextservice->Indexer();
		if($index) {
			$lucenesearch = $fulltextservice->Search();
			$searchresult = $lucenesearch->search($query, array('owner'=>$owner, 'status'=>$status, 'category'=>$categorynames, 'user'=>$user->isAdmin() ? [] : [$user->getLogin()], 'mimetype'=>$mimetype, 'startFolder'=>$startFolder, 'rootFolder'=>$rootFolder), ($pageNumber == 'all' ? array() : array('limit'=>$limit, 'offset'=>$limit * ($pageNumber-1))));
			if($searchresult === false) {
				$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('splash_invalid_searchterm')));
				$dcount = 0;
				$fcount = 0;
				$totalPages = 0;
				$entries = array();
				$facets = array();
				$searchTime = 0;
			} else {
				$entries = array();
				$facets = $searchresult['facets'];
				$dcount = 0;
				$fcount = 0;
				if($searchresult) {
					foreach($searchresult['hits'] as $hit) {
						if($hit['document_id'][0] == 'D') {
							if($tmp = $dms->getDocument(substr($hit['document_id'], 1))) {
//								if($tmp->getAccessMode($user) >= M_READ) {
									$tmp->verifyLastestContentExpriry();
									$entries[] = $tmp;
									$dcount++;
//								}
							}
						} elseif($hit['document_id'][0] == 'F') {
							if($tmp = $dms->getFolder(substr($hit['document_id'], 1))) {
//								if($tmp->getAccessMode($user) >= M_READ) {
									$entries[] = $tmp;
									$fcount++;
//								}
							}
						}
					}
				}
				if($pageNumber != 'all' && $searchresult['count'] > $limit) {
					$totalPages = (int) ($searchresult['count']/$limit);
					if($searchresult['count']%$limit)
						$totalPages++;
//					if($limit > 0)
//						$entries = array_slice($entries, ($pageNumber-1)*$limit, $limit);
				} else {
					$totalPages = 1;
				}
				$total = $searchresult['count'];
			}
			$searchTime = getTime() - $startTime;
			$searchTime = round($searchTime, 2);
		} else {
			$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('splash_invalid_search_service')));
			$dcount = 0;
			$fcount = 0;
			$totalPages = 0;
			$entries = array();
			$facets = array();
			$searchTime = 0;
		}
	}
	// }}}
} else {
	// Search in Database {{{
	if (isset($_GET["query"]) && is_string($_GET["query"])) {
		$query = $_GET["query"];
	}
	else {
		$query = "";
	}

	if (isset($_GET["orderby"]) && is_string($_GET["orderby"])) {
		$orderby = $_GET["orderby"];
	}
	else {
		$orderby = "";
	}

	/* Select if only documents (0x01), only folders (0x02) or both (0x03)
	 * are found
	 */
	$resultmode = 0x03;
	if (isset($_GET["resultmode"]) && is_numeric($_GET["resultmode"])) {
			$resultmode = $_GET['resultmode'];
	}

	$mode = "AND";
	if (isset($_GET["mode"]) && is_numeric($_GET["mode"]) && $_GET["mode"]==0) {
			$mode = "OR";
	}

	$searchin = array();
	if (isset($_GET['searchin']) && is_array($_GET["searchin"])) {
		foreach ($_GET["searchin"] as $si) {
			if (isset($si) && is_numeric($si)) {
				switch ($si) {
					case 1: // keywords
					case 2: // name
					case 3: // comment
					case 4: // attributes
					case 5: // id
						$searchin[$si] = $si;
						break;
				}
			}
		}
	}

	// if none is checkd search all
	if (count($searchin)==0) $searchin=array(1, 2, 3, 4, 5);

	// Check to see if the search has been restricted to a particular sub-tree in
	// the folder hierarchy.
	if (isset($_GET["targetid"]) && is_numeric($_GET["targetid"]) && $_GET["targetid"]>0) {
		$targetid = $_GET["targetid"];
		$startFolder = $dms->getFolder($targetid);
	}
	else {
		$startFolder = $dms->getRootFolder();
	}
	if (!is_object($startFolder)) {
		UI::exitError(getMLText("search"),getMLText("invalid_folder_id"));
	}

	// Check to see if the search has been restricted to a particular
	// document owner.
	$owner = array();
	$ownerobjs = array();
	if (isset($_GET["owner"])) {
		if (!is_array($_GET['owner'])) {
			if(!empty($_GET['owner']) && $o = $dms->getUserByLogin($_GET['owner'])) {
				$ownerobjs[] = $o;
				$owner = $o->getLogin();
			} else
				UI::exitError(getMLText("search"),getMLText("unknown_owner"));
		} else {
			foreach($_GET["owner"] as $l) {
				if($o = $dms->getUserByLogin($l)) {
					$ownerobjs[] = $o;
					$owner[] = $o->getLogin();
				}
			}
		}
	}

	// Is the search restricted to documents created between two specific dates?
	$startdate = array();
	$stopdate = array();
	if (isset($_GET["creationdate"]) && $_GET["creationdate"]!=null) {
		$creationdate = true;
	} else {
		$creationdate = false;
	}

	if(isset($_GET["createstart"])) {
		$tmp = explode("-", $_GET["createstart"]);
		$startdate = array('year'=>(int)$tmp[0], 'month'=>(int)$tmp[1], 'day'=>(int)$tmp[2], 'hour'=>0, 'minute'=>0, 'second'=>0);
	} else {
		if(isset($_GET["createstartyear"]))
			$startdate = array('year'=>$_GET["createstartyear"], 'month'=>$_GET["createstartmonth"], 'day'=>$_GET["createstartday"], 'hour'=>0, 'minute'=>0, 'second'=>0);
	}
	if ($startdate && !checkdate($startdate['month'], $startdate['day'], $startdate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_create_date_end"));
	}
	if(isset($_GET["createend"])) {
		$tmp = explode("-", $_GET["createend"]);
		$stopdate = array('year'=>(int)$tmp[0], 'month'=>(int)$tmp[1], 'day'=>(int)$tmp[2], 'hour'=>23, 'minute'=>59, 'second'=>59);
	} else {
		if(isset($_GET["createendyear"]))
			$stopdate = array('year'=>$_GET["createendyear"], 'month'=>$_GET["createendmonth"], 'day'=>$_GET["createendday"], 'hour'=>23, 'minute'=>59, 'second'=>59);
	}
	if ($stopdate && !checkdate($stopdate['month'], $stopdate['day'], $stopdate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_create_date_end"));
	}

	$statusstartdate = array();
	$statusstopdate = array();
	if (isset($_GET["statusdate"]) && $_GET["statusdate"]!=null) {
		$statusdate = true;
	} else {
		$statusdate = false;
	}

	if(isset($_GET["statusstart"])) {
		$tmp = explode("-", $_GET["statusstart"]);
		$statusstartdate = array('year'=>(int)$tmp[0], 'month'=>(int)$tmp[1], 'day'=>(int)$tmp[2], 'hour'=>0, 'minute'=>0, 'second'=>0);
	}
	if ($statusstartdate && !checkdate($statusstartdate['month'], $startdate['day'], $startdate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_status_date_end"));
	}
	if(isset($_GET["statusend"])) {
		$tmp = explode("-", $_GET["statusend"]);
		$statusstopdate = array('year'=>(int)$tmp[0], 'month'=>(int)$tmp[1], 'day'=>(int)$tmp[2], 'hour'=>23, 'minute'=>59, 'second'=>59);
	}
	if ($statusstopdate && !checkdate($statusstopdate['month'], $stopdate['day'], $stopdate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_status_date_end"));
	}

	$expstartdate = array();
	$expstopdate = array();
	if (isset($_GET["expirationdate"]) && $_GET["expirationdate"]!=null) {
		$expirationdate = true;
	} else {
		$expirationdate = false;
	}

	if(isset($_GET["expirationstart"]) && $_GET["expirationstart"]) {
		$tmp = explode("-", $_GET["expirationstart"]);
		$expstartdate = array('year'=>(int)$tmp[0], 'month'=>(int)$tmp[1], 'day'=>(int)$tmp[2], 'hour'=>0, 'minute'=>0, 'second'=>0);
		if (!checkdate($expstartdate['month'], $expstartdate['day'], $expstartdate['year'])) {
			UI::exitError(getMLText("search"),getMLText("invalid_expiration_date_start"));
		}
	} else {
//		$expstartdate = array('year'=>$_GET["expirationstartyear"], 'month'=>$_GET["expirationstartmonth"], 'day'=>$_GET["expirationstartday"], 'hour'=>0, 'minute'=>0, 'second'=>0);
		$expstartdate = array();
	}
	if(isset($_GET["expirationend"]) && $_GET["expirationend"]) {
		$tmp = explode("-", $_GET["expirationend"]);
		$expstopdate = array('year'=>(int)$tmp[0], 'month'=>(int)$tmp[1], 'day'=>(int)$tmp[2], 'hour'=>0, 'minute'=>0, 'second'=>0);
		if (!checkdate($expstopdate['month'], $expstopdate['day'], $expstopdate['year'])) {
			UI::exitError(getMLText("search"),getMLText("invalid_expiration_date_end"));
		}
	} else {
		//$expstopdate = array('year'=>$_GET["expirationendyear"], 'month'=>$_GET["expirationendmonth"], 'day'=>$_GET["expirationendday"], 'hour'=>23, 'minute'=>59, 'second'=>59);
		$expstopdate = array();
	}

	// status
	$status = array();
	if (isset($_GET["pendingReview"])){
		$status[] = S_DRAFT_REV;
	}
	if (isset($_GET["pendingApproval"])){
		$status[] = S_DRAFT_APP;
	}
	if (isset($_GET["inWorkflow"])){
		$status[] = S_IN_WORKFLOW;
	}
	if (isset($_GET["released"])){
		$status[] = S_RELEASED;
	}
	if (isset($_GET["rejected"])){
		$status[] = S_REJECTED;
	}
	if (isset($_GET["obsolete"])){
		$status[] = S_OBSOLETE;
	}
	if (isset($_GET["expired"])){
		$status[] = S_EXPIRED;
	}

	/* Do not search for folders if result shall be filtered by status.
	 * If this is not done, unexplainable results will be delivered.
	 * e.g. a search for expired documents of a given user will list
	 * also all folders of that user because the status doesn't apply
	 * to folders.
	 */
//	if($status)
//		$resultmode = 0x01;

	// category
	$categories = array();
	$categorynames = array();
	if(isset($_GET['category']) && $_GET['category']) {
		foreach($_GET['category'] as $catname) {
			if($catname) {
				$cat = $dms->getDocumentCategoryByName($catname);
				$categories[] = $cat;
				$categorynames[] = $cat->getName();
			}
		}
	}

	/* Do not search for folders if result shall be filtered by categories. */
//	if($categories)
//		$resultmode = 0x01;

	if (isset($_GET["attributes"]))
		$attributes = $_GET["attributes"];
	else
		$attributes = array();

	foreach($attributes as $attrdefid=>$attribute) {
		$attrdef = $dms->getAttributeDefinition($attrdefid);
		if($attribute) {
			if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_date) {
				if(is_array($attribute)) {
					if(!empty($attributes[$attrdefid]['from']))
						$attributes[$attrdefid]['from'] = date('Y-m-d', makeTsFromDate($attribute['from']));
					if(!empty($attributes[$attrdefid]['to']))
						$attributes[$attrdefid]['to'] = date('Y-m-d', makeTsFromDate($attribute['to']));
				} else {
					$attributes[$attrdefid] = date('Y-m-d', makeTsFromDate($attribute));
				}
			}
		}
	}

	//
	// Get the page number to display. If the result set contains more than
	// 25 entries, it is displayed across multiple pages.
	//
	// This requires that a page number variable be used to track which page the
	// user is interested in, and an extra clause on the select statement.
	//
	// Default page to display is always one.
	$pageNumber=1;
	$limit = 15;
	if (isset($_GET["pg"])) {
		if (is_numeric($_GET["pg"]) && $_GET["pg"]>0) {
			$pageNumber = (int) $_GET["pg"];
		}
		elseif (!strcasecmp($_GET["pg"], "all")) {
		//	$limit = 0;
		}
	}


	// ---------------- Start searching -----------------------------------------
	$startTime = getTime();
	$resArr = $dms->search(array(
		'query'=>$query,
		'limit'=>0,
		'offset'=>0 /*$limit, ($pageNumber-1)*$limit*/,
		'logicalmode'=>$mode,
		'searchin'=>$searchin,
		'startFolder'=>$startFolder,
		'owner'=>$ownerobjs,
		'status'=>$status,
		'creationstartdate'=>$creationdate ? $startdate : array(),
		'creationenddate'=>$creationdate ? $stopdate : array(),
		'modificationstartdate'=>array(),
		'modificationenddate'=>array(),
		'categories'=>$categories,
		'attributes'=>$attributes,
		'mode'=>$resultmode,
		'expirationstartdate'=>$expirationdate ? $expstartdate : array(),
		'expirationenddate'=>$expirationdate ? $expstopdate : array(),
		'statusstartdate'=>$statusdate ? $statusstartdate : array(),
		'statusenddate'=>$statusdate ? $statusstopdate : array(),
		'orderby'=>$orderby
	));
	$total = $resArr['totalDocs'] + $resArr['totalFolders'];
	$searchTime = getTime() - $startTime;
	$searchTime = round($searchTime, 2);

	$entries = array();
	$fcount = 0;
	if($resArr['folders']) {
		foreach ($resArr['folders'] as $entry) {
			if ($entry->getAccessMode($user) >= M_READ) {
				$entries[] = $entry;
				$fcount++;
			}
		}
	}
	$dcount = 0;
	if($resArr['docs']) {
		foreach ($resArr['docs'] as $entry) {
			if ($entry->getAccessMode($user) >= M_READ) {
				$entry->verifyLastestContentExpriry();
				$entries[] = $entry;
				$dcount++;
			}
		}
	}
	if (!isset($_GET["pg"]) || strcasecmp($_GET["pg"], "all")) {
		$totalPages = (int) (count($entries)/$limit);
		if(count($entries)%$limit)
			$totalPages++;
		$entries = array_slice($entries, ($pageNumber-1)*$limit, $limit);
	} else
		$totalPages = 1;
	$facets = array();
// }}}
}

// -------------- Output results --------------------------------------------

if($settings->_showSingleSearchHit && count($entries) == 1) {
	$entry = $entries[0];
	if($entry->isType('document')) {
		header('Location: ../out/out.ViewDocument.php?documentid='.$entry->getID());
		exit;
	} elseif($entry->isType('folder')) {
		header('Location: ../out/out.ViewFolder.php?folderid='.$entry->getID());
		exit;
	}
} else {
	$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
	$view = UI::factory($theme, $tmp[1], array('dms'=>$dms, 'user'=>$user, 'query'=>$query, 'searchhits'=>$entries, 'totalpages'=>$totalPages, 'pagenumber'=>$pageNumber, 'searchtime'=>$searchTime, 'urlparams'=>$_GET, 'cachedir'=>$settings->_cacheDir));
	if($view) {
		$view->setParam('facets', $facets);
		$view->setParam('accessobject', $accessop);
		$view->setParam('onepage', $settings->_onePageMode); // do most navigation by reloading areas of pages with ajax
		$view->setParam('showtree', showtree());
		$view->setParam('enableRecursiveCount', $settings->_enableRecursiveCount);
		$view->setParam('maxRecursiveCount', $settings->_maxRecursiveCount);
		$view->setParam('total', $total);
		$view->setParam('totaldocs', $dcount /*resArr['totalDocs']*/);
		$view->setParam('totalfolders', $fcount /*resArr['totalFolders']*/);
		$view->setParam('fullsearch', (!empty($_GET["fullsearch"]) && $settings->_enableFullSearch) ? true : false);
		$view->setParam('mode', isset($mode) ? $mode : '');
		$view->setParam('orderby', isset($orderby) ? $orderby : '');
		$view->setParam('defaultsearchmethod', !empty($_GET["fullsearch"]) || $settings->_defaultSearchMethod);
		$view->setParam('resultmode', isset($resultmode) ? $resultmode : '');
		$view->setParam('searchin', isset($searchin) ? $searchin : array());
		$view->setParam('startfolder', isset($startFolder) ? $startFolder : null);
		$view->setParam('owner', $owner);
		$view->setParam('startdate', isset($startdate) ? $startdate : array());
		$view->setParam('stopdate', isset($stopdate) ? $stopdate : array());
		$view->setParam('expstartdate', isset($expstartdate) ? $expstartdate : array());
		$view->setParam('expstopdate', isset($expstopdate) ? $expstopdate : array());
		$view->setParam('statusstartdate', isset($statusstartdate) ? $statusstartdate : array());
		$view->setParam('statusstopdate', isset($statusstopdate) ? $statusstopdate : array());
		$view->setParam('creationdate', isset($creationdate) ? $creationdate : '');
		$view->setParam('expirationdate', isset($expirationdate) ? $expirationdate: '');
		$view->setParam('statusdate', isset($statusdate) ? $statusdate: '');
		$view->setParam('status', isset($status) ? $status : array());
		$view->setParam('categories', isset($categories) ? $categories : '');
		$view->setParam('category', isset($categorynames) ? $categorynames : '');
		$view->setParam('mimetype', isset($mimetype) ? $mimetype : '');
		$view->setParam('attributes', isset($attributes) ? $attributes : '');
		$attrdefs = $dms->getAllAttributeDefinitions(array(SeedDMS_Core_AttributeDefinition::objtype_document, SeedDMS_Core_AttributeDefinition::objtype_documentcontent, SeedDMS_Core_AttributeDefinition::objtype_folder, SeedDMS_Core_AttributeDefinition::objtype_all));
		$view->setParam('attrdefs', $attrdefs);
		$allCats = $dms->getDocumentCategories();
		$view->setParam('allcategories', $allCats);
		$allUsers = $dms->getAllUsers($settings->_sortUsersInList);
		$view->setParam('allusers', $allUsers);
		$view->setParam('workflowmode', $settings->_workflowMode);
		$view->setParam('enablefullsearch', $settings->_enableFullSearch);
		$view->setParam('previewWidthList', $settings->_previewWidthList);
		$view->setParam('timeout', $settings->_cmdTimeout);
		$view->setParam('xsendfile', $settings->_enableXsendfile);
		$view($_GET);
		exit;
	}
}
