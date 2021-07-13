<?php
/**
 * Implementation of notification service
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2016 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Implementation of notification service
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2016 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_NotificationService {
	/**
	 * List of services for sending notification
	 */
	protected $services;

	/*
	 * List of servives with errors
	 */
	protected $errors;

	/*
	 * Service for logging
	 */
	protected $logger;

	/*
	 * Possible types of receivers
	 */
	const RECV_ANY = 0;
	const RECV_NOTIFICATION = 1;
	const RECV_OWNER = 2;
	const RECV_REVIEWER = 3;
	const RECV_APPROVER = 4;
	const RECV_WORKFLOW = 5;

	public function __construct($logger = null) {
		$this->services = array();
		$this->errors = array();
		$this->logger = $logger;
	}

	public function addService($service, $name='') {
		if(!$name)
			$name = md5(uniqid());
		$this->services[$name] = $service;
		$this->errors[$name] = true;
	}

	public function getServices() {
		return $this->services;
	}

	public function getErrors() {
		return $this->errors;
	}

	public function toIndividual($sender, $recipient, $subject, $message, $params=array(), $recvtype=0) {
		$error = true;
		foreach($this->services as $name => $service) {
			/* Set $to to email address of user or the string passed in $recipient
			 * This is only used for logging
			 */
			if(is_object($recipient) && $recipient->isType('user') && !$recipient->isDisabled() && $recipient->getEmail()!="") {
				$to = $recipient->getEmail();
			} elseif(is_string($recipient) && trim($recipient) != "") {
				$to = $recipient;
			} else {
				$to = '';
			}

			/* Call filter of notification service if set */
			if(!is_callable([$service, 'filter']) || $service->filter($sender, $recipient, $subject, $message, $params, $recvtype)) {
				if(!$service->toIndividual($sender, $recipient, $subject, $message, $params)) {
					$error = false;
					$this->errors[$name] = false;
					$this->logger->log('Notification service \''.$name.'\': Sending notification \''.$subject.'\' to user \''.$to.'\' ('.$recvtype.') failed.', PEAR_LOG_ERR);
				} else {
					$this->logger->log('Notification service \''.$name.'\': Sending notification \''.$subject.'\' to user \''.$to.'\' ('.$recvtype.') successful.', PEAR_LOG_INFO);
					$this->errors[$name] = true;
				}
			} else {
				$this->logger->log('Notification service \''.$name.'\': Notification \''.$subject.'\' to user \''.$to.'\' ('.$recvtype.') filtered out.', PEAR_LOG_INFO);
			}
		}
		return $error;
	}

	/**
	 * Send a notification to each user of a group
	 *
	 */
	public function toGroup($sender, $groupRecipient, $subject, $message, $params=array(), $recvtype=0) {
		$error = true;
		foreach($this->services as $name => $service) {
			$ret = true;
			foreach ($groupRecipient->getUsers() as $recipient) {
				$ret &= $this->toIndividual($sender, $recipient, $subject, $message, $params, $recvtype);
			}
			$this->errors[$name] = $ret;
			if(!$ret) {
				$error = false;
			}
		}
		return $error;
	}

	/**
	 * Send a notification to a list of recipients
	 *
	 * The list of recipients may contain both email addresses and users
	 *
	 * @param string|object $sender either an email address or a user
	 * @param array $recipients list of recipients
	 * @param string $subject key of translatable phrase for the subject
	 * @param string $message key of translatable phrase for the message body
	 * @param array $params list of parameters filled into the subject and body
	 * @param int $recvtype type of receiver
	 * @return boolean true on success, otherwise false
	 */
	public function toList($sender, $recipients, $subject, $message, $params=array(), $recvtype=0) {
		$error = true;
		foreach($this->services as $name => $service) {
			$ret = true;
			foreach ($recipients as $recipient) {
				$ret &= $this->toIndividual($sender, $recipient, $subject, $message, $params, $recvtype);
			}
			$this->errors[$name] = $ret;
			if(!$ret) {
				$error = false;
			}
		}
		return $error;
	}

}

