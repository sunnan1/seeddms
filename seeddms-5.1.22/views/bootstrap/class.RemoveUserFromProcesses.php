<?php
/**
 * Implementation of RemoveUserFromProcesses view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2017 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for RemoveUserFromProcesses view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2017 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_RemoveUserFromProcesses extends SeedDMS_Bootstrap_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$rmuser = $this->params['rmuser'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("rm_user_from_processes"));

?>
<div class="alert">
<?php printMLText("confirm_rm_user_from_processes", array ("username" => htmlspecialchars($rmuser->getFullName())));?>
</div>
<?php
		$this->contentContainerStart();
?>
<form class="form-horizontal" action="../op/op.UsrMgr.php" name="form1" method="post">
<input type="hidden" name="userid" value="<?php print $rmuser->getID();?>">
<input type="hidden" name="action" value="removefromprocesses">
<?php echo createHiddenFieldWithKey('removefromprocesses'); ?>

<?php
		$reviewStatus = $rmuser->getReviewStatus();
		$tmpr = array();
		$cr = array("-2"=>0, '-1'=>0, '0'=>0, '1'=>0);
		foreach($reviewStatus['indstatus'] as $ri) {
			$doc = $dms->getDocument($ri['documentID']);
			$ri['latest'] = $doc->getLatestContent()->getVersion();
			if($ri['latest'] == $ri['version'])
				$cr[$ri['status']]++;
			if(isset($tmpr[$ri['status']]))
				$tmpr[$ri['status']][] = $ri;
			else
				$tmpr[$ri['status']] = array($ri);
		}

		$approvalStatus = $rmuser->getApprovalStatus();
		$tmpa = array();
		$ca = array("-2"=>0, '-1'=>0, '0'=>0, '1'=>0);
		foreach($approvalStatus['indstatus'] as $ai) {
			$doc = $dms->getDocument($ai['documentID']);
			$ai['latest'] = $doc->getLatestContent()->getVersion();
			if($ai['latest'] == $ai['version'])
				$ca[$ai['status']]++;
			if(isset($tmpa[$ai['status']]))
				$tmpa[$ai['status']][] = $ai;
			else
				$tmpa[$ai['status']] = array($ai);
		}
?>
<?php if(isset($tmpr["0"])) { ?>
<?php
		$this->formField(
			getMLText('reviews_not_touched', array('no_reviews' => count($tmpr["0"]))),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'status[review][]',
				'value'=>'0',
				'checked'=>true
			),
			array(
				'field_wrap' => array('<label class="checkbox">', ' '.getMLText('reviews_not_touched_latest', array('no_reviews' => $cr["0"]))."</label>")
			)
		);
?>
<?php } ?>
<?php if(isset($tmpr["1"])) { ?>
<?php
		$this->formField(
			getMLText('reviews_accepted', array('no_reviews' => count($tmpr["1"]))),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'status[approval][]',
				'value'=>'1',
				'checked'=>true
			),
			array(
				'field_wrap' => array('<label class="checkbox">', ' '.getMLText('reviews_accepted_latest', array('no_reviews' => $cr["1"]))."</label>")
			)
		);
?>
<?php } ?>
<?php if(isset($tmpr["-1"])) { ?>
<?php
		$this->formField(
			getMLText('reviews_rejected', array('no_reviews' => count($tmpr["-1"]))),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'status[approval][]',
				'value'=>'-1',
				'checked'=>true
			),
			array(
				'field_wrap' => array('<label class="checkbox">', ' '.getMLText('reviews_rejected_latest', array('no_reviews' => $cr["-1"]))."</label>")
			)
		);
?>
<?php } ?>

<?php if(isset($tmpa["0"])) { ?>
<?php
		$this->formField(
			getMLText('approvals_not_touched', array('no_approvals' => count($tmpa["0"]))),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'status[approval][]',
				'value'=>'0',
				'checked'=>true
			),
			array(
				'field_wrap' => array('<label class="checkbox">', getMLText('approvals_not_touched_latest', array('no_approvals' => $ca["0"]))."</label>")
			)
		);
?>
<?php } ?>
<?php if(isset($tmpa["1"])) { ?>
<?php
		$this->formField(
			getMLText('approvals_accepted', array('no_approvals' => count($tmpa["1"]))),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'status[approval][]',
				'value'=>'1',
				'checked'=>true
			),
			array(
				'field_wrap' => array('<label class="checkbox">',getMLText('approvals_accepted_latest', array('no_approvals' => $ca["1"]))."</label>")
			)
		);
?>
<?php } ?>
<?php if(isset($tmpa["-1"])) { ?>
<?php
		$this->formField(
			getMLText('approvals_rejected', array('no_approvals' => count($tmpa["-1"]))),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'status[approval][]',
				'value'=>'-1',
				'checked'=>true
			),
			array(
				'field_wrap' => array('<label class="checkbox">', getMLText('approvals_rejected_latest', array('no_approvals' => $ca["-1"]))."</label>")
			)
		);
?>
<?php } ?>
<?php
		/*
		$options = array();
		$allUsers = $dms->getAllUsers($sortusersinlist);
		foreach ($allUsers as $currUser) {
			if (!$currUser->isGuest())
				$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin()), ($currUser->getID()==$user->getID()), array(array('data-subtitle', htmlspecialchars($currUser->getFullName()))));
		}
		$this->formField(
			getMLText("user"),
			array(
				'element'=>'select',
				'id'=>'newuser',
				'name'=>'newuserid',
				'class'=>'chzn-select',
				'options'=>$options
			)
		);
		 */
		$this->formSubmit("<i class=\"fa fa-remove\"></i> ".getMLText('rm_user_from_processes'));
?>

</form>
<?php
		$this->contentContainerEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
