<?php
/**
 * Implementation of ManageNotify view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Include class to preview documents
 */
require_once("SeedDMS/Preview.php");

/**
 * Class which outputs the html page for ManageNotify view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ManageNotify extends SeedDMS_Bootstrap_Style {

	// Get list of subscriptions for documents or folders for user or groups
	function getNotificationList($as_group, $folders) { /* {{{ */

		// First, get the list of groups of which the user is a member.
		$notifications = array();
		if ($as_group){
			if(!($groups = $this->user->getGroups()))
				return array();

			foreach ($groups as $group) {
				$tmp = $group->getNotifications($folders ? T_FOLDER : T_DOCUMENT);
				if($tmp) {
					$notifications = array_merge($notifications, $tmp);
				}
			}
		} else {
			$notifications = $this->user->getNotifications($folders ? T_FOLDER : T_DOCUMENT);
		}

		return $notifications;
	} /* }}} */

	function printFolderNotificationList($notifications, $deleteaction=true) { /* {{{ */
		if (count($notifications)==0) {
			printMLText("empty_notify_list");
		}
		else {

			print "<table class=\"table table-condensed\">";
			print "<thead><tr>\n";
			print "<th></th>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("owner")."</th>\n";
			print "<th>".getMLText("actions")."</th>\n";
			print "</tr></thead>\n<tbody>\n";
			foreach($notifications as $notification) {
				$fld = $this->dms->getFolder($notification->getTarget());
				if (is_object($fld)) {
					echo $this->folderListRowStart($fld);
					$txt = $this->callHook('folderListItem', $fld, true, 'viewfolder');
					if(is_string($txt))
						echo $txt;
					else {
						echo $this->folderListRow($fld, true);
					}
					print "<td>";
					if ($deleteaction) print "<a href='../op/op.ManageNotify.php?id=".$fld->getID()."&type=folder&action=del' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> ".getMLText("delete")."</a>";
					else print "<a href='../out/out.FolderNotify.php?folderid=".$fld->getID()."' class=\"btn btn-mini\">".getMLText("edit")."</a>";
					print "</td>";
					echo $this->folderListRowEnd($fld);
				}
			}
			print "</tbody></table>";
		}
	} /* }}} */

	function printDocumentNotificationList($notifications,$deleteaction=true) { /* {{{ */

		if (count($notifications)==0) {
			printMLText("empty_notify_list");
		}
		else {
			$previewer = new SeedDMS_Preview_Previewer($this->cachedir, $this->previewwidth, $this->timeout, $this->xsendfile);

			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th></th>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("status")."</th>\n";
			print "<th>".getMLText("action")."</th>\n";
			print "<th></th>\n";
			print "</tr></thead>\n<tbody>\n";
			foreach ($notifications as $notification) {
				$doc = $this->dms->getDocument($notification->getTarget());
				if (is_object($doc)) {
					$doc->verifyLastestContentExpriry();
					echo $this->documentListRowStart($doc);
					$txt = $this->callHook('documentListItem', $doc, $previewer, true, 'managenotify');
					if(is_string($txt))
						echo $txt;
					else {
						echo $this->documentListRow($doc, $previewer, true);
					}
					print "<td>";
					if ($deleteaction) print "<a href='../op/op.ManageNotify.php?id=".$doc->getID()."&type=document&action=del' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> ".getMLText("delete")."</a>";
					else print "<a href='../out/out.DocumentNotify.php?documentid=".$doc->getID()."' class=\"btn btn-mini\">".getMLText("edit")."</a>";
					print "</td>\n";
					echo $this->documentListRowEnd($doc);
				}
			}
			print "</tbody></table>";
		}
	} /* }}} */

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');

//		$this->printFolderChooserJs("form1");
//		$this->printDocumentChooserJs("form2");
		$this->printClickDocumentJs();
		$this->printClickFolderJs();
	} /* }}} */

	function show() { /* {{{ */
		$this->dms = $this->params['dms'];
		$this->user = $this->params['user'];
		$this->cachedir = $this->params['cachedir'];
		$this->previewwidth = $this->params['previewWidthList'];
		$this->db = $this->dms->getDB();
		$this->timeout = $this->params['timeout'];
		$this->xsendfile = $this->params['xsendfile'];

		$this->htmlStartPage(getMLText("my_account"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("my_account"), "my_account");

		$this->rowStart();
		$this->columnStart(6);
		$this->contentHeading(getMLText("edit_folder_notify"));
		$this->contentContainerStart();

		print "<form class=\"form-horizontal\" method=\"post\" action=\"../op/op.ManageNotify.php?type=folder&action=add\" name=\"form1\">";
		$this->formField(getMLText("choose_target_folder"), $this->getFolderChooserHtml("form1", M_READ));
		$this->formField(
			getMLText("include_subdirectories"),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'recursefolder',
				'value'=>1
			)
		);
		$this->formField(
			getMLText("include_documents"),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'recursedoc',
				'value'=>1
			)
		);
		$this->formSubmit("<i class=\"fa fa-plus\"></i> ".getMLText('add'));
		print "</form>";
		$this->contentContainerEnd();
		$this->columnEnd();
		$this->columnStart(6);
		$this->contentHeading(getMLText("edit_document_notify"));
		$this->contentContainerStart();
		print "<form method=\"post\" action=\"../op/op.ManageNotify.php?type=document&action=add\" name=\"form2\">";
		/* 'form1' must be passed to printDocumentChooser() because the typeahead
		 * function is currently hardcoded on this value */
		$this->formField(getMLText("choose_target_document"), $this->getDocumentChooserHtml("form2"));
		$this->formSubmit("<i class=\"fa fa-plus\"></i> ".getMLText('add'));
		print "</form>";

		$this->contentContainerEnd();
		$this->columnEnd();
		$this->rowEnd();

		//
		// Display the results.
		//
		$this->rowStart();
		$this->columnStart(6);
		$this->contentHeading(getMLText("user"));
		$ret=$this->getNotificationList(false,true);
		$this->printFolderNotificationList($ret);
		$this->contentHeading(getMLText("group"));
		$ret=$this->getNotificationList(true,true);
		$this->printFolderNotificationList($ret,false);
		$this->columnEnd();
		$this->columnStart(6);
		$this->contentHeading(getMLText("user"));
		$ret=$this->getNotificationList(false,false);
		$this->printDocumentNotificationList($ret);
		$this->contentHeading(getMLText("group"));
		$ret=$this->getNotificationList(true,false);
		$this->printDocumentNotificationList($ret,false);
		$this->columnEnd();
		$this->rowEnd();

		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
