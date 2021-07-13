<?php
/**
 * Implementation of Info view
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
 * Class which outputs the html page for Info view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Info extends SeedDMS_Bootstrap_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$version = $this->params['version'];
		$availversions = $this->params['availversions'];
		$extmgr = $this->params['extmgr'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		if($availversions) {
			$newversion = '';
			foreach($availversions as $availversion) {
				if($availversion[0] == 'stable')
					$newversion = $availversion[1];
			}
			if($newversion > $version->version()) {
				$this->warningMsg(getMLText('no_current_version', array('latestversion'=>$newversion)));
			}
		} else {
			$this->warningMsg(getMLText('no_version_check'));
		}

		$this->rowStart();
		$this->columnStart(6);
		$this->contentHeading(getMLText("seeddms_info"));
		$seedextensions = $extmgr->getExtensionConfiguration();
		echo "<table class=\"table table-condensed\">\n";
		echo "<thead>\n<tr>\n";
		echo "<th>".getMLText("name");
		echo "</th>\n";
		echo "</tr>\n</thead>\n<tbody>\n";
		$dbversion = $dms->getDBVersion();
		echo "<tr><td>".getMLText('seeddms_version')."</td><td>".$version->version()."</td></tr>\n";
		if($user->isAdmin()) {
			echo "<tr><td>".getMLText('database_schema_version')."</td><td>".$dbversion['major'].".".$dbversion['minor'].".".$dbversion['subminor']."</td></tr>\n";
			foreach($seedextensions as $extname=>$extconf)
				echo "<tr><td>".$extname."<br />".$extconf['title']."</td><td>".$extconf['version']."</td></tr>\n";
		}
		echo "</tbody>\n</table>\n";
		$this->columnEnd();
		$this->columnStart(6);
		if($user->isAdmin()) {
			$this->contentHeading(getMLText("php_info"));
			echo "<table class=\"table table-condensed\">\n";
			echo "<thead>\n<tr>\n";
			echo "<th>".getMLText("name");
			echo "</th>\n";
			echo "</tr>\n</thead>\n<tbody>\n";
			echo "<tr><td>PHP</td><td>".phpversion()."</td></tr>\n";
			echo "<tr><td>Path to php.ini</td><td>".php_ini_loaded_file()."</td></tr>\n";
			echo "</tbody>\n</table>\n";

			$this->contentHeading(getMLText("installed_php_extensions"));
			$phpextensions = get_loaded_extensions(false);
			echo "<table class=\"table table-condensed\">\n";
			echo "<thead>\n<tr>\n";
			echo "<th>".getMLText("name");
			echo "</th>\n";
			echo "</tr>\n</thead>\n<tbody>\n";
			foreach($phpextensions as $extname)
				echo "<tr><td>".$extname."</td><td>"."</td></tr>\n";
			echo "</tbody>\n</table>\n";

			$this->contentHeading(getMLText("missing_php_extensions"));
			echo "<table class=\"table table-condensed\">\n";
			echo "<thead>\n<tr>\n";
			echo "<th>".getMLText("name");
			echo "</th>\n";
			echo "</tr>\n</thead>\n<tbody>\n";
			$requiredext = array('zip', 'xml', 'xsl', 'json', 'intl', 'fileinfo', 'mbstring', 'curl');
			foreach(array_diff($requiredext, $phpextensions) as $extname)
				echo "<tr><td>".$extname."</td><td>"."</td></tr>\n";
			echo "</tbody>\n</table>\n";

			$this->contentHeading(getMLText("missing_php_functions"));
			echo "<table class=\"table table-condensed\">\n";
			echo "<thead>\n<tr>\n";
			echo "<th>".getMLText("name");
			echo "<th>".getMLText("missing_func_note");
			echo "</th>\n";
			echo "</tr>\n</thead>\n<tbody>\n";
			foreach(array('proc_open') as $funcname) {
				if(!function_exists($funcname)) {
					echo "<tr><td>".$funcname."</td><td>".getMLText('func_'.$funcname."_missing")."</td></tr>";
				}
			}
			echo "</tbody>\n</table>\n";

			if(function_exists('apache_get_modules')) {
				$this->contentHeading(getMLText("installed_apache_extensions"));
				$apacheextensions = apache_get_modules();
				echo "<table class=\"table table-condensed\">\n";
				echo "<thead>\n<tr>\n";
				echo "<th>".getMLText("name");
				echo "</th>\n";
				echo "</tr>\n</thead>\n<tbody>\n";
				foreach($apacheextensions as $extname)
					echo "<tr><td>".$extname."</td><td>"."</td></tr>\n";
				echo "</tbody>\n</table>\n";
			}
		}
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
