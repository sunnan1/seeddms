<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
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
require_once("inc/inc.Language.php");
require_once("inc/inc.Init.php");
require_once("inc/inc.Extension.php");
require_once("inc/inc.DBInit.php");
require_once("inc/inc.ClassUI.php");
require_once("inc/inc.ClassAccessOperation.php");
require_once("inc/inc.Authentication.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$view = UI::factory($theme, $tmp[1], array('dms'=>$dms, 'user'=>$user));
if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

$accessop = new SeedDMS_AccessOperation($dms, null, $user, $settings);

$attrdefs = $dms->getAllAttributeDefinitions();

if(isset($_GET['attrdefid']) && $_GET['attrdefid']) {
	$selattrdef = $dms->getAttributeDefinition($_GET['attrdefid']);
} else {
	$selattrdef = null;
}

if($view) {
	$view->setParam('accessobject', $accessop);
	$view->setParam('onepage', $settings->_onePageMode); // do most navigation by reloading areas of pages with ajax
	$view->setParam('attrdefs', $attrdefs);
	$view->setParam('selattrdef', $selattrdef);
	$view->setParam('showtree', showtree());
	$view->setParam('cachedir', $settings->_cacheDir);
	$view->setParam('enableRecursiveCount', $settings->_enableRecursiveCount);
	$view->setParam('maxRecursiveCount', $settings->_maxRecursiveCount);
	$view->setParam('previewWidthList', $settings->_previewWidthList);
	$view->setParam('timeout', $settings->_cmdTimeout);
	$view->setParam('xsendfile', $settings->_enableXsendfile);
	$view($_GET);
}
