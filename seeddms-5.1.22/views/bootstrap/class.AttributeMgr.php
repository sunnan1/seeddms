<?php
/**
 * Implementation of AttributeMgr view
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
 * Class which outputs the html page for AttributeMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_AttributeMgr extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		$selattrdef = $this->params['selattrdef'];
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('cancel', 'splash_move_document', 'confirm_move_document', 'move_document', 'confirm_transfer_link_document', 'transfer_content', 'link_document', 'splash_move_folder', 'confirm_move_folder', 'move_folder'));
?>

$(document).ready( function() {
	$('body').on('submit', '#form', function(ev){
//		if(checkForm()) return;
//		ev.preventDefault();
	});
	$( "#selector" ).change(function() {
		$('div.ajax').trigger('update', {attrdefid: $(this).val()});
		window.history.pushState({"html":"","pageTitle":""},"", '../out/out.AttributeMgr.php?attrdefid=' + $(this).val());
	});
});
<?php
		$this->printDeleteFolderButtonJs();
		$this->printDeleteDocumentButtonJs();
		$this->printDeleteAttributeValueButtonJs();
		$this->printClickDocumentJs();
		$this->printClickFolderJs();
	} /* }}} */

	function info() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$attrdefs = $this->params['attrdefs'];
		$selattrdef = $this->params['selattrdef'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$enableRecursiveCount = $this->params['enableRecursiveCount'];
		$maxRecursiveCount = $this->params['maxRecursiveCount'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		if($selattrdef) {
			$this->contentHeading(getMLText("attrdef_info"));
			$res = $selattrdef->getStatistics(30);
			if(!empty($res['frequencies']['document']) ||!empty($res['frequencies']['folder']) ||!empty($res['frequencies']['content'])) {
				foreach(array('document', 'folder', 'content') as $type) {
					$content = '';
					if(isset($res['frequencies'][$type]) && $res['frequencies'][$type]) {
						$content .= "<table class=\"table table-condensed\">";
						$content .= "<thead>\n<tr>\n";
						$content .= "<th>".getMLText("attribute_value")."</th>\n";
						$content .= "<th>".getMLText("attribute_count")."</th>\n";
						$content .= "<th></th>\n";
						$content .= "</tr></thead>\n<tbody>\n";
						$separator = $selattrdef->getValueSetSeparator();
						foreach($res['frequencies'][$type] as $entry) {
							$value = $selattrdef->parseValue($entry['value']);
							$content .= "<tr>";
							$content .= "<td>".htmlspecialchars(implode('<span style="color: #aaa;">'.($separator ? ' '.$separator.' ' : ' ; ').'</span>', $value))."</td>";
							$content .= "<td><a href=\"../out/out.Search.php?resultmode=".($type == 'folder' ? 2 : ($type == 'document' ? 1 : 3))."&attributes[".$selattrdef->getID()."]=".urlencode($entry['value'])."\">".urlencode($entry['c'])."</a></td>";
							$content .= "<td>";
							/* various checks, if the value is valid */
							if(!$selattrdef->validate($entry['value'])) {
								$content .= getAttributeValidationText($selattrdef->getValidationError(), $selattrdef->getName(), $entry['value'], $selattrdef->getRegex());
							}
							/* Check if value is in value set */
							/*
							if($selattrdef->getValueSet()) {
								foreach($value as $v) {
									if(!in_array($v, $selattrdef->getValueSetAsArray()))
										$content .= getMLText("attribute_value_not_in_valueset");
								}
							}
							 */
							$content .= "</td>";
							$content .= "<td>";
							$content .= "<div class=\"list-action\">";
							if($user->isAdmin()) {
								$content .= $this->printDeleteAttributeValueButton($selattrdef, implode(';', $value), 'splash_rm_attr_value', true);
							} else {
								$content .= '<span style="padding: 2px; color: #CCC;"><i class="fa fa-remove"></i></span>';
							}
							$content .= "</div>";
							$content .= "</td>";
							$content .= "</tr>";
						}
						$content .= "</tbody></table>";
					}
					if($content)
						$this->printAccordion(getMLText('attribute_value')." (".getMLText($type).")", $content);
				}
			}

			if($res['folders'] || $res['docs']) {
				print "<table id=\"viewfolder-table\" class=\"table table-condensed\">";
				print "<thead>\n<tr>\n";
				print "<th></th>\n";	
				print "<th>".getMLText("name")."</th>\n";
				print "<th>".getMLText("status")."</th>\n";
				print "<th>".getMLText("action")."</th>\n";
				print "</tr>\n</thead>\n<tbody>\n";
				foreach($res['folders'] as $subFolder) {
					echo $this->folderListRow($subFolder);
				}
				$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
				foreach($res['docs'] as $document) {
					echo $this->documentListRow($document, $previewer);
				}

				echo "</tbody>\n</table>\n";
			}

			if($res['contents']) {
				print "<table id=\"viewfolder-table\" class=\"table\">";
				print "<thead>\n<tr>\n";
				print "<th></th>\n";	
				print "<th>".getMLText("name")."</th>\n";
				print "<th>".getMLText("status")."</th>\n";
				print "<th>".getMLText("action")."</th>\n";
				print "</tr>\n</thead>\n<tbody>\n";
				$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
				foreach($res['contents'] as $content) {
					$doc = $content->getDocument();
					echo $this->documentListRow($doc, $previewer);
				}
				print "</tbody></table>";
			}
		}
	} /* }}} */

	function actionmenu() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selattrdef = $this->params['selattrdef'];

		if($selattrdef && !$selattrdef->isUsed()) {
?>
			<form style="display: inline-block;" method="post" action="../op/op.AttributeMgr.php" >
				<?php echo createHiddenFieldWithKey('removeattrdef'); ?>
				<input type="hidden" name="attrdefid" value="<?php echo $selattrdef->getID()?>">
				<input type="hidden" name="action" value="removeattrdef">
				<button type="submit" class="btn"><i class="fa fa-remove"></i> <?php echo getMLText("rm_attrdef")?></button>
			</form>
<?php
		}
	} /* }}} */

	function showAttributeForm($attrdef) { /* {{{ */
?>
			<form class="form-horizontal" action="../op/op.AttributeMgr.php" method="post">
<?php
		if($attrdef) {
			echo createHiddenFieldWithKey('editattrdef');
?>
			<input type="hidden" name="action" value="editattrdef">
			<input type="hidden" name="attrdefid" value="<?php echo $attrdef->getID()?>" />
<?php
		} else {
			echo createHiddenFieldWithKey('addattrdef');
?>
			<input type="hidden" name="action" value="addattrdef">
<?php
		}
		$this->formField(
			getMLText("attrdef_name"),
			array(
				'element'=>'input',
				'type'=>'text',
				'name'=>'name',
				'value'=>($attrdef ? htmlspecialchars($attrdef->getName()) : '')
			)
		);
		$options = array();
		$options[] = array(SeedDMS_Core_AttributeDefinition::objtype_all, getMLText('all'));
		$options[] = array(SeedDMS_Core_AttributeDefinition::objtype_folder, getMLText('folder'), $attrdef && $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_folder);
		$options[] = array(SeedDMS_Core_AttributeDefinition::objtype_document, getMLText('document'), $attrdef && $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_document);
		$options[] = array(SeedDMS_Core_AttributeDefinition::objtype_documentcontent, getMLText('documentcontent'), $attrdef && $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_documentcontent);
		$this->formField(
			getMLText("attrdef_objtype"),
			array(
				'element'=>'select',
				'name'=>'objtype',
				'options'=>$options
			)
		);
		$options = array();
		$options[] = getMLText('types_generic');
		$options[] = array(SeedDMS_Core_AttributeDefinition::type_int, getMLText('attrdef_type_int'), $attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_int);
		$options[] = array(SeedDMS_Core_AttributeDefinition::type_float, getMLText('attrdef_type_float'), $attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_float);
		$options[] = array(SeedDMS_Core_AttributeDefinition::type_string, getMLText('attrdef_type_string'), $attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_string);
		$options[] = array(SeedDMS_Core_AttributeDefinition::type_boolean, getMLText('attrdef_type_boolean'), $attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_boolean);
		$options[] = array(SeedDMS_Core_AttributeDefinition::type_date, getMLText('attrdef_type_date'), $attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_date);
		$options[] = array(SeedDMS_Core_AttributeDefinition::type_email, getMLText('attrdef_type_email'), $attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_email);
		$options[] = array(SeedDMS_Core_AttributeDefinition::type_url, getMLText('attrdef_type_url'), $attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_url);
		$options[] = 'SeedDMS';
		$options[] = array(SeedDMS_Core_AttributeDefinition::type_folder, getMLText('attrdef_type_folder'), $attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_folder);
		$options[] = array(SeedDMS_Core_AttributeDefinition::type_document, getMLText('attrdef_type_document'), $attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_document);
		$options[] = array(SeedDMS_Core_AttributeDefinition::type_user, getMLText('attrdef_type_user'), $attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_user);
		$options[] = array(SeedDMS_Core_AttributeDefinition::type_group, getMLText('attrdef_type_group'), $attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_group);
		if($moreoptions = $this->callHook('additionalTypes', $attrdef)) {
			foreach($moreoptions as $option) {
				if(is_string($option))
					$options[] = $option;
				elseif(is_array($option))
					$options[] = array((int) $option['value'], $option['name'], $attrdef && $attrdef->getType() == $option['value']);
			}
		}
		$this->formField(
			getMLText("attrdef_type"),
			array(
				'element'=>'select',
				'name'=>'type',
				'options'=>$options
			)
		);
		$this->formField(
			getMLText("attrdef_multiple"),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'multiple',
				'value'=>1,
				'checked'=>($attrdef && $attrdef->getMultipleValues())
			)
		);
		$this->formField(
			getMLText("attrdef_minvalues"),
			array(
				'element'=>'input',
				'type'=>'text',
				'name'=>'minvalues',
				'value'=>($attrdef ? $attrdef->getMinValues() : ''),
			),
			['help'=>getMLText('attrdef_minvalues_help')]
		);
		$this->formField(
			getMLText("attrdef_maxvalues"),
			array(
				'element'=>'input',
				'type'=>'text',
				'name'=>'maxvalues',
				'value'=>($attrdef ? $attrdef->getMaxValues() : ''),
			)
		);
		$this->formField(
			getMLText("attrdef_valueset"),
			(($attrdef && strlen($attrdef->getValueSet()) > 30)
			? array(
				'element'=>'textarea',
				'name'=>'valueset',
				'rows'=>5,
				'value'=>(($attrdef && $attrdef->getValueSet()) ? $attrdef->getValueSetSeparator().implode("\n".$attrdef->getValueSetSeparator(), $attrdef->getValueSetAsArray()) : ''),
			)
			: array(
				'element'=>'input',
				'type'=>'text',
				'name'=>'valueset',
				'value'=>($attrdef ? $attrdef->getValueSet() : ''),
			)),
			['help'=>getMLText('attrdef_valueset_help')]
		);
		$this->formField(
			getMLText("attrdef_regex"),
			array(
				'element'=>'input',
				'type'=>'text',
				'name'=>'regex',
				'value'=>($attrdef ? $attrdef->getRegex() : ''),
			),
			['help'=>getMLText('attrdef_regex_help')]
		);
		$this->formSubmit('<i class="fa fa-save"></i> '.getMLText('save'));
?>
			</form>
<?php
} /* }}} */

	function form() { /* {{{ */
		$selattrdef = $this->params['selattrdef'];

		$this->showAttributeForm($selattrdef);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$attrdefs = $this->params['attrdefs'];
		$selattrdef = $this->params['selattrdef'];

		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/bootbox/bootbox.min.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("attrdef_management"));
		$this->rowStart();
		$this->columnStart(6);
?>
<form class="form-horizontal">
	<select class="chzn-select" id="selector" class="input-xlarge">
		<option value="-1"><?php echo getMLText("choose_attrdef")?></option>
		<option value="0"><?php echo getMLText("new_attrdef")?></option>
<?php
		if($attrdefs) {
			foreach ($attrdefs as $attrdef) {
				switch($attrdef->getObjType()) {
					case SeedDMS_Core_AttributeDefinition::objtype_all:
						$ot = getMLText("all");
						break;
					case SeedDMS_Core_AttributeDefinition::objtype_folder:
						$ot = getMLText("folder");
						break;
					case SeedDMS_Core_AttributeDefinition::objtype_document:
						$ot = getMLText("document");
						break;
					case SeedDMS_Core_AttributeDefinition::objtype_documentcontent:
						$ot = getMLText("documentcontent");
						break;
				}
				switch($attrdef->getType()) {
					case SeedDMS_Core_AttributeDefinition::type_int:
						$t = getMLText("attrdef_type_int");
						break;
					case SeedDMS_Core_AttributeDefinition::type_float:
						$t = getMLText("attrdef_type_float");
						break;
					case SeedDMS_Core_AttributeDefinition::type_string:
						$t = getMLText("attrdef_type_string");
						break;
					case SeedDMS_Core_AttributeDefinition::type_boolean:
						$t = getMLText("attrdef_type_boolean");
						break;
					case SeedDMS_Core_AttributeDefinition::type_date:
						$t = getMLText("attrdef_type_date");
						break;
					case SeedDMS_Core_AttributeDefinition::type_email:
						$t = getMLText("attrdef_type_email");
						break;
					case SeedDMS_Core_AttributeDefinition::type_url:
						$t = getMLText("attrdef_type_url");
						break;
					case SeedDMS_Core_AttributeDefinition::type_boolean:
						$t = getMLText("attrdef_type_boolean");
						break;
					case SeedDMS_Core_AttributeDefinition::type_folder:
						$t = getMLText("attrdef_type_folder");
						break;
					case SeedDMS_Core_AttributeDefinition::type_document:
						$t = getMLText("attrdef_type_document");
						break;
					case SeedDMS_Core_AttributeDefinition::type_user:
						$t = getMLText("attrdef_type_user");
						break;
					case SeedDMS_Core_AttributeDefinition::type_group:
						$t = getMLText("attrdef_type_group");
						break;
				}
				print "<option value=\"".$attrdef->getID()."\" ".($selattrdef && $attrdef->getID()==$selattrdef->getID() ? 'selected' : '')." data-subtitle=\"".htmlspecialchars($ot.", ".$t)."\">" . htmlspecialchars($attrdef->getName()/* ." (".$ot.", ".$t.")"*/);
			}
		}
?>
	</select>
</form>
	<div class="ajax" style="margin-bottom: 15px;" data-view="AttributeMgr" data-action="actionmenu" <?php echo ($selattrdef ? "data-query=\"attrdefid=".$selattrdef->getID()."\"" : "") ?>></div>
	<div class="ajax" data-view="AttributeMgr" data-action="info" <?php echo ($selattrdef ? "data-query=\"attrdefid=".$selattrdef->getID()."\"" : "") ?>></div>
<?php
		$this->columnEnd();
		$this->columnStart(6);
?>
	<?php	$this->contentContainerStart(); ?>
		<div class="ajax" data-view="AttributeMgr" data-action="form" <?php echo ($selattrdef ? "data-query=\"attrdefid=".$selattrdef->getID()."\"" : "") ?>></div>
<?php
		$this->contentContainerEnd();
		$this->columnEnd();
		$this->rowEnd();

		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
