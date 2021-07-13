<?php
include("../inc/inc.Settings.php");

//include("../inc/inc.LogInit.php");
// LogInit.php cannot be used, because of the different log file
if($settings->_logFileEnable) {
	if ($settings->_logFileRotation=="h") $logname=date("YmdH", time());
	else if ($settings->_logFileRotation=="d") $logname=date("Ymd", time());
	else $logname=date("Ym", time());
	$logname = $settings->_contentDir."log/webdav-".$logname.".log";
	if(!file_exists($settings->_contentDir.'log'))
		@mkdir($settings->_contentDir.'log');
	if(file_exists($settings->_contentDir.'log') && is_dir($settings->_contentDir.'log')) {
		$logger = Log::factory('file', $logname);
		$logger->setMask(Log::MAX(PEAR_LOG_DEBUG));
	} else
		$logger = null;
} else {
	$logger = null;
}

require_once("../inc/inc.Language.php");
require_once("../inc/inc.Init.php");
require_once("../inc/inc.Extension.php");
require_once("../inc/inc.DBInit.php");
require_once("../inc/inc.ClassNotificationService.php");
require_once("../inc/inc.ClassEmailNotify.php");
require_once("../inc/inc.Notification.php");
require_once("../inc/inc.ClassController.php");

$notifier = new SeedDMS_NotificationService($logger);

if(isset($GLOBALS['SEEDDMS_HOOKS']['notification'])) {
	foreach($GLOBALS['SEEDDMS_HOOKS']['notification'] as $notificationObj) {
		if(method_exists($notificationObj, 'preAddService')) {
			$notificationObj->preAddService($dms, $notifier);
		}
	}
}

if($settings->_enableEmail) {
	$notifier->addService(new SeedDMS_EmailNotify($dms, $settings->_smtpSendFrom, $settings->_smtpServer, $settings->_smtpPort, $settings->_smtpUser, $settings->_smtpPassword));
}

if(isset($GLOBALS['SEEDDMS_HOOKS']['notification'])) {
	foreach($GLOBALS['SEEDDMS_HOOKS']['notification'] as $notificationObj) {
		if(method_exists($notificationObj, 'postAddService')) {
			$notificationObj->postAddService($dms, $notifier);
		}
	}
}

include("webdav.php");
$server = new HTTP_WebDAV_Server_SeedDMS();
$server->ServeRequest($dms, $logger, $notifier);
//$files = array();
//$options = array('path'=>'/Test1/subdir', 'depth'=>1);
//echo $server->MKCOL(&$options);

?>
