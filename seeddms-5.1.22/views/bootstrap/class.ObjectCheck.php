<?php
/**
 * Implementation of ObjectCheck view
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
 * Class which outputs the html page for ObjectCheck view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ObjectCheck extends SeedDMS_Bootstrap_Style {

	function tree($dms, $folder, $previewer, $repair, $path=':', $indent='') { /* {{{ */
		global $user;

		/* Don't do folderlist check for root folder */
		if($path != ':') {
			/* If the path contains a folder id twice, the a cyclic relation
			 * exists.
			 */
			$tmparr = explode(':', $path);
			array_shift($tmparr);
			if(count($tmparr) != count(array_unique($tmparr))) {
				echo $this->folderListRowStart($folder);
				echo $this->folderListRow($folder, true);
				print "<td>Folder path contains cyclic relation</td>";
				echo "<td>";
				if($repair) {
					print "<span class=\"success\">".getMLText('repaired')."</span>\n";
				}
				echo "</td>";
				echo $this->folderListRowEnd($folder);
			}
			$folderList = $folder->getFolderList();
			/* Check the folder */
			if($folderList != $path) {
				echo $this->folderListRowStart($folder);
				echo $this->folderListRow($folder, true);
				$this->needsrepair = true;
				print "<td>Folderlist is '".$folderList."', should be '".$path."'</td>";
				echo "<td>";
				if($repair) {
					$folder->repair();
					print "<span class=\"success\">".getMLText('repaired')."</span>\n";
				}
				echo "</td>";
				echo $this->folderListRowEnd($folder);
			}
		}

		$subfolders = $folder->getSubFolders();
		foreach($subfolders as $subfolder) {
			$this->tree($dms, $subfolder, $previewer, $repair, $path.$folder->getId().':', $indent.'  ');
		}
		$path .= $folder->getId().':';
		$documents = $folder->getDocuments();
		foreach($documents as $document) {
			/* Check the folder list of the document */
			$folderList = $document->getFolderList();
			if($folderList != $path) {
				print "<tr>\n";
				$this->needsrepair = true;
				$lc = $document->getLatestContent();
				print "<td><a class=\"standardText\" href=\"../out/out.ViewDocument.php?documentid=".$document->getID()."\"><img class=\"mimeicon\" src=\"".$this->getMimeIcon($lc->getFileType())."\" title=\"".$lc->getMimeType()."\"></a></td>";
				print "<td><a class=\"standardText\" href=\"../out/out.ViewDocument.php?documentid=".$document->getID()."\">/";
				$folder = $document->getFolder();
				$tmppath = $folder->getPath();
				for ($i = 1; $i  < count($tmppath); $i++) {
					print htmlspecialchars($tmppath[$i]->getName())."/";
				}
				print htmlspecialchars($document->getName());
				print "</a></td>";
				$owner = $document->getOwner();
				print "<td>".htmlspecialchars($owner->getFullName())."</td>";
				print "<td>Folderlist is '".$folderList."', should be '".$path."'</td>";
				echo "<td>";
				if($repair) {
					$document->repair();
					print "<span class=\"success\">".getMLText('repaired')."</span>\n";
				}
				echo "</td>";
				print "</tr>\n";
			}

			/* Check if the content is available */
			$versions = $document->getContent();
			if($versions) {
				foreach($versions as $version) {
					$filepath = $dms->contentDir . $version->getPath();
					if(!file_exists($filepath)) {
						echo $this->documentListRowStart($document);
						echo $this->documentListRow($document, $previewer, true, $version->getVersion());
						print "<td>Document content of version ".$version->getVersion()." is missing ('".$path."')</td>";
						echo "<td>";
						if($repair) {
							print "<span class=\"warning\">Cannot be repaired</span>\n";
						}
						echo "</td>";
						echo $this->documentListRowEnd($document);
					}
					$mimetype = $version->getMimeType();
					$filetype = $version->getFileType();
					if(strlen($filetype) < 2) {
						switch($mimetype) {
						case "application/pdf":
						case "image/png":
						case "image/gif":
						case "image/jpg":
							$expect = substr($mimetype, -3, 3);
							if($filetype != '.'.$expect) {
								$this->needsrepair = true;
								echo $this->documentListRowStart($document);
								echo $this->documentListRow($document, $previewer, true, $version->getVersion());
								echo "<td>.".$expect." != ".$filetype."</td>";
								echo "<td>";
								if($repair) {
									$version->repair();
									echo "<span class=\"success\">".getMLText('repaired')."</span>";
								}
								echo "</td>";
								echo $this->documentListRowEnd($document);
							}
							break;
						}
					}
				}
			} else {
				echo $this->documentListRowStart($document);
				echo $this->documentListRow($document, $previewer, true);
				echo "<td>Document has no content! Delete the document manually.</td>";
				echo "<td></td>";
				echo $this->documentListRowEnd($document);
			}
		}
	} /* }}} */

	function js() { /* {{{ */
		$user = $this->params['user'];
		$folder = $this->params['folder'];

		header('Content-Type: application/javascript; charset=UTF-8');

		$this->printDeleteFolderButtonJs();
		$this->printDeleteDocumentButtonJs();
		$this->printClickDocumentJs();
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$unlinkedversions = $this->params['unlinkedcontent'];
		$unlinkedfolders = $this->params['unlinkedfolders'];
		$unlinkeddocuments = $this->params['unlinkeddocuments'];
		$nofilesizeversions = $this->params['nofilesizeversions'];
		$nochecksumversions = $this->params['nochecksumversions'];
		$duplicateversions = $this->params['duplicateversions'];
		$processwithoutusergroup = $this->params['processwithoutusergroup'];
		$repair = $this->params['repair'];
		$unlink = $this->params['unlink'];
		$setfilesize = $this->params['setfilesize'];
		$setchecksum = $this->params['setchecksum'];
		$rootfolder = $this->params['rootfolder'];
		$this->enableClipboard = $this->params['enableclipboard'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		$previewer->setConverters($previewconverters);

		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/bootbox/bootbox.min.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("objectcheck"));

		if($repair) {
			echo "<div class=\"alert\">".getMLText('repairing_objects')."</div>";
		}
		$this->contentContainerStart();
		print "<table class=\"table table-condensed\">";
		print "<thead>\n<tr>\n";
		print "<th></th>\n";
		print "<th>".getMLText("name")."</th>\n";
		print "<th>".getMLText("status")."</th>\n";
		print "<th>".getMLText("action")."</th>\n";
		print "<th>".getMLText("error")."</th>\n";
		print "</tr>\n</thead>\n<tbody>\n";
		$this->needsrepair = false;
		$this->tree($dms, $folder, $previewer, $repair);
		print "</tbody></table>\n";
		$this->contentContainerEnd();

		if($this->needsrepair && $repair == 0) {
			echo '<p><a href="out.ObjectCheck.php?repair=1" class="btn btn-primary">'.getMLText('do_object_repair').'</a></p>';
		}

		if($unlinkedfolders) {
			$this->contentHeading(getMLText("unlinked_folders"));
			$this->contentContainerStart();
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("id")."</th>\n";
			print "<th>".getMLText("parent")."</th>\n";
			print "<th></th>\n";
			print "<th>".getMLText("error")."</th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($unlinkedfolders as $error) {
				echo "<tr>";
				echo "<td>".$error['name']."</td>";
				echo "<td>".$error['id']."</td>";
				echo "<td>".$error['parent']."</td>";
				echo "<td>".$error['msg']."</td>";
				echo "<td><a class=\"btn movefolder\" source=\"".$error['id']."\" dest=\"".$rootfolder->getID()."\" formtoken=\"".createFormKey('movefolder')."\">Move</a> </td>";
				echo "</tr>";
			}
			print "</tbody></table>\n";
			$this->contentContainerEnd();
		}

		if($unlinkeddocuments) {
			$this->contentHeading(getMLText("unlinked_documents"));
			$this->contentContainerStart();
			print "<table class=\"table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("id")."</th>\n";
			print "<th>".getMLText("parent")."</th>\n";
			print "<th>".getMLText("error")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($unlinkeddocuments as $error) {
				echo "<tr>";
				echo "<td>".$error['name']."</td>";
				echo "<td>".$error['id']."</td>";
				echo "<td>".$error['parent']."</td>";
				echo "<td>".$error['msg']."</td>";
				echo "<td><a class=\"btn movedocument\" source=\"".$error['id']."\" dest=\"".$rootfolder->getID()."\" formtoken=\"".createFormKey('movedocument')."\">Move</a> </td>";
				echo "</tr>";
			}
			print "</tbody></table>\n";
			$this->contentContainerEnd();
		}

		$this->contentHeading(getMLText("unlinked_content"));
		$this->contentContainerStart();
		if($unlink) {
			echo "<p>".getMLText('unlinking_objects')."</p>";
		}

		if($unlinkedversions) {
			print "<table class=\"table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("original_filename")."</th>\n";
			print "<th>".getMLText("mimetype")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($unlinkedversions as $version) {
				$doc = $version->getDocument();
				print "<tr><td>".$doc->getId()."</td><td>".$version->getVersion()."</td><td>".$version->getOriginalFileName()."</td><td>".$version->getMimeType()."</td>";
				if($unlink) {
					$doc->removeContent($version);
				}
				print "</tr>\n";
			}
			print "</tbody></table>\n";
			if($unlink == 0) {
				echo '<p><a href="out.ObjectCheck.php?unlink=1">'.getMLText('do_object_unlink').'</a></p>';
			}
		}

		$this->contentContainerEnd();

		$this->contentHeading(getMLText("missing_filesize"));
		$this->contentContainerStart();

		if($nofilesizeversions) {
			print "<table class=\"table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("original_filename")."</th>\n";
			print "<th>".getMLText("mimetype")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($nofilesizeversions as $version) {
				$doc = $version->getDocument();
				print "<tr><td>".$doc->getId()."</td><td>".$version->getVersion()."</td><td>".$version->getOriginalFileName()."</td><td>".$version->getMimeType()."</td>";
				echo "<td>";
				if($setfilesize) {
					if($version->setFileSize())
						echo getMLText('repaired');
				}
				echo "</td>";
				print "</tr>\n";
			}
			print "</tbody></table>\n";
			if($setfilesize == 0) {
				echo '<p><a href="out.ObjectCheck.php?setfilesize=1">'.getMLText('do_object_setfilesize').'</a></p>';
			}
		}

		$this->contentContainerEnd();

		$this->contentHeading(getMLText("missing_checksum"));
		$this->contentContainerStart();

		if($nochecksumversions) {
			print "<table class=\"table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("original_filename")."</th>\n";
			print "<th>".getMLText("mimetype")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($nochecksumversions as $version) {
				$doc = $version->getDocument();
				print "<tr><td>".$doc->getId()."</td><td>".$version->getVersion()."</td><td>".$version->getOriginalFileName()."</td><td>".$version->getMimeType()."</td>";
				echo "<td>";
				if($setchecksum) {
					if($version->setChecksum())
						echo getMLText('repaired');
				}
				echo "</td>";
				print "</tr>\n";
			}
			print "</tbody></table>\n";
			if($setchecksum == 0) {
				echo '<p><a href="out.ObjectCheck.php?setchecksum=1">'.getMLText('do_object_setchecksum').'</a></p>';
			}
		}

		$this->contentContainerEnd();

		$this->contentHeading(getMLText("duplicate_content"));
		$this->contentContainerStart();

		if($duplicateversions) {
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("original_filename")."</th>\n";
			print "<th>".getMLText("mimetype")."</th>\n";
			print "<th>".getMLText("duplicates")."</th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($duplicateversions as $rec) {
				$version = $rec['content'];
				$doc = $version->getDocument();
				print "<tr>";
				print "<td>".$doc->getId()."</td><td>".$version->getVersion()."</td><td>".$version->getOriginalFileName()."</td><td>".$version->getMimeType()."</td>";
				print "<td>";
				foreach($rec['duplicates'] as $duplicate) {
					$dupdoc = $duplicate->getDocument();
					print "<a href=\"../out/out.ViewDocument.php?documentid=".$dupdoc->getID()."\">".$dupdoc->getID()."/".$duplicate->getVersion()."</a>";
					echo "<br />";
				}
				print "</td>";
				print "</tr>\n";
			}
			print "</tbody></table>\n";
		}

		$this->contentContainerEnd();

		$this->contentHeading(getMLText("process_without_user_group"));
		$this->contentContainerStart();

		if($processwithoutusergroup) {
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("process")."</th>\n";
			print "<th>".getMLText("user_group")."</th>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("userid_groupid")."</th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach(array('review', 'approval') as $process) {
				foreach(array('user', 'group') as $ug) {
					if($processwithoutusergroup[$process][$ug]) {
						foreach($processwithoutusergroup[$process][$ug] as $rec) {
							print "<tr>";
							print "<td>".$process."</td>";
							print "<td>".$ug."</td>";
							print "<td><a href=\"../out/out.ViewDocument.php?documentid=".$rec['documentID']."\">".$rec['name']."</a></td><td>".$rec['version']."</td>";
							print "<td>".$rec['required']."</td>";
							print "</tr>\n";
						}
					}
				}
			}
			print "</tbody></table>\n";
		}

		$this->contentContainerEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
