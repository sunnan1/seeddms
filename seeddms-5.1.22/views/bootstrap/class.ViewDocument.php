<?php
/**
 * Implementation of ViewDocument view
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
 * Class which outputs the html page for ViewDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ViewDocument extends SeedDMS_Bootstrap_Style {

	protected function getAccessModeText($defMode) { /* {{{ */
		switch($defMode) {
			case M_NONE:
				return getMLText("access_mode_none");
				break;
			case M_READ:
				return getMLText("access_mode_read");
				break;
			case M_READWRITE:
				return getMLText("access_mode_readwrite");
				break;
			case M_ALL:
				return getMLText("access_mode_all");
				break;
		}
	} /* }}} */

	protected function printAccessList($obj) { /* {{{ */
		$accessList = $obj->getAccessList();
		if (count($accessList["users"]) == 0 && count($accessList["groups"]) == 0)
			return;

		$content = '';
		for ($i = 0; $i < count($accessList["groups"]); $i++)
		{
			$group = $accessList["groups"][$i]->getGroup();
			$accesstext = $this->getAccessModeText($accessList["groups"][$i]->getMode());
			$content .= $accesstext.": ".htmlspecialchars($group->getName());
			if ($i+1 < count($accessList["groups"]) || count($accessList["users"]) > 0)
				$content .= "<br />";
		}
		for ($i = 0; $i < count($accessList["users"]); $i++)
		{
			$user = $accessList["users"][$i]->getUser();
			$accesstext = $this->getAccessModeText($accessList["users"][$i]->getMode());
			$content .= $accesstext.": ".htmlspecialchars($user->getFullName());
			if ($i+1 < count($accessList["users"]))
				$content .= "<br />";
		}

		if(count($accessList["groups"]) + count($accessList["users"]) > 3) {
			$this->printPopupBox(getMLText('list_access_rights'), $content);
		} else {
			echo $content;
		}
	} /* }}} */

	/**
	 * Output a single attribute in the document info section
	 *
	 * @param object $attribute attribute
	 */
	protected function printAttribute($attribute) { /* {{{ */
		$attrdef = $attribute->getAttributeDefinition();
?>
		    <tr>
					<td><?php echo htmlspecialchars($attrdef->getName()); ?>:</td>
					<td><?php echo $this->getAttributeValue($attribute); ?></td>
		    </tr>
<?php
	} /* }}} */

	protected function printVersionAttributes($folder, $version) { /* {{{ */
			$attributes = $version->getAttributes();
			if($attributes) {
				foreach($attributes as $attribute) {
					$arr = $this->callHook('showDocumentContentAttribute', $version, $attribute);
					if(is_array($arr)) {
						print "<li>".$arr[0].": ".$arr[1]."</li>\n";
					} else {
						$attrdef = $attribute->getAttributeDefinition();
						print "<li>".htmlspecialchars($attrdef->getName()).": ";
						$this->printAttributeValue($attribute);
						echo "</li>\n";
					}
				}
			}
	} /* }}} */

	function documentListItem() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$previewwidth = $this->params['previewWidthList'];
		$cachedir = $this->params['cachedir'];
		$document = $this->params['document'];
		if($document) {
			if ($document->getAccessMode($user) >= M_READ) {
				$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth);
				$txt = $this->callHook('documentListItem', $document, $previewer, false, 'viewitem');
				if(is_string($txt))
					$content = $txt;
				else 
					$content = $this->documentListRow($document, $previewer, true);
				echo $content;
			}
		}
	} /* }}} */

	function timelinedata() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$document = $this->params['document'];

		$jsondata = array();
		if($user->isAdmin()) {
			$data = $document->getTimeline();

			foreach($data as $i=>$item) {
				switch($item['type']) {
				case 'add_version':
					$msg = getMLText('timeline_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName()), 'version'=> $item['version']));
					break;
				case 'add_file':
					$msg = getMLText('timeline_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName())));
					break;
				case 'status_change':
					$msg = getMLText('timeline_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName()), 'version'=> $item['version'], 'status'=> getOverallStatusText($item['status'])));
					break;
				default:
					$msg = '???';
				}
				$data[$i]['msg'] = $msg;
			}

			foreach($data as $item) {
				if($item['type'] == 'status_change')
					$classname = $item['type']."_".$item['status'];
				else
					$classname = $item['type'];
				$d = makeTsFromLongDate($item['date']);
				$jsondata[] = array('start'=>date('c', $d)/*$item['date']*/, 'content'=>$item['msg'], 'className'=>$classname);
			}
		}
		header('Content-Type: application/json');
		echo json_encode($jsondata);
	} /* }}} */

	function js() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$document = $this->params['document'];

		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('cancel', 'splash_move_document', 'confirm_move_document', 'move_document', 'confirm_transfer_link_document', 'transfer_content', 'link_document', 'splash_move_folder', 'confirm_move_folder', 'move_folder'));
		if($user->isAdmin()) {
			$latestContent = $this->callHook('documentLatestContent', $document);
			if($latestContent === null)
				$latestContent = $document->getLatestContent();
			$this->printTimelineJs('out.ViewDocument.php?action=timelinedata&documentid='.$latestContent->getDocument()->getID(), 300, '', date('Y-m-d'));
		}
//		$this->printDocumentChooserJs("form1");
		$this->printDeleteDocumentButtonJs();
		/* Add js for catching click on document in one page mode */
		$this->printClickDocumentJs();
	} /* }}} */

	function documentInfos() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$document = $this->params['document'];

		$txt = $this->callHook('documentInfos', $document);
		if(is_string($txt))
			echo $txt;
		else {
		$this->contentHeading(getMLText("document_infos"));
		$this->contentContainerStart();
		$txt = $this->callHook('preDocumentInfos', $document);
		if(is_string($txt))
			echo $txt;
?>
		<table class="table-condensed">
<?php
		if($user->isAdmin()) {
			echo "<tr>";
			echo "<td>".getMLText("id").":</td>\n";
			echo "<td>".htmlspecialchars($document->getID())."</td>\n";
			echo "</tr>";
		}
?>
		<tr>
		<td><?php printMLText("name");?>:</td>
		<td><?php $this->printInlineEdit(htmlspecialchars($document->getName()), $document);?></td>
		</tr>
		<tr>
		<td><?php printMLText("owner");?>:</td>
		<td>
<?php
		$owner = $document->getOwner();
		print "<a class=\"infos\" href=\"mailto:".htmlspecialchars($owner->getEmail())."\">".htmlspecialchars($owner->getFullName())."</a>";
?>
		</td>
		</tr>
<?php
		if($document->getComment()) {
?>
		<tr>
		<td><?php printMLText("comment");?>:</td>
		<td><?php print htmlspecialchars($document->getComment());?></td>
		</tr>
<?php
		}
		if($user->isAdmin() || $document->getAccessMode($user) > M_READ) {
			echo "<tr>";
			echo "<td>".getMLText('default_access').":</td>";
			echo "<td>".$this->getAccessModeText($document->getDefaultAccess())."</td>";
			echo "</tr>";
			if($document->inheritsAccess()) {
				echo "<tr>";
				echo "<td>".getMLText("access_mode").":</td>\n";
				echo "<td>";
				echo getMLText("inherited")."<br />";
				$this->printAccessList($document);
				echo "</tr>";
			} else {
				echo "<tr>";
				echo "<td>".getMLText('access_mode').":</td>";
				echo "<td>";
				$this->printAccessList($document);
				echo "</td>";
				echo "</tr>";
			}
		}
?>
		<tr>
		<td><?php printMLText("used_discspace");?>:</td>
		<td><?php print SeedDMS_Core_File::format_filesize($document->getUsedDiskSpace());?></td>
		</tr>
		<tr>
		<td><?php printMLText("creation_date");?>:</td>
		<td><?php print getLongReadableDate($document->getDate()); ?></td>
		</tr>
<?php
		if($document->expires()) {
?>
		<tr>
		<td><?php printMLText("expires");?>:</td>
		<td><?php print getReadableDate($document->getExpires()); ?></td>
		</tr>
<?php
		}
		if($document->getKeywords()) {
			$arr = $this->callHook('showDocumentKeywords', $document);
			if(is_array($arr)) {
				echo "<tr>";
				echo "<td>".$arr[0].":</td>";
				echo "<td>".$arr[1]."</td>";
				echo "</tr>";
			} elseif(is_string($arr)) {
				echo $arr;
			} else {
?>
		<tr>
		<td><?php printMLText("keywords");?>:</td>
		<td><?php print htmlspecialchars($document->getKeywords());?></td>
		</tr>
<?php
			}
		}
		if($cats = $document->getCategories()) {
			$arr = $this->callHook('showDocumentCategories', $document);
			if(is_array($arr)) {
				echo "<tr>";
				echo "<td>".$arr[0].":</td>";
				echo "<td>".$arr[1]."</td>";
				echo "</tr>";
			} elseif(is_string($arr)) {
				echo $arr;
			} else {
?>
		<tr>
		<td><?php printMLText("categories");?>:</td>
		<td>
<?php
				$ct = array();
				foreach($cats as $cat)
					$ct[] = htmlspecialchars($cat->getName());
				echo implode(', ', $ct);
?>
		</td>
		</tr>
<?php
			}
		}
		$attributes = $document->getAttributes();
		if($attributes) {
			foreach($attributes as $attribute) {
				$arr = $this->callHook('showDocumentAttribute', $document, $attribute);
				if(is_array($arr)) {
					echo "<tr>";
					echo "<td>".$arr[0].":</td>";
					echo "<td>".$arr[1]."</td>";
					echo "</tr>";
				} elseif(is_string($arr)) {
					echo $arr;
				} else {
					$this->printAttribute($attribute);
				}
			}
		}
		$arrarr = $this->callHook('additionalDocumentInfos', $document);
		if(is_array($arrarr)) {
			foreach($arrarr as $arr) {
				echo "<tr>";
				echo "<td>".$arr[0].":</td>";
				echo "<td>".$arr[1]."</td>";
				echo "</tr>";
			}
		} elseif(is_string($arrarr)) {
			echo $arrarr;
		}
?>
		</table>
<?php
		$txt = $this->callHook('postDocumentInfos', $document);
		if(is_string($txt))
			echo $txt;
		$this->contentContainerEnd();
		}
	} /* }}} */

	function preview() { /* {{{ */
		$dms = $this->params['dms'];
		$document = $this->params['document'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];
		$showfullpreview = $this->params['showFullPreview'];
		$converttopdf = $this->params['convertToPdf'];
		$pdfconverters = $this->params['pdfConverters'];
		$cachedir = $this->params['cachedir'];
		if(!$showfullpreview)
			return;

		$latestContent = $this->callHook('documentLatestContent', $document);
		if($latestContent === null)
			$latestContent = $document->getLatestContent();
		$txt = $this->callHook('preDocumentPreview', $latestContent);
		if(is_string($txt))
			echo $txt;
		$txt = $this->callHook('documentPreview', $latestContent);
		if(is_string($txt))
			echo $txt;
		else {
			switch($latestContent->getMimeType()) {
			case 'audio/mpeg':
			case 'audio/mp3':
			case 'audio/ogg':
			case 'audio/wav':
				$this->contentHeading(getMLText("preview"));
	?>
			<audio controls style="width: 100%;" preload="false">
			<source  src="../op/op.ViewOnline.php?documentid=<?php echo $latestContent->getDocument()->getID(); ?>&version=<?php echo $latestContent->getVersion(); ?>" type="audio/mpeg">
			</audio>
	<?php
				break;
			case 'video/webm':
			case 'video/mp4':
			case 'video/avi':
			case 'video/msvideo':
			case 'video/x-msvideo':
			case 'video/x-matroska':
				$this->contentHeading(getMLText("preview"));
	?>
			<video controls style="width: 100%;">
			<source  src="../op/op.ViewOnline.php?documentid=<?php echo $latestContent->getDocument()->getID(); ?>&version=<?php echo $latestContent->getVersion(); ?>" type="video/mp4">
			</video>
	<?php
				break;
			case 'application/pdf':
				$this->contentHeading(getMLText("preview"));
	?>
				<iframe src="../pdfviewer/web/viewer.html?file=<?php echo urlencode('../../op/op.ViewOnline.php?documentid='.$latestContent->getDocument()->getID().'&version='.$latestContent->getVersion()); ?>" width="100%" height="700px"></iframe>
	<?php
				break;
			case 'image/svg+xml':
			case 'image/jpg':
			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':
				$this->contentHeading(getMLText("preview"));
	?>
				<img src="../op/op.ViewOnline.php?documentid=<?php echo $latestContent->getDocument()->getID(); ?>&version=<?php echo $latestContent->getVersion(); ?>" width="100%">
	<?php
				break;
			default:
				$txt = $this->callHook('additionalDocumentPreview', $latestContent);
				if(is_string($txt))
					echo $txt;
				break;
			}
		}
		$txt = $this->callHook('postDocumentPreview', $latestContent);
		if(is_string($txt))
			echo $txt;

		if($converttopdf) {
			$pdfpreviewer = new SeedDMS_Preview_PdfPreviewer($cachedir, $timeout, $xsendfile);
			$pdfpreviewer->setConverters($pdfconverters);
			if($pdfpreviewer->hasConverter($latestContent->getMimeType())) {
				$this->contentHeading(getMLText("preview_pdf"));
?>
				<iframe src="../pdfviewer/web/viewer.html?file=<?php echo urlencode('../../op/op.PdfPreview.php?documentid='.$latestContent->getDocument()->getID().'&version='.$latestContent->getVersion()); ?>" width="100%" height="700px"></iframe>
<?php
			}
		}
	} /* }}} */

	function show() { /* {{{ */
		parent::show();

		$this->callHook('preViewDocument');

		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$accessop = $this->params['accessobject'];
		$viewonlinefiletypes = $this->params['viewonlinefiletypes'];
		$enableownerrevapp = $this->params['enableownerrevapp'];
		$workflowmode = $this->params['workflowmode'];
		$cachedir = $this->params['cachedir'];
		$previewwidthlist = $this->params['previewWidthList'];
		$previewwidthdetail = $this->params['previewWidthDetail'];
		$previewconverters = $this->params['previewConverters'];
		$pdfconverters = $this->params['pdfConverters'];
		$documentid = $document->getId();
		$currenttab = $this->params['currenttab'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$versions = $this->callHook('documentVersions', $document);
		if($versions === null)
			$versions = $document->getContent();

		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/bootbox/bootbox.min.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<link href="../styles/'.$this->theme.'/timeline/timeline.css" rel="stylesheet">'."\n", 'css');
		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/timeline/timeline-min.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/timeline/timeline-locales.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);

		echo $this->callHook('preContent');
		if ($document->isLocked()) {
			$lockingUser = $document->getLockingUser();
			$txt = $this->callHook('documentIsLocked', $document, $lockingUser);
			if(is_string($txt))
				echo $txt;
			else {
?>
		<div class="alert alert-warning">
			<?php printMLText("lock_message", array("email" => $lockingUser->getEmail(), "username" => htmlspecialchars($lockingUser->getFullName())));?>
		</div>
<?php
			}
		}

		/* Retrieve latest content and  attacheѕ files */
		$latestContent = $this->callHook('documentLatestContent', $document);
		if($latestContent === null)
			$latestContent = $document->getLatestContent();
		$files = $document->getDocumentFiles($latestContent->getVersion());
		$files = SeedDMS_Core_DMS::filterDocumentFiles($user, $files);

		/* Retrieve linked documents */
		$links = $document->getDocumentLinks();
		$links = SeedDMS_Core_DMS::filterDocumentLinks($user, $links, 'target');

		/* Retrieve reverse linked documents */
		$reverselinks = $document->getReverseDocumentLinks();
		$reverselinks = SeedDMS_Core_DMS::filterDocumentLinks($user, $reverselinks, 'source');

		$needwkflaction = false;
		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
		} elseif($workflowmode == 'advanced') {
			$workflow = $latestContent->getWorkflow();
			if($workflow) {
				$workflowstate = $latestContent->getWorkflowState();
				$transitions = $workflow->getNextTransitions($workflowstate);
				$needwkflaction = $latestContent->needsWorkflowAction($user);
			}
		}

		if($needwkflaction) {
			$this->infoMsg(getMLText('needs_workflow_action'));
		}

		$status = $latestContent->getStatus();
		$reviewStatus = $latestContent->getReviewStatus();
		$approvalStatus = $latestContent->getApprovalStatus();

		$this->rowStart();
		$this->columnStart(4);
		$txt = $this->callHook('startLeftColumn', $document);
		if(is_string($txt))
			echo $txt;
		$this->documentInfos();
		$this->preview();

		$this->columnEnd();
		$this->columnStart(8);

		$txt = $this->callHook('startRightColumn', $document);
		if(is_string($txt))
			echo $txt;
?>
    <ul class="nav nav-tabs" id="docinfotab">
		  <li class="<?php if(!$currenttab || $currenttab == 'docinfo') echo 'active'; ?>"><a data-target="#docinfo" data-toggle="tab"><?php printMLText('current_version'); ?></a></li>
			<?php if (count($versions)>1) { ?>
		  <li class="<?php if($currenttab == 'previous') echo 'active'; ?>"><a data-target="#previous" data-toggle="tab"><?php printMLText('previous_versions'); ?></a></li>
<?php
			}
			if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
				if((is_array($reviewStatus) && count($reviewStatus)>0) ||
					(is_array($approvalStatus) && count($approvalStatus)>0)) {
?>
		  <li class="<?php if($currenttab == 'revapp') echo 'active'; ?>"><a data-target="#revapp" data-toggle="tab"><?php if($workflowmode == 'traditional') echo getMLText('reviewers')."/"; echo getMLText('approvers'); ?></a></li>
<?php
				}
			} elseif($workflowmode == 'advanced') {
				if($workflow) {
?>
		  <li class="<?php if($currenttab == 'workflow') echo 'active'; ?>"><a data-target="#workflow" data-toggle="tab"><?php echo getMLText('workflow'); ?></a></li>
<?php
				}
			}
?>
		  <li class="<?php if($currenttab == 'attachments') echo 'active'; ?>"><a data-target="#attachments" data-toggle="tab"><?php printMLText('linked_files'); echo (count($files)) ? " (".count($files).")" : ""; ?></a></li>
			<li class="<?php if($currenttab == 'links') echo 'active'; ?>"><a data-target="#links" data-toggle="tab"><?php printMLText('linked_documents'); echo (count($links) || count($reverselinks)) ? " (".count($links)."/".count($reverselinks).")" : ""; ?></a></li>
<?php
			$tabs = $this->callHook('extraTabs', $document);
			if($tabs) {
				foreach($tabs as $tabid=>$tab) {
					echo '<li class="'.($currenttab == $tabid ? 'active' : '').'"><a data-target="#'.$tabid.'" data-toggle="tab">'.$tab['title'].'</a></li>';
				}
			}
?>
		</ul>
		<div class="tab-content">
		  <div class="tab-pane <?php if(!$currenttab || $currenttab == 'docinfo') echo 'active'; ?>" id="docinfo">
<?php
		if(!$latestContent) {
			$this->contentContainerStart();
			print getMLText('document_content_missing');
			$this->contentContainerEnd();
			$this->contentEnd();
			$this->htmlEndPage();
			exit;
		}

		$checksum = SeedDMS_Core_File::checksum($dms->contentDir.$latestContent->getPath());
		if($checksum != $latestContent->getChecksum()) {
			$this->errorMsg(getMLText('wrong_checksum'));
		}

		$txt = $this->callHook('preLatestVersionTab', $latestContent);
		if(is_string($txt))
			echo $txt;

		// verify if file exists
		$file_exists=file_exists($dms->contentDir . $latestContent->getPath());

		$this->contentContainerStart();
		print "<table class=\"table\">";
		print "<thead>\n<tr>\n";
		print "<th colspan=\"2\">".htmlspecialchars($latestContent->getOriginalFileName())."</th>\n";
//		print "<th width='*'>".getMLText("file")."</th>\n";
//		print "<th width='25%'>".getMLText("comment")."</th>\n";
		print "<th width='20%'>".getMLText("status")."</th>\n";
		print "<th width='25%'></th>\n";
		print "</tr></thead><tbody>\n";
		print "<tr>\n";
		print "<td style=\"width:".$previewwidthdetail."px; text-align: center;\">";
		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidthdetail, $timeout, $xsendfile);
		$previewer->setConverters($previewconverters);
		$previewer->createPreview($latestContent);
		if ($file_exists) {
			if ($viewonlinefiletypes && (in_array(strtolower($latestContent->getFileType()), $viewonlinefiletypes) || in_array(strtolower($latestContent->getMimeType()), $viewonlinefiletypes))) {
				print "<a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$latestContent->getDocument()->getId()."&version=". $latestContent->getVersion()."\">";
			} else {
				print "<a href=\"../op/op.Download.php?documentid=".$latestContent->getDocument()->getId()."&version=".$latestContent->getVersion()."\">";
			}
		}
		if($previewer->hasPreview($latestContent)) {
			print("<img class=\"mimeicon\" width=\"".$previewwidthdetail."\" src=\"../op/op.Preview.php?documentid=".$latestContent->getDocument()->getID()."&version=".$latestContent->getVersion()."&width=".$previewwidthdetail."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">");
		} else {
			print "<img class=\"mimeicon\" width=\"".$previewwidthdetail."\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
		}
		if ($file_exists) {
			print "</a>";
		}
		print "</td>\n";

		print "<td><ul class=\"actions unstyled\">\n";
//		print "<li class=\"wordbreak\">".$latestContent->getOriginalFileName() ."</li>\n";
		print "<li>".getMLText('version').": ".$latestContent->getVersion()."</li>\n";

		if ($file_exists)
			print "<li>". SeedDMS_Core_File::format_filesize($latestContent->getFileSize()) .", ".htmlspecialchars($latestContent->getMimeType())."</li>";
		else print "<li><span class=\"warning\">".getMLText("document_deleted")."</span></li>";

		$updatingUser = $latestContent->getUser();
		print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".htmlspecialchars($updatingUser->getEmail())."\">".htmlspecialchars($updatingUser->getFullName())."</a></li>";
		print "<li>".getLongReadableDate($latestContent->getDate())."</li>";

		print "</ul>\n";
		$txt = $this->callHook('showVersionComment', $latestContent);
		if($txt) {
			echo $txt;
		} else {
			if($latestContent->getComment())
				print "<p style=\"font-style: italic;\">".htmlspecialchars($latestContent->getComment())."</p>";
		}
		print "<ul class=\"actions unstyled\">\n";
		$this->printVersionAttributes($folder, $latestContent);
		print "</ul></td>\n";

		print "<td width='10%'>";
		print getOverallStatusText($status["status"]);
		if ( $status["status"]==S_DRAFT_REV || $status["status"]==S_DRAFT_APP || $status["status"]==S_IN_WORKFLOW || $status["status"]==S_EXPIRED ){
			print "<br><span".($document->hasExpired()?" class=\"warning\" ":"").">".(!$document->getExpires() ? getMLText("does_not_expire") : getMLText("expires").": ".getReadableDate($document->getExpires()))."</span>";
		}
		print "</td>";

		print "<td>";

		if ($file_exists){
			print "<ul class=\"unstyled actions\">";
			print "<li><a href=\"../op/op.Download.php?documentid=".$latestContent->getDocument()->getId()."&version=".$latestContent->getVersion()."\"><i class=\"fa fa-download\"></i>".getMLText("download")."</a></li>";
			if ($viewonlinefiletypes && (in_array(strtolower($latestContent->getFileType()), $viewonlinefiletypes) || in_array(strtolower($latestContent->getMimeType()), $viewonlinefiletypes)))
				print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$latestContent->getDocument()->getId()."&version=". $latestContent->getVersion()."\"><i class=\"fa fa-star\"></i>" . getMLText("view_online") . "</a></li>";
			$items = $this->callHook('extraVersionViews', $latestContent);
			if($items) {
				foreach($items as $item) {
					if(is_string($item))
						echo "<li>".$item."</li>";
					elseif(is_array($item))
						echo "<li><a href=\"".$item['link']."\">".(!empty($item['icon']) ? "<i class=\"fa fa-".$item['icon']."\"></i>" : "").getMLText($item['label'])."</a></li>";
				}
			}
			print "</ul>";
		}

		print "<ul class=\"unstyled actions\">";
		if ($file_exists){
			if($accessop->mayEditVersion()) {
				print "<li><a href=\"../out/out.EditOnline.php?documentid=".$latestContent->getDocument()->getId()."&version=".$latestContent->getVersion()."\"><i class=\"fa fa-edit\"></i>".getMLText("edit_version")."</a></li>";
			}
		}
		/* Only admin has the right to remove version in any case or a regular
		 * user if enableVersionDeletion is on
		 */
		if($accessop->mayRemoveVersion()) {
			print "<li><a href=\"../out/out.RemoveVersion.php?documentid=".$latestContent->getDocument()->getId()."&version=".$latestContent->getVersion()."\"><i class=\"fa fa-remove\"></i>".getMLText("rm_version")."</a></li>";
		}
		if($accessop->mayOverwriteStatus()) {
			print "<li><a href='../out/out.OverrideContentStatus.php?documentid=".$latestContent->getDocument()->getId()."&version=".$latestContent->getVersion()."'><i class=\"fa fa-align-justify\"></i>".getMLText("change_status")."</a></li>";
		}
		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
			// Allow changing reviewers/approvals only if not reviewed
			if($accessop->maySetReviewersApprovers()) {
				print "<li><a href='../out/out.SetReviewersApprovers.php?documentid=".$latestContent->getDocument()->getId()."&version=".$latestContent->getVersion()."'><i class=\"fa fa-edit\"></i>".getMLText("change_assignments")."</a></li>";
			}
		} elseif($workflowmode == 'advanced') {
			if($accessop->maySetWorkflow()) {
				if(!$workflow) {
					print "<li><a href='../out/out.SetWorkflow.php?documentid=".$latestContent->getDocument()->getId()."&version=".$latestContent->getVersion()."'><i class=\"fa fa-random\"></i>".getMLText("set_workflow")."</a></li>";
				}
			}
		}
		/*
		if($accessop->maySetExpires()) {
			print "<li><a href='../out/out.SetExpires.php?documentid=".$documentid."'><i class=\"fa fa-clock-o\"></i>".getMLText("set_expiry")."</a></li>";
		}
		*/
		if($accessop->mayEditComment()) {
			print "<li><a href=\"out.EditComment.php?documentid=".$latestContent->getDocument()->getId()."&version=".$latestContent->getVersion()."\"><i class=\"fa fa-comment\"></i>".getMLText("edit_comment")."</a></li>";
		}
		if($accessop->mayEditAttributes()) {
			print "<li><a href=\"out.EditAttributes.php?documentid=".$latestContent->getDocument()->getId()."&version=".$latestContent->getVersion()."\"><i class=\"fa fa-edit\"></i>".getMLText("edit_attributes")."</a></li>";
		}

		$items = $this->callHook('extraVersionActions', $latestContent);
		if($items) {
			foreach($items as $item) {
				if(is_string($item))
					echo "<li>".$item."</li>";
				elseif(is_array($item))
					echo "<li><a href=\"".$item['link']."\">".(!empty($item['icon']) ? "<i class=\"fa fa-".$item['icon']."\"></i>" : "").getMLText($item['label'])."</a></li>";
			}
		}

		print "</ul>";
		echo "</td>";
		print "</tr></tbody>\n</table>\n";
		$this->contentContainerEnd();

		if($user->isAdmin()) {
			$this->contentHeading(getMLText("status"));
			$this->contentContainerStart();
			$statuslog = $latestContent->getStatusLog();
			echo "<table class=\"table table-condensed\"><thead>";
			echo "<th>".getMLText('date')."</th><th>".getMLText('status')."</th><th>".getMLText('user')."</th><th>".getMLText('comment')."</th></tr>\n";
			echo "</thead><tbody>";
			foreach($statuslog as $entry) {
				if($suser = $dms->getUser($entry['userID']))
					$fullname = htmlspecialchars($suser->getFullName());
				else
					$fullname = "--";
				echo "<tr><td>".getLongReadableDate($entry['date'])."</td><td>".getOverallStatusText($entry['status'])."</td><td>".$fullname."</td><td>".htmlspecialchars($entry['comment'])."</td></tr>\n";
			}
			print "</tbody>\n</table>\n";
			$this->contentContainerEnd();

			$wkflogs = $latestContent->getWorkflowLog();
			if($wkflogs) {
				$this->contentHeading(getMLText("workflow_summary"));
				$this->contentContainerStart();
				echo "<table class=\"table table-condensed\"><thead>";
				echo "<th>".getMLText('date')."</th><th>".getMLText('action')."</th><th>".getMLText('user')."</th><th>".getMLText('comment')."</th></tr>\n";
				echo "</thead><tbody>";
				foreach($wkflogs as $wkflog) {
					echo "<tr>";
					echo "<td>".$wkflog->getDate()."</td>";
					echo "<td>".htmlspecialchars($wkflog->getTransition()->getAction()->getName())."</td>";
					$loguser = $wkflog->getUser();
					echo "<td>".htmlspecialchars($loguser->getFullName())."</td>";
					echo "<td>".htmlspecialchars($wkflog->getComment())."</td>";
					echo "</tr>";
				}
				print "</tbody>\n</table>\n";
				$this->contentContainerEnd();
			}
		}
?>
		</div>
<?php
		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
			if((is_array($reviewStatus) && count($reviewStatus)>0) ||
				(is_array($approvalStatus) && count($approvalStatus)>0)) {
?>
		  <div class="tab-pane <?php if($currenttab == 'revapp') echo 'active'; ?>" id="revapp">
<?php
		$this->rowStart();
		/* Just check fo an exting reviewStatus, even workflow mode is set
		 * to traditional_only_approval. There may be old documents which
		 * are still in S_DRAFT_REV.
		 */
		if (/*$workflowmode != 'traditional_only_approval' &&*/ is_array($reviewStatus) && count($reviewStatus)>0) {

			$this->columnStart(6);
//		$this->contentContainerStart();
			print "<legend>".getMLText('reviewers')."</legend>";
			print "<table class=\"table table-condensed\">\n";

			print "<tr>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("last_update").", ".getMLText("comment")."</th>\n";
//			print "<td width='25%'><b>".getMLText("comment")."</b></td>";
			print "<th>".getMLText("status")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n";

			foreach ($reviewStatus as $r) {
				$class = '';
				switch($r['status']) {
				case '-1':
					$class = 'error';
					break;
				case '1':
					$class = 'success';
					break;
				}
				$required = null;
				$is_reviewer = false;
				$accesserr = '';
				switch ($r["type"]) {
					case 0: // Reviewer is an individual.
						$required = $dms->getUser($r["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_user")." '".$r["required"]."'";
						}
						else {
							$reqName = "<i class=\"fa fa-user\"></i> ".htmlspecialchars($required->getFullName()." (".$required->getLogin().")");
							if($user->isAdmin()) {
								if($document->getAccessMode($required) < M_READ || $latestContent->getAccessMode($required) < M_READ)
									$accesserr = getMLText("access_denied");
								elseif(is_object($required) && $required->isDisabled())
									$accesserr = getMLText("login_disabled_title");
							}
							if($required->getId() == $user->getId()/* && ($user->getId() != $owner->getId() || $enableownerrevapp == 1)*/)
								$is_reviewer = true;
						}
						break;
					case 1: // Reviewer is a group.
						$required = $dms->getGroup($r["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_group")." '".$r["required"]."'";
						}
						else {
							$reqName = "<i class=\"fa fa-group\"></i> ".htmlspecialchars($required->getName());
							if($user->isAdmin()) {
								$grpusers = $required->getUsers();
								if(!$grpusers)
									$accesserr = getMLText("no_group_members");
							}
							if($required->isMember($user)/* && ($user->getId() != $owner->getId() || $enableownerrevapp == 1)*/)
								$is_reviewer = true;
						}
						break;
				}
				if($user->isAdmin() || $r["status"] > -2) {
					print "<tr>\n";
					print "<td>".$reqName."</td>\n";
					print "<td><i style=\"font-size: 80%;\">".getLongReadableDate($r["date"])." - ";
					/* $updateUser is the user who has done the review */
					$updateUser = $dms->getUser($r["userID"]);
					print (is_object($updateUser) ? htmlspecialchars($updateUser->getFullName()." (".$updateUser->getLogin().")") : "unknown user id '".$r["userID"]."'")."</i><br />";
					print htmlspecialchars($r["comment"]);
					if($r['file']) {
						echo "<br />";
						echo "<a href=\"../op/op.Download.php?documentid=".$documentid."&reviewlogid=".$r['reviewLogID']."\" class=\"btn btn-secondary btn-mini\"><i class=\"fa fa-download\"></i> ".getMLText('download')."</a>";
					}
					print "</td>\n";
						print "<td>";
						if($class)
							echo "<i class=\"fa fa-circle text-".$class."\"></i> ";
						print getReviewStatusText($r["status"])."</td>\n";
					print "<td><ul class=\"unstyled\">";
					if($accesserr)
							echo "<li><span class=\"text-error\">".$accesserr."</span></li>";
	
					if($accessop->mayReview()) {
						if ($is_reviewer) {
							if ($r["status"]==0) {
								print "<li><a href=\"../out/out.ReviewDocument.php?documentid=".$latestContent->getDocument()->getId()."&version=".$latestContent->getVersion()."&reviewid=".$r['reviewID']."\" class=\"btn btn-primary btn-mini\">".getMLText("add_review")."</a></li>";
							} elseif ($accessop->mayUpdateReview($updateUser) && (($r["status"]==1)||($r["status"]==-1))) {
								print "<li><a href=\"../out/out.ReviewDocument.php?documentid=".$latestContent->getDocument()->getId()."&version=".$latestContent->getVersion()."&reviewid=".$r['reviewID']."\" class=\"btn btn-primary btn-mini\">".getMLText("edit")."</a></li>";
							}
						}
					}
	
					print "</ul></td>\n";	
					print "</tr>\n";
				}
			}
			print "</table>";
//		$this->contentContainerEnd();

			$this->columnEnd();
		}
		$this->columnStart(6);
//		$this->contentContainerStart();
		print "<legend>".getMLText('approvers')."</legend>";
		print "<table class=\"table table-condensed\">\n";
		if (is_array($approvalStatus) && count($approvalStatus)>0) {

			print "<tr>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("last_update").", ".getMLText("comment")."</th>\n";	
//			print "<td width='25%'><b>".getMLText("comment")."</b></td>";
			print "<th>".getMLText("status")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n";

			foreach ($approvalStatus as $a) {
				$class = '';
				switch($a['status']) {
				case '-1':
					$class = 'error';
					break;
				case '1':
					$class = 'success';
					break;
				}
				$required = null;
				$is_approver = false;
				$accesserr = '';
				switch ($a["type"]) {
					case 0: // Approver is an individual.
						$required = $dms->getUser($a["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_user")." '".$a["required"]."'";
						}
						else {
							$reqName = "<i class=\"fa fa-user\"></i> ".htmlspecialchars($required->getFullName()." (".$required->getLogin().")");
							if($user->isAdmin()) {
								if($document->getAccessMode($required) < M_READ || $latestContent->getAccessMode($required) < M_READ)
									$accesserr = getMLText("access_denied");
								elseif(is_object($required) && $required->isDisabled())
									$accesserr = getMLText("login_disabled_title");
							}
							if($required->getId() == $user->getId())
								$is_approver = true;
						}
						break;
					case 1: // Approver is a group.
						$required = $dms->getGroup($a["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_group")." '".$a["required"]."'";
						}
						else {
							$reqName = "<i class=\"fa fa-group\"></i> ".htmlspecialchars($required->getName());
							if($user->isAdmin()) {
								$grpusers = $required->getUsers();
								if(!$grpusers)
									$accesserr = getMLText("no_group_members");
							}
							if($required->isMember($user)/* && ($user->getId() != $owner->getId() || $enableownerrevapp == 1)*/)
								$is_approver = true;
						}
						break;
				}
				if($user->isAdmin() || $a["status"] > -2) {
					print "<tr>\n";
					print "<td>".$reqName."</td>\n";
					print "<td><i style=\"font-size: 80%;\">".getLongReadableDate($a["date"])." - ";
					/* $updateUser is the user who has done the approval */
					$updateUser = $dms->getUser($a["userID"]);
					print (is_object($updateUser) ? htmlspecialchars($updateUser->getFullName()." (".$updateUser->getLogin().")") : "unknown user id '".$a["userID"]."'")."</i><br />";	
					print htmlspecialchars($a["comment"]);
					if($a['file']) {
						echo "<br />";
						echo "<a href=\"../op/op.Download.php?documentid=".$documentid."&approvelogid=".$a['approveLogID']."\" class=\"btn btn-secondary btn-mini\"><i class=\"fa fa-download\"></i> ".getMLText('download')."</a>";
					}
					echo "</td>\n";
						print "<td>";
						if($class)
							echo "<i class=\"fa fa-circle text-".$class."\"></i> ";
						print getApprovalStatusText($a["status"])."</td>\n";
					print "<td><ul class=\"unstyled\">";
					if($accesserr)
							echo "<li><span class=\"text-error\">".$accesserr."</span></li>";
	
					if($accessop->mayApprove()) {
						if ($is_approver) {
							if ($a['status'] == 0) {
								print "<li><a class=\"btn btn-primary btn-mini\" href=\"../out/out.ApproveDocument.php?documentid=".$latestContent->getDocument()->getId()."&version=".$latestContent->getVersion()."&approveid=".$a['approveID']."\">".getMLText("add_approval")."</a></li>";
							} elseif ($accessop->mayUpdateApproval($updateUser) && (($a["status"]==1)||($a["status"]==-1))) {
								print "<li><a class=\"btn btn-primary btn-mini\" href=\"../out/out.ApproveDocument.php?documentid=".$latestContent->getDocument()->getId()."&version=".$latestContent->getVersion()."&approveid=".$a['approveID']."\">".getMLText("edit")."</a></li>";
							}
						}
					}

					print "</ul>";
					print "</td>\n";
					print "</tr>\n";
				}
			}
		}

		print "</table>\n";
//		$this->contentContainerEnd();
		$this->columnEnd();
		$this->rowEnd();

		if($user->isAdmin() || $user->getId() == $document->getOwner()->getId()) {
			$this->rowStart();
			/* Check for an existing review log, even if the workflowmode
			 * is set to traditional_only_approval. There may be old documents
			 * that still have a review log if the workflow mode has been
			 * changed afterwards.
			 */
			if($latestContent->getReviewStatus(10) /*$workflowmode != 'traditional_only_approval'*/) {
				$this->columnStart(6);
				$this->printProtocol($latestContent, 'review');
				$this->columnEnd();
			}
			$this->columnStart(6);
			$this->printProtocol($latestContent, 'approval');
			$this->columnEnd();
			$this->rowEnd();
		}
?>
		  </div>
<?php
		}
		} elseif($workflowmode == 'advanced') {
			if($workflow) {
				/* Check if user is involved in workflow */
				$user_is_involved = false;
				foreach($transitions as $transition) {
					if($latestContent->triggerWorkflowTransitionIsAllowed($user, $transition)) {
						$user_is_involved = true;
					}
				}
?>
		  <div class="tab-pane <?php if($currenttab == 'workflow') echo 'active'; ?>" id="workflow">
<?php
			$this->rowStart();
			if($user_is_involved || $user->isAdmin())
				$this->columnStart(6);
			else
				$this->columnStart(12);
			$this->contentContainerStart();
			if($user->isAdmin()) {
				if(SeedDMS_Core_DMS::checkIfEqual($workflow->getInitState(), $latestContent->getWorkflowState())) {
					print "<form action=\"../out/out.RemoveWorkflowFromDocument.php\" method=\"get\"><input type=\"hidden\" name=\"documentid\" value=\"".$latestContent->getDocument()->getId()."\" /><input type=\"hidden\" name=\"version\" value=\"".$latestContent->getVersion()."\" /><button type=\"submit\" class=\"btn btn-danger\"><i class=\"fa fa-remove\"></i> ".getMLText('rm_workflow')."</button></form>";
				} else {
					print "<form action=\"../out/out.RewindWorkflow.php\" method=\"get\"><input type=\"hidden\" name=\"documentid\" value=\"".$latestContent->getDocument()->getId()."\" /><input type=\"hidden\" name=\"version\" value=\"".$latestContent->getVersion()."\" /><button type=\"submit\" class=\"btn btn-danger\"><i class=\"fa fa-refresh\"></i> ".getMLText('rewind_workflow')."</button></form>";
				}
			}

			echo "<h4>".htmlspecialchars($workflow->getName())."</h4>";
			if($parentworkflow = $latestContent->getParentWorkflow()) {
				echo "<p>Sub workflow of '".htmlspecialchars($parentworkflow->getName())."'</p>";
			}
			echo "<h5>".getMLText('current_state').": ".htmlspecialchars($workflowstate->getName())."</h5>";
			echo "<table class=\"table table-condensed\">\n";
			echo "<tr>";
			echo "<td>".getMLText('next_state').":</td>";
			foreach($transitions as $transition) {
				$nextstate = $transition->getNextState();
				$docstatus = $nextstate->getDocumentStatus();
				echo "<td><i class=\"fa fa-circle".($docstatus == S_RELEASED ? " released" : ($docstatus == S_REJECTED ? " rejected" : " in-workflow"))."\"></i> ".htmlspecialchars($nextstate->getName())."</td>";
			}
			echo "</tr>";
			echo "<tr>";
			echo "<td>".getMLText('action').":</td>";
			foreach($transitions as $transition) {
				$action = $transition->getAction();
				echo "<td>".getMLText('action_'.strtolower($action->getName()), array(), htmlspecialchars($action->getName()))."</td>";
			}
			echo "</tr>";
			echo "<tr>";
			echo "<td>".getMLText('users').":</td>";
			foreach($transitions as $transition) {
				$transusers = $transition->getUsers();
				echo "<td>";
				foreach($transusers as $transuser) {
					$u = $transuser->getUser();
					echo htmlspecialchars($u->getFullName());
					if($document->getAccessMode($u) < M_READ) {
						echo " (no access)";
					}
					echo "<br />";
				}
				echo "</td>";
			}
			echo "</tr>";
			echo "<tr>";
			echo "<td>".getMLText('groups').":</td>";
			foreach($transitions as $transition) {
				$transgroups = $transition->getGroups();
				echo "<td>";
				foreach($transgroups as $transgroup) {
					$g = $transgroup->getGroup();
					echo getMLText('at_least_n_users_of_group',
						array("number_of_users" => $transgroup->getNumOfUsers(),
							"group" => htmlspecialchars($g->getName())));
					if ($document->getGroupAccessMode($g) < M_READ) {
						echo " (no access)";
					}
					echo "<br />";
				}
				echo "</td>";
			}
			echo "</tr>";
			echo "<tr class=\"success\">";
			echo "<td>".getMLText('users_done_work').":</td>";
			foreach($transitions as $transition) {
				echo "<td>";
				if($latestContent->executeWorkflowTransitionIsAllowed($transition)) {
					/* If this is reached, then the transition should have been executed
					 * but for some reason the next state hasn't been reached. This can
					 * be causes, if a transition which was previously already executed
					 * is about to be executed again. E.g. there was already a transition
					 * T1 from state S1 to S2 triggered by user U1.
					 * Then there was a second transition T2 from
					 * S2 back to S1. If the state S1 has been reached again, then
					 * executeWorkflowTransitionIsAllowed() will think that T1 could be
					 * executed because there is already a log entry saying, that U1
					 * has triggered the workflow.
					 */
					echo "Done ";
				}
				$wkflogs = $latestContent->getWorkflowLog($transition);
				foreach($wkflogs as $wkflog) {
					$loguser = $wkflog->getUser();
					echo htmlspecialchars($loguser->getFullName())." (";
					$names = array();
					foreach($loguser->getGroups() as $loggroup) {
						$names[] =  htmlspecialchars($loggroup->getName());
					}
					echo implode(", ", $names);
					echo ") - ";
					echo $wkflog->getDate();
					echo "<br />";
				}
				echo "</td>";
			}
			echo "</tr>";
			echo "<tr>";
			echo "<td></td>";
			$allowedtransitions = array();
			foreach($transitions as $transition) {
				echo "<td>";
				if($latestContent->triggerWorkflowTransitionIsAllowed($user, $transition)) {
					$action = $transition->getAction();
					print "<form action=\"../out/out.TriggerWorkflow.php\" method=\"get\"><input type=\"hidden\" name=\"documentid\" value=\"".$latestContent->getDocument()->getId()."\" /><input type=\"hidden\" name=\"version\" value=\"".$latestContent->getVersion()."\" /><input type=\"hidden\" name=\"transition\" value=\"".$transition->getID()."\" /><input type=\"submit\" class=\"btn btn-primary\" value=\"".getMLText('action_'.strtolower($action->getName()), array(), htmlspecialchars($action->getName()))."\" /></form>";
					$allowedtransitions[] = $transition;
				}
				echo "</td>";
			}
			echo "</tr>";
			echo "</table>";

			$workflows = $dms->getAllWorkflows();
			if($workflows) {
				$subworkflows = array();
				foreach($workflows as $wkf) {
					if($wkf->getInitState()->getID() == $workflowstate->getID()) {
						if($workflow->getID() != $wkf->getID()) {
							$subworkflows[] = $wkf;
						}
					}
				}
				if($subworkflows) {
					echo "<form action=\"../out/out.RunSubWorkflow.php\" method=\"get\"><input type=\"hidden\" name=\"documentid\" value=\"".$latestContent->getDocument()->getId()."\" /><input type=\"hidden\" name=\"version\" value=\"".$latestContent->getVersion()."\" />";
					echo "<select name=\"subworkflow\">";
					foreach($subworkflows as $subworkflow) {
						echo "<option value=\"".$subworkflow->getID()."\">".$subworkflow->getName()."</option>";
					}
					echo "</select>";
					echo "<label class=\"inline\">";
					echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"".getMLText('run_subworkflow')."\" />";
					echo "</lable>";
					echo "</form>";
				}
			}
			/* If in a sub workflow, the check if return the parent workflow
			 * is possible.
			 */
			if($parentworkflow = $latestContent->getParentWorkflow()) {
				$states = $parentworkflow->getStates();
				foreach($states as $state) {
					/* Check if the current workflow state is also a state in the
					 * parent workflow
					 */
					if($latestContent->getWorkflowState()->getID() == $state->getID()) {
						echo "Switching from sub workflow '".$workflow->getName()."' into state ".$state->getName()." of parent workflow '".$parentworkflow->getName()."' is possible<br />";
						/* Check if the transition from the state where the sub workflow
						 * starts into the current state is also allowed in the parent
						 * workflow. Checking at this point is actually too late, because
						 * the sub workflow shouldn't be entered in the first place,
						 * but that is difficult to check.
						 */
						/* If the init state has not been left, return is always possible */
						if($workflow->getInitState()->getID() == $latestContent->getWorkflowState()->getID()) {
							echo "Initial state of sub workflow has not been left. Return to parent workflow is possible<br />";
							echo "<form action=\"../out/out.ReturnFromSubWorkflow.php\" method=\"get\"><input type=\"hidden\" name=\"documentid\" value=\"".$latestContent->getDocument()->getId()."\" /><input type=\"hidden\" name=\"version\" value=\"".$latestContent->getVersion()."\" />";
							echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"".getMLText('return_from_subworkflow')."\" />";
							echo "</form>";
						} else {
							/* Get a transition from the last state in the parent workflow
							 * (which is the initial state of the sub workflow) into
							 * current state.
							 */
							echo "Check for transition from ".$workflow->getInitState()->getName()." into ".$latestContent->getWorkflowState()->getName()." is possible in parentworkflow ".$parentworkflow->getID()."<br />";
							$transitions = $parentworkflow->getTransitionsByStates($workflow->getInitState(), $latestContent->getWorkflowState());
							if($transitions) {
								echo "Found transitions in workflow ".$parentworkflow->getID()."<br />";
								foreach($transitions as $transition) {
									if($latestContent->triggerWorkflowTransitionIsAllowed($user, $transition)) {
										echo "Triggering transition is allowed<br />";
										echo "<form action=\"../out/out.ReturnFromSubWorkflow.php\" method=\"get\"><input type=\"hidden\" name=\"documentid\" value=\"".$latestContent->getDocument()->getId()."\" /><input type=\"hidden\" name=\"version\" value=\"".$latestContent->getVersion()."\" /><input type=\"hidden\" name=\"transition\" value=\"".$transition->getID()."\" />";
										echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"".getMLText('return_from_subworkflow')."\" />";
										echo "</form>";

									}
								}
							}
						}
					}
				}
			}
			$this->contentContainerEnd();
			$this->columnEnd();
			if($user_is_involved || $user->isAdmin()) {
				$this->columnStart(6);
?>
	<iframe src="out.WorkflowGraph.php?workflow=<?php echo $workflow->getID(); ?><?php if($allowedtransitions) foreach($allowedtransitions as $tr) {echo "&transitions[]=".$tr->getID();} ?>" width="99%" height="661" style="border: 1px solid #AAA;"></iframe>
<?php
				$this->columnEnd();
			}
			$this->rowEnd();
?>
		  </div>
<?php
			}
		}
		if (count($versions)>1) {
?>
		  <div class="tab-pane <?php if($currenttab == 'previous') echo 'active'; ?>" id="previous">
<?php
			$txt = $this->callHook('prePreviousVersionsTab', $versions);
			if(is_string($txt))
				echo $txt;

			$this->contentContainerStart();

			print "<table class=\"table\">";
			print "<thead>\n<tr>\n";
			print "<th colspan=\"2\"></th>\n";
			print "<th width='20%'>".getMLText("status")."</th>\n";
			print "<th width='25%'></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";

			for ($i = count($versions)-2; $i >= 0; $i--) {
				$version = $versions[$i];
				$vstat = $version->getStatus();
				$workflow = $version->getWorkflow();
				$workflowstate = $version->getWorkflowState();

				// verify if file exists
				$file_exists=file_exists($dms->contentDir . $version->getPath());

				print "<tr>\n";
				print "<td style=\"width:".$previewwidthdetail."px; text-align: center;\">";
				if($file_exists) {
					if ($viewonlinefiletypes && (in_array(strtolower($version->getFileType()), $viewonlinefiletypes) || in_array(strtolower($version->getMimeType()), $viewonlinefiletypes))) {
							print "<a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$version->getDocument()->getId()."&version=".$version->getVersion()."\">";
					} else {
						print "<a href=\"../op/op.Download.php?documentid=".$version->getDocument()->getId()."&version=".$version->getVersion()."\">";
					}
				}
				$previewer->createPreview($version);
				if($previewer->hasPreview($version)) {
					print("<img class=\"mimeicon\" width=\"".$previewwidthdetail."\" src=\"../op/op.Preview.php?documentid=".$version->getDocument()->getId()."&version=".$version->getVersion()."&width=".$previewwidthdetail."\" title=\"".htmlspecialchars($version->getMimeType())."\">");
				} else {
					print "<img class=\"mimeicon\" width=\"".$previewwidthdetail."\" src=\"".$this->getMimeIcon($version->getFileType())."\" title=\"".htmlspecialchars($version->getMimeType())."\">";
				}
				if($file_exists) {
					print "</a>\n";
				}
				print "</td>\n";
				print "<td><ul class=\"unstyled\">\n";
				print "<li>".htmlspecialchars($version->getOriginalFileName())."</li>\n";
				print "<li>".getMLText('version').": ".$version->getVersion()."</li>\n";
				if ($file_exists) print "<li>". SeedDMS_Core_File::format_filesize($version->getFileSize()) .", ".htmlspecialchars($version->getMimeType())."</li>";
				else print "<li><span class=\"warning\">".getMLText("document_deleted")."</span></li>";
				$updatingUser = $version->getUser();
				print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".htmlspecialchars($updatingUser->getEmail())."\">".htmlspecialchars($updatingUser->getFullName())."</a></li>";
				print "<li>".getLongReadableDate($version->getDate())."</li>";
				print "</ul>\n";
				$txt = $this->callHook('showVersionComment', $version);
				if($txt) {
					echo $txt;
				} else {
					if($version->getComment())
						print "<p style=\"font-style: italic;\">".htmlspecialchars($version->getComment())."</p>";
				}
				print "<ul class=\"actions unstyled\">\n";
				$this->printVersionAttributes($folder, $version);
				print "</ul></td>\n";
//				print "<td>".htmlspecialchars($version->getComment())."</td>";
				print "<td>".getOverallStatusText($vstat["status"])."</td>";
				print "<td>";
				if ($file_exists){
					print "<ul class=\"actions unstyled\">";
					print "<li><a href=\"../op/op.Download.php?documentid=".$version->getDocument()->getId()."&version=".$version->getVersion()."\"><i class=\"fa fa-download\"></i>".getMLText("download")."</a>";
					if ($viewonlinefiletypes && (in_array(strtolower($version->getFileType()), $viewonlinefiletypes) || in_array(strtolower($version->getMimeType()), $viewonlinefiletypes)))
						print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$version->getDocument()->getId()."&version=".$version->getVersion()."\"><i class=\"fa fa-star\"></i>" . getMLText("view_online") . "</a>";
					$items = $this->callHook('extraVersionViews', $version);
					if($items) {
						foreach($items as $item) {
							if(is_string($item))
								echo "<li>".$item."</li>";
							elseif(is_array($item))
								echo "<li><a href=\"".$item['link']."\">".(!empty($item['icon']) ? "<i class=\"fa fa-".$item['icon']."\"></i>" : "").getMLText($item['label'])."</a></li>";
						}
					}
					print "</ul>";
				}
				print "<ul class=\"actions unstyled\">";
				/* Only admin has the right to remove version in any case or a regular
				 * user if enableVersionDeletion is on
				 */
				if($accessop->mayRemoveVersion()) {
					print "<li><a href=\"out.RemoveVersion.php?documentid=".$version->getDocument()->getId()."&version=".$version->getVersion()."\"><i class=\"fa fa-remove\"></i>".getMLText("rm_version")."</a></li>";
				}
				if($accessop->mayEditComment()) {
					print "<li><a href=\"out.EditComment.php?documentid=".$version->getDocument()->getId()."&version=".$version->getVersion()."\"><i class=\"fa fa-comment\"></i>".getMLText("edit_comment")."</a></li>";
				}
				if($accessop->mayEditAttributes()) {
					print "<li><a href=\"out.EditAttributes.php?documentid=".$version->getDocument()->getId()."&version=".$version->getVersion()."\"><i class=\"fa fa-edit\"></i>".getMLText("edit_attributes")."</a></li>";
				}
				print "<li><a href='../out/out.DocumentVersionDetail.php?documentid=".$version->getDocument()->getId()."&version=".$version->getVersion()."'><i class=\"fa fa-info-circle\"></i>".getMLText("details")."</a></li>";
				$items = $this->callHook('extraVersionActions', $version);
				if($items) {
					foreach($items as $item) {
						if(is_string($item))
							echo "<li>".$item."</li>";
						elseif(is_array($item))
							echo "<li><a href=\"".$item['link']."\">".(!empty($item['icon']) ? "<i class=\"fa fa-".$item['icon']."\"></i>" : "").getMLText($item['label'])."</a></li>";
					}
				}
				print "</ul>";
				print "</td>\n</tr>\n";
			}
			print "</tbody>\n</table>\n";
			$this->contentContainerEnd();
?>
		  </div>
<?php
		}
?>
		  <div class="tab-pane <?php if($currenttab == 'attachments') echo 'active'; ?>" id="attachments">
<?php

		if (count($files) > 0) {

			print "<table class=\"table\">";
			print "<thead>\n<tr>\n";
			print "<th width='20%'></th>\n";
			print "<th width='20%'>".getMLText("file")."</th>\n";
			print "<th width='40%'>".getMLText("comment")."</th>\n";
			print "<th width='20%'></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";

			foreach($files as $file) {

				$file_exists=file_exists($dms->contentDir . $file->getPath());

				$responsibleUser = $file->getUser();

				print "<tr>";
				print "<td>";
				$previewer->createPreview($file, $previewwidthdetail);
				if($file_exists) {
					if ($viewonlinefiletypes && (in_array(strtolower($file->getFileType()), $viewonlinefiletypes) || in_array(strtolower($file->getMimeType()), $viewonlinefiletypes)))
						print "<a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&file=". $file->getID()."\">";
					else
						print "<a href=\"../op/op.Download.php?documentid=".$documentid."&file=".$file->getID()."\">";
				}
				if($previewer->hasPreview($file)) {
					print("<img class=\"mimeicon\" width=\"".$previewwidthdetail."\" src=\"../op/op.Preview.php?documentid=".$document->getID()."&file=".$file->getID()."&width=".$previewwidthdetail."\" title=\"".htmlspecialchars($file->getMimeType())."\">");
				} else {
					print "<img class=\"mimeicon\" width=\"".$previewwidthdetail."\" src=\"".$this->getMimeIcon($file->getFileType())."\" title=\"".htmlspecialchars($file->getMimeType())."\">";
				}
				if($file_exists) {
					print "</a>";
				}
				print "</td>";
				
				print "<td><ul class=\"unstyled\">\n";
				print "<li>".htmlspecialchars($file->getName())."</li>\n";
				if($file->getName() != $file->getOriginalFileName())
					print "<li>".htmlspecialchars($file->getOriginalFileName())."</li>\n";
				if ($file_exists)
					print "<li>".SeedDMS_Core_File::format_filesize(filesize($dms->contentDir . $file->getPath())) ." bytes, ".htmlspecialchars($file->getMimeType())."</li>";
				else print "<li>".htmlspecialchars($file->getMimeType())." - <span class=\"warning\">".getMLText("document_deleted")."</span></li>";

				print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".htmlspecialchars($responsibleUser->getEmail())."\">".htmlspecialchars($responsibleUser->getFullName())."</a></li>";
				print "<li>".getLongReadableDate($file->getDate())."</li>";
				if($file->getVersion())
					print "<li>".getMLText('linked_to_current_version')."</li>";
				else
					print "<li>".getMLText('linked_to_document')."</li>";
				print "</ul></td>";
				print "<td>".htmlspecialchars($file->getComment())."</td>";
			
				print "<td><ul class=\"unstyled actions\">";
				if ($file_exists) {
					print "<li><a href=\"../op/op.Download.php?documentid=".$documentid."&file=".$file->getID()."\"><i class=\"fa fa-download\"></i>".getMLText('download')."</a>";
					if ($viewonlinefiletypes && (in_array(strtolower($file->getFileType()), $viewonlinefiletypes) || in_array(strtolower($file->getMimeType()), $viewonlinefiletypes)))
						print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&file=". $file->getID()."\"><i class=\"fa fa-star\"></i>" . getMLText("view_online") . "</a></li>";
				}
				echo "</ul><ul class=\"unstyled actions\">";
				if (($document->getAccessMode($user) == M_ALL)||($file->getUserID()==$user->getID())) {
					print "<li><a href=\"out.RemoveDocumentFile.php?documentid=".$documentid."&fileid=".$file->getID()."\"><i class=\"fa fa-remove\"></i>".getMLText("delete")."</a></li>";
					print "<li><a href=\"out.EditDocumentFile.php?documentid=".$documentid."&fileid=".$file->getID()."\"><i class=\"fa fa-edit\"></i>".getMLText("edit")."</a></li>";
				}
				print "</ul></td>";		
				
				print "</tr>";
			}
			print "</tbody>\n</table>\n";	

		}
		else $this->infoMsg(getMLText("no_attached_files"));

		if ($document->getAccessMode($user) >= M_READWRITE){
			if(0){
?>
			<div id="_draganddrophandler" class="droptarget well alert" data-droptarget="attachment_<?= $document->getID(); ?>" data-target="<?= $document->getID(); ?>" data-uploadformtoken="<?= createFormKey(''); ?>"><?php printMLText('drop_files_here'); ?><a href="../out/out.AddFile.php?documentid=<?= $documentid ?>"> <?= getMLText("add") ?></a></div>
<?php
			}
			print "<a href=\"../out/out.AddFile.php?documentid=".$documentid."\" class=\"btn btn-primary\">".getMLText("add")."</a>\n";
		}
?>
		  </div>
		  <div class="tab-pane <?php if($currenttab == 'links') echo 'active'; ?>" id="links">
<?php
		if (count($links) > 0) {

			print "<table id=\"viewfolder-table\" class=\"table table-condensed table-hover\">";
			print "<thead>\n<tr>\n";
			print "<th></th>\n";	
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("status")."</th>\n";
			print "<th>".getMLText("action")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";

			foreach($links as $link) {
				$responsibleUser = $link->getUser();
				$targetDoc = $link->getTarget();

				echo $this->documentListRowStart($targetDoc);
				$targetDoc->verifyLastestContentExpriry();
				$txt = $this->callHook('documentListItem', $targetDoc, $previewer, false, 'reverselinks');
				if(is_string($txt))
					echo $txt;
				else {
					echo $this->documentListRow($targetDoc, $previewer, true);
				}
				print "<td><span class=\"actions\">";
				print getMLText("document_link_by")." ".htmlspecialchars($responsibleUser->getFullName());
				if (($user->getID() == $responsibleUser->getID()) || ($document->getAccessMode($user) == M_ALL ))
					print "<br />".getMLText("document_link_public").": ".(($link->isPublic()) ? getMLText("yes") : getMLText("no"));
				if (($user->getID() == $responsibleUser->getID()) || ($document->getAccessMode($user) == M_ALL ))
					print "<form action=\"../op/op.RemoveDocumentLink.php\" method=\"post\">".createHiddenFieldWithKey('removedocumentlink')."<input type=\"hidden\" name=\"documentid\" value=\"".$documentid."\" /><input type=\"hidden\" name=\"linkid\" value=\"".$link->getID()."\" /><button type=\"submit\" class=\"btn btn-danger btn-mini\"><i class=\"fa fa-remove\"></i> ".getMLText("delete")."</button></form>";
				print "</span></td>";
				echo $this->documentListRowEnd($targetDoc);
			}
			print "</tbody>\n</table>\n";
		}
		else $this->infoMsg(getMLText("no_linked_files"));

		if (!$user->isGuest()){
			$this->contentContainerStart();
?>
			<br>
			<form action="../op/op.AddDocumentLink.php" name="form1" class="form-horizontal">
			<input type="hidden" name="documentid" value="<?php print $documentid;?>">
			<?php echo createHiddenFieldWithKey('adddocumentlink'); ?>
			<?php $this->formField(getMLText("add_document_link"), $this->getDocumentChooserHtml("form1")); ?>
			<?php
			if ($document->getAccessMode($user) >= M_READWRITE) {
				$this->formField(
					getMLText("document_link_public"),
					array(
						'element'=>'input',
						'type'=>'checkbox',
						'name'=>'public',
						'value'=>'true',
						'checked'=>true
					)
				);
			}
			$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('save'));
?>
			</form>
<?php
			$this->contentContainerEnd();
		}

		if (count($reverselinks) > 0) {
			$this->contentHeading(getMLText("reverse_links"));
//			$this->contentContainerStart();

			print "<table id=\"viewfolder-table\" class=\"table table-condensed table-hover\">";
			print "<thead>\n<tr>\n";
			print "<th></th>\n";	
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("status")."</th>\n";
			print "<th>".getMLText("action")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";

			foreach($reverselinks as $link) {
				$responsibleUser = $link->getUser();
				$sourceDoc = $link->getDocument();

				echo $this->documentListRowStart($sourceDoc);
				$sourceDoc->verifyLastestContentExpriry();
				$txt = $this->callHook('documentListItem', $sourceDoc, $previewer, false, 'reverselinks');
				if(is_string($txt))
					echo $txt;
				else {
					echo $this->documentListRow($sourceDoc, $previewer, true);
				}
				print "<td><span class=\"actions\">";
				if (($user->getID() == $responsibleUser->getID()) || ($document->getAccessMode($user) == M_ALL ))
				print getMLText("document_link_by")." ".htmlspecialchars($responsibleUser->getFullName());
				if (($user->getID() == $responsibleUser->getID()) || ($document->getAccessMode($user) == M_ALL ))
					print "<br />".getMLText("document_link_public").": ".(($link->isPublic()) ? getMLText("yes") : getMLText("no"));
					print "<form action=\"../op/op.RemoveDocumentLink.php\" method=\"post\">".createHiddenFieldWithKey('removedocumentlink')."<input type=\"hidden\" name=\"documentid\" value=\"".$sourceDoc->getId()."\" /><input type=\"hidden\" name=\"linkid\" value=\"".$link->getID()."\" /><button type=\"submit\" class=\"btn btn-danger btn-mini\"><i class=\"fa fa-remove\"></i> ".getMLText("delete")."</button></form>";
				print "</span></td>";
				echo $this->documentListRowEnd($sourceDoc);
			}
			print "</tbody>\n</table>\n";
//			$this->contentContainerEnd();
		}
?>
			</div>
<?php
			if($tabs) {
				foreach($tabs as $tabid=>$tab) {
					echo '<div class="tab-pane '.($currenttab == $tabid ? 'active' : '').'" id="'.$tabid.'">';
					echo $tab['content'];
					echo "</div>\n";
				}
			}
?>
		</div>
<?php
		if($user->isAdmin()) {
			$this->contentHeading(getMLText("timeline"));
			$this->printTimelineHtml(300);
		}

		$this->columnEnd();
		$this->rowEnd();
		echo $this->callHook('postContent');
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
