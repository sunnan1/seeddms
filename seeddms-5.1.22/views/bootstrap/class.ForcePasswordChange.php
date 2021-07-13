<?php
/**
 * Implementation of ForcePasswordChange view
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
 * Class which outputs the html page for ForcePasswordChange view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ForcePasswordChange extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
?>
function checkForm()
{
	msg = new Array();

	if($("#currentpwd").val() == "") msg.push("<?php printMLText("js_no_pwd");?>");
	if($("#pwd").val() == "") msg.push("<?php printMLText("js_no_pwd");?>");
	if($("#pwd").val() != $("#pwdconf").val()) msg.push("<?php printMLText("js_pwd_not_conf");?>");
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
	}
	else
		return true;
}

$(document).ready( function() {
	$('body').on('submit', '#form', function(ev){
		if(checkForm()) return;
		ev.preventDefault();
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$passwordstrength = $this->params['passwordstrength'];

		$this->htmlStartPage(getMLText("sign_in"), "forcepasswordchange");
		$this->globalBanner();
		$this->contentStart();
		$this->contentHeading(getMLText('password_expiration'));
		echo "<div class=\"alert\">".getMLText('password_expiration_text')."</div>";
		$this->contentContainerStart();
?>
<form class="form-horizontal" action="../op/op.EditUserData.php" method="post" id="form" name="form1">
	<?php echo createHiddenFieldWithKey('edituserdata'); ?>
<?php
		$this->formField(
			getMLText("current_password"),
			array(
				'element'=>'input',
				'type'=>'password',
				'id'=>'currentpwd',
				'name'=>'currentpwd',
				'autocomplete'=>'off',
				'required'=>true
			)
		);
		$this->formField(
			getMLText("new_password"),
			'<input class="pwd" type="password" rel="strengthbar" id="pwd" name="pwd" size="30">'
		);
		if($passwordstrength) {
			$this->formField(
				getMLText("password_strength"),
				'<div id="strengthbar" class="progress" style="width: 220px; height: 30px; margin-bottom: 8px;"><div class="bar bar-danger" style="width: 0%;"></div></div>'
			);
		}
		$this->formField(
			getMLText("confirm_pwd"),
			array(
				'element'=>'input',
				'type'=>'password',
				'id'=>'pwdconf',
				'name'=>'pwdconf',
				'autocomplete'=>'off',
			)
		);
		$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('submit_password'));
?>
<input type="hidden" name="fullname" value="<?php print htmlspecialchars($user->getFullName());?>" />
<input type="hidden" name="email" value="<?php print htmlspecialchars($user->getEmail());?>" />
<input type="hidden" name="comment" value="<?php print htmlspecialchars($user->getComment());?>" />
</form>

<?php
		$this->contentContainerEnd();
		$tmpfoot = array();
		$tmpfoot[] = "<a href=\"../op/op.Logout.php\">" . getMLText("logout") . "</a>\n";
		print "<p>";
		print implode(' | ', $tmpfoot);
		print "</p>\n";
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
