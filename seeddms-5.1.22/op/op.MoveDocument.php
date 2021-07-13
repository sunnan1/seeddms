<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
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

include("../inc/inc.Settings.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

/* Check if the form data comes from a trusted request */
if(!checkFormKey('movedocument', 'GET')) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_request_token"))),getMLText("invalid_request_token"));
}

if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_GET["documentid"];
$document = $dms->getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$oldFolder = $document->getFolder();

if (!isset($_GET["targetid"]) || !is_numeric($_GET["targetid"]) || $_GET["targetid"]<1) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_target_folder"));
}

$targetid = $_GET["targetid"];
$targetFolder = $dms->getFolder($targetid);

if (!is_object($targetFolder)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_target_folder"));
}

if (($document->getAccessMode($user, 'moveDocument') < M_READWRITE) || ($targetFolder->getAccessMode($user, 'moveDocument') < M_READWRITE)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

if($document->isLocked()) {
	$lockingUser = $document->getLockingUser();
	if (($lockingUser->getID() != $user->getID()) && ($document->getAccessMode($user, 'moveDocument') != M_ALL)) {
		UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("lock_message", array("email" => $lockingUser->getEmail(), "username" => htmlspecialchars($lockingUser->getFullName()))));
	}
}

if ($targetid == $oldFolder->getID()) {
	UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("target_equals_source_folder"));
}

/* Check if name already exists in the folder */
if(!$settings->_enableDuplicateDocNames) {
	if($targetFolder->hasDocumentByName($document->getName())) {
		UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("document_duplicate_name"));
	}
}

if ($document->setFolder($targetFolder)) {
	// Send notification to subscribers.
	if($notifier) {
		$nl1 = $oldFolder->getNotifyList();
		$nl2 = $document->getNotifyList();
		$nl3 = $targetFolder->getNotifyList();
		$nl = array(
			'users'=>array_unique(array_merge($nl1['users'], $nl2['users'], $nl3['users']), SORT_REGULAR),
			'groups'=>array_unique(array_merge($nl1['groups'], $nl2['groups'], $nl3['groups']), SORT_REGULAR)
		);
		$subject = "document_moved_email_subject";
		$message = "document_moved_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['old_folder_path'] = $oldFolder->getFolderPathPlain();
		$params['new_folder_path'] = $targetFolder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$params['sitename'] = $settings->_siteName;
		$params['http_root'] = $settings->_httpRoot;
		$notifier->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($nl["groups"] as $grp) {
			$notifier->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
		// if user is not owner send notification to owner
//			if ($user->getID() != $document->getOwner()->getID()) 
//				$notifier->toIndividual($user, $document->getOwner(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
	}

} else {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
}

add_log_line();

header("Location:../out/out.ViewDocument.php?documentid=".$documentid);

?>
