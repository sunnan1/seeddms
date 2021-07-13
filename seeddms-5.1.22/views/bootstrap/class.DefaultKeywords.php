<?php
/**
 * Implementation of DefaultKeywords view
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
 * Class which outputs the html page for DefaultKeywords view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_DefaultKeywords extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
?>
function checkForm()
{
	msg = new Array();

	if($("#form .name").val() == "") msg.push("<?php printMLText("js_no_name");?>");
	if (msg != "")
	{
  	noty({
  		text: msg.join('<br />'),
  		type: 'error',
      dismissQueue: true,
  		layout: 'topRight',
  		theme: 'defaultTheme',
			_timeout: 1500,
  	});
		return false;
	}
	else
		return true;
}

function checkFormName()
{
	msg = new Array();

	if($(".formn .name").val() == "") msg.push("<?php printMLText("js_no_name");?>");
	if (msg != "")
	{
  	noty({
  		text: msg.join('<br />'),
  		type: 'error',
      dismissQueue: true,
  		layout: 'topRight',
  		theme: 'defaultTheme',
			_timeout: 1500,
  	});
		return false;
	}
	else
		return true;
}

function checkKeywordForm()
{
	msg = new Array();

	if($(".formk .keywords").val() == "") msg.push("<?php printMLText("js_no_name");?>");
	if (msg != "")
	{
  	noty({
  		text: msg.join('<br />'),
  		type: 'error',
      dismissQueue: true,
  		layout: 'topRight',
  		theme: 'defaultTheme',
			_timeout: 1500,
  	});
		return false;
	}
	else
		return true;
}

$(document).ready( function() {
	$('body').on('submit', '#form', function(ev){
		if(checkForm()) return;
		ev.preventDefault();
	});
	$('body').on('submit', '.formk', function(ev){
		if(checkKeywordForm()) return;
		ev.preventDefault();
	});
	$('body').on('submit', '.formn', function(ev){
		if(checkFormName()) return;
		ev.preventDefault();
	});
	$( "#selector" ).change(function() {
		$('div.ajax').trigger('update', {categoryid: $(this).val()});
	});
});
<?php
	} /* }}} */

	function actionmenu() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selcategoryid = $this->params['selcategoryid'];

		if($selcategoryid && $selcategoryid > 0) {
?>
						<form style="display: inline-block;" method="post" action="../op/op.DefaultKeywords.php" >
						<?php echo createHiddenFieldWithKey('removecategory'); ?>
						<input type="hidden" name="categoryid" value="<?php echo $selcategoryid?>">
						<input type="hidden" name="action" value="removecategory">
						<button class="btn btn-danger" type="submit"><i class="fa fa-remove"></i> <?php echo getMLText("rm_default_keyword_category")?></button>
						</form>
<?php
		}
	} /* }}} */

	function form() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$category = $dms->getKeywordCategory($this->params['selcategoryid']);

		$this->showKeywordForm($category, $user);
	} /* }}} */

	function showKeywordForm($category, $user) { /* {{{ */
		if(!$category) {
			$this->contentContainerStart();
?>
			
			<form class="form-horizontal" action="../op/op.DefaultKeywords.php" method="post" id="form">
  		<?php echo createHiddenFieldWithKey('addcategory'); ?>
			<input type="hidden" name="action" value="addcategory">
<?php
			$this->formField(
				getMLText("name"),
				array(
					'element'=>'input',
					'type'=>'text',
					'name'=>'name',
					'value'=>''
				)
			);
			$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('new_default_keyword_category'));
?>
			</form>
<?php
			$this->contentContainerEnd();
		} else {
			$this->contentContainerStart();
			$owner = $category->getOwner();
			if ((!$user->isAdmin()) && ($owner->getID() != $user->getID())) return;
?>
				<form class="form-horizontal form" action="../op/op.DefaultKeywords.php" method="post">
					<?php echo createHiddenFieldWithKey('editcategory'); ?>
					<input type="hidden" name="action" value="editcategory">
<?php
				$this->formField(
					getMLText("name"),
					array(
						'element'=>'input',
						'type'=>'text',
						'name'=>'name',
						'value'=>$category->getName()
					)
				);
				$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('save'));
?>
				</form>
<?php
			$this->contentContainerEnd();
			$this->contentHeading(getMLText("default_keywords"));
			$this->contentContainerStart();
?>
						<?php
							$lists = $category->getKeywordLists();
							if (count($lists) == 0)
								print getMLText("no_default_keywords");
							else
								foreach ($lists as $list) {
?>
									<form class="form-inline form" style="display: inline-block;" method="post" action="../op/op.DefaultKeywords.php">
  								<?php echo createHiddenFieldWithKey('editkeywords'); ?>
									<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
									<input type="Hidden" name="keywordsid" value="<?php echo $list["id"]?>">
									<input type="Hidden" name="action" value="editkeywords">
									<input name="keywords" class="keywords" type="text" value="<?php echo htmlspecialchars($list["keywords"]) ?>">
									<button class="btn btn-primary" title="<?php echo getMLText("save")?>"><i class="fa fa-save"></i> <?php echo getMLText("save")?></button>
									<!--	 <input name="action" value="removekeywords" type="Image" src="images/del.gif" title="<?php echo getMLText("delete")?>" border="0"> &nbsp; -->
									</form>
									<form style="display: inline-block;" method="post" action="../op/op.DefaultKeywords.php" >
  								<?php echo createHiddenFieldWithKey('removekeywords'); ?>
									<input type="hidden" name="categoryid" value="<?php echo $category->getID()?>">
									<input type="hidden" name="keywordsid" value="<?php echo $list["id"]?>">
									<input type="hidden" name="action" value="removekeywords">
									<button class="btn btn-danger" title="<?php echo getMLText("delete")?>"><i class="fa fa-remove"></i> <?php echo getMLText("delete")?></button>
									</form>
									<br>
						<?php }  ?>
				
				<div class="control-group">
					<label class="control-label"></label>
					<div class="controls">
					  <form class="form-inline formk" action="../op/op.DefaultKeywords.php" method="post">
  				  <?php echo createHiddenFieldWithKey('newkeywords'); ?>
						<input type="Hidden" name="action" value="newkeywords">
						<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
						<input type="text" class="keywords" name="keywords">
					  	<input type="submit" class="btn" value="<?php printMLText("new_default_keywords");?>">
						</form>
					</div>
				</div>

<?php
			$this->contentContainerEnd();
		}
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$categories = $this->params['categories'];
		$selcategoryid = $this->params['selcategoryid'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->contentHeading(getMLText("global_default_keywords"));
		$this->rowStart();
		$this->columnStart(4);
?>
<form class="form-horizontal">
			<select class="chzn-select" id="selector" class="input-xlarge">
		    <option value="-1"><?php echo getMLText("choose_category")?>
		    <option value="0"><?php echo getMLText("new_default_keyword_category")?>
<?php
				
		$selected=0;
		$count=2;				
		foreach ($categories as $category) {
		
			$owner = $category->getOwner();
			if ((!$user->isAdmin()) && ($owner->getID() != $user->getID())) continue;

			if ($selcategoryid && $category->getID()==$selcategoryid) $selected=$count;				
			print "<option value=\"".$category->getID()."\">" . htmlspecialchars($category->getName());
			$count++;
		}
?>
			</select>
</form>
	<div class="ajax" style="margin-bottom: 15px;" data-view="DefaultKeywords" data-action="actionmenu" <?php echo ($selcategoryid ? "data-query=\"categoryid=".$selcategoryid."\"" : "") ?>></div>
<?php
		$this->columnEnd();
		$this->columnStart(8);
?>
		<div class="ajax" data-view="DefaultKeywords" data-action="form" <?php echo ($selcategoryid ? "data-query=\"categoryid=".$selcategoryid."\"" : "") ?>></div>
		</div>
<?php
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
