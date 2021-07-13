<?php
/**
 * Implementation of GroupMgr view
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
 * Class which outputs the html page for GroupMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_GroupMgr extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		$selgroup = $this->params['selgroup'];
		$strictformcheck = $this->params['strictformcheck'];

		header('Content-Type: application/javascript; charset=UTF-8');
?>
function checkForm1() {
	msg = new Array();
	
	if($("#name").val() == "") msg.push("<?php printMLText("js_no_name");?>");
<?php
	if ($strictformcheck) {
?>
	if($("#comment").val() == "") msg.push("<?php printMLText("js_no_comment");?>");
<?php
	}
?>
	if (msg != "") {
  	noty({
  		text: msg.join('<br />'),
  		type: 'error',
      dismissQueue: true,
  		layout: 'topRight',
  		theme: 'defaultTheme',
			_timeout: 1500,
  	});
		return false;
	} else
		return true;
}

function checkForm2() {
	msg = "";
	
		if($("#userid").val() == -1) msg += "<?php printMLText("js_select_user");?>\n";

		if (msg != "") {
			noty({
				text: msg,
				type: 'error',
				dismissQueue: true,
				layout: 'topRight',
				theme: 'defaultTheme',
				_timeout: 1500,
			});
			return false;
		} else
			return true;
	}

$(document).ready( function() {
	$('body').on('submit', '#form_1', function(ev){
		if(checkForm1())
			return;
		ev.preventDefault();
	});

	$('body').on('submit', '#form_2', function(ev){
		if(checkForm2())
			return;
		ev.preventDefault();
	});

	$( "#selector" ).change(function() {
		$('div.ajax').trigger('update', {groupid: $(this).val()});
		window.history.pushState({"html":"","pageTitle":""},"", '../out/out.GroupMgr.php?groupid=' + $(this).val());
	});
});
<?php
	} /* }}} */

	function info() { /* {{{ */
		$dms = $this->params['dms'];
		$selgroup = $this->params['selgroup'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$workflowmode = $this->params['workflowmode'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		if($selgroup) {
			$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
			$this->contentHeading(getMLText("group_info"));
			echo "<table class=\"table table-condensed\">\n";
			if($workflowmode == "traditional") {
				$reviewstatus = $selgroup->getReviewStatus();
				$i = 0;
				foreach($reviewstatus as $rv) {
					if($rv['status'] == 0) {
						$i++;
					}
				}
				echo "<tr><td>".getMLText('pending_reviews')."</td><td>".$i."</td></tr>";
			}
			if($workflowmode == "traditional" || $workflowmode == 'traditional_only_approval') {
				$approvalstatus = $selgroup->getApprovalStatus();
				$i = 0;
				foreach($approvalstatus as $rv) {
					if($rv['status'] == 0) {
						$i++;
					}
				}
				echo "<tr><td>".getMLText('pending_approvals')."</td><td>".$i."</td></tr>";
			}
			if($workflowmode == 'advanced') {
				$workflowStatus = $selgroup->getWorkflowStatus();
				if($workflowStatus)
					echo "<tr><td>".getMLText('pending_workflows')."</td><td>".count($workflowStatus)."</td></tr>\n";
			}
			echo "</table>";
		}
	} /* }}} */

	function actionmenu() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selgroup = $this->params['selgroup'];

		if($selgroup) {
?>
<div class="btn-group">
  <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
		<?php echo getMLText('action'); ?>
    <span class="caret"></span>
  </a>
  <ul class="dropdown-menu">
<?php
			echo '<li><a href="../out/out.RemoveGroup.php?groupid='.$selgroup->getID().'"><i class="fa fa-remove"></i> '.getMLText("rm_group").'</a><li>';
			if($selgroup->getUsers())
				echo '<li><a href="../op/op.UserListCsv.php?groupid='.$selgroup->getID().'"><i class="fa fa-download"></i> '.getMLText("export_user_list_csv").'</a><li>';
?>
	</ul>
</div>
<?php
		}
	} /* }}} */

	function showGroupForm($group) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$allUsers = $this->params['allusers'];
		$groups = $this->params['allgroups'];
?>
	<form class="form-horizontal" action="../op/op.GroupMgr.php" name="form_1" id="form_1" method="post">
<?php
		if($group) {
			echo createHiddenFieldWithKey('editgroup');
?>
	<input type="hidden" name="groupid" value="<?php print $group->getID();?>">
	<input type="hidden" name="action" value="editgroup">
<?php
		} else {
			echo createHiddenFieldWithKey('addgroup');
?>
	<input type="hidden" name="action" value="addgroup">
<?php
		}
		$this->formField(
			getMLText("name"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'name',
				'name'=>'name',
				'value'=>($group ? htmlspecialchars($group->getName()) : '')
			)
		);
		$this->formField(
			getMLText("comment"),
			array(
				'element'=>'textarea',
				'id'=>'comment',
				'name'=>'comment',
				'rows'=>4,
				'value'=>($group ? htmlspecialchars($group->getComment()) : '')
			)
		);
		$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('save'));
?>
	</form>
<?php
		if($group) {
			$this->contentSubHeading(getMLText("group_members"));
?>
		<table class="table-condensed">
<?php
			$members = $group->getUsers();
			if (count($members) == 0)
				print "<tr><td>".getMLText("no_group_members")."</td></tr>";
			else {
			
				foreach ($members as $member) {
				
					print "<tr>";
					print "<td><i class=\"fa fa-user\"></i></td>";
					print "<td>" . htmlspecialchars($member->getFullName()) . "</td>";
					print "<td>" . ($group->isMember($member,true)?getMLText("manager"):"&nbsp;") . "</td>";
					print "<td>";
					print "<form action=\"../op/op.GroupMgr.php\" method=\"post\" class=\"form-inline\" style=\"display: inline-block; margin-bottom: 0px;\"><input type=\"hidden\" name=\"action\" value=\"rmmember\" /><input type=\"hidden\" name=\"groupid\" value=\"".$group->getID()."\" /><input type=\"hidden\" name=\"userid\" value=\"".$member->getID()."\" />".createHiddenFieldWithKey('rmmember')."<button type=\"submit\" class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> ".getMLText("delete")."</button></form>";
					print "&nbsp;";
					print "<form action=\"../op/op.GroupMgr.php\" method=\"post\" class=\"form-inline\" style=\"display: inline-block; margin-bottom: 0px;\"><input type=\"hidden\" name=\"groupid\" value=\"".$group->getID()."\" /><input type=\"hidden\" name=\"action\" value=\"tmanager\" /><input type=\"hidden\" name=\"userid\" value=\"".$member->getID()."\" />".createHiddenFieldWithKey('tmanager')."<button type=\"submit\" class=\"btn btn-mini\"><i class=\"fa fa-random\"></i> ".getMLText("toggle_manager")."</button></form>";
					print "</td></tr>";
				}
			}
?>
		</table>
		
<?php
			$this->contentSubHeading(getMLText("add_member"));
?>
		
		<form class="form-horizontal" action="../op/op.GroupMgr.php" method="POST" name="form_2" id="form_2">
		<?php echo createHiddenFieldWithKey('addmember'); ?>
		<input type="Hidden" name="action" value="addmember">
		<input type="Hidden" name="groupid" value="<?php print $group->getID();?>">
<?php
		$options = array();
		$allUsers = $dms->getAllUsers($sortusersinlist);
		foreach ($allUsers as $currUser) {
			if (!$group->isMember($currUser))
				$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin().' - '.$currUser->getFullName()), ($currUser->getID()==$user->getID()), array(array('data-subtitle', htmlspecialchars($currUser->getEmail()))));
		}
		$this->formField(
			getMLText("user"),
			array(
				'element'=>'select',
				'id'=>'userid',
				'name'=>'userid',
				'class'=>'chzn-select',
				'options'=>$options
			)
		);
		$this->formField(
			getMLText("manager"),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'manager',
				'value'=>1
			)
		);
		$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('add'));
?>
		</form>
<?php
		}
	} /* }}} */

	function form() { /* {{{ */
		$selgroup = $this->params['selgroup'];

		$this->showGroupForm($selgroup);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selgroup = $this->params['selgroup'];
		$allUsers = $this->params['allusers'];
		$allGroups = $this->params['allgroups'];
		$strictformcheck = $this->params['strictformcheck'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->contentHeading(getMLText("group_management"));
		$this->rowStart();
		$this->columnStart(4);
?>
<form class="form-horizontal">
<?php
		$options = array();
		$options[] = array("-1", getMLText("choose_group"));
		$options[] = array("0", getMLText("add_group"));
		foreach ($allGroups as $group) {
			$options[] = array($group->getID(), htmlspecialchars($group->getName()), $selgroup && $group->getID()==$selgroup->getID());
		}
		$this->formField(
			null, //getMLText("selection"),
			array(
				'element'=>'select',
				'id'=>'selector',
				'class'=>'chzn-select',
				'options'=>$options,
				'placeholder'=>getMLText('select_groups'),
			)
		);
?>
</form>
	<div class="ajax" style="margin-bottom: 15px;" data-view="GroupMgr" data-action="actionmenu" <?php echo ($selgroup ? "data-query=\"groupid=".$selgroup->getID()."\"" : "") ?>></div>
	<div class="ajax" data-view="GroupMgr" data-action="info" <?php echo ($selgroup ? "data-query=\"groupid=".$selgroup->getID()."\"" : "") ?>></div>
<?php
		$this->columnEnd();
		$this->columnStart(8);
		$this->contentContainerStart();
?>
		<div class="ajax" data-view="GroupMgr" data-action="form" <?php echo ($selgroup ? "data-query=\"groupid=".$selgroup->getID()."\"" : "") ?>></div>
<?php
		$this->contentContainerEnd();
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
