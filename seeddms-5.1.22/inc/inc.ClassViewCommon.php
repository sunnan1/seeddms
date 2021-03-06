<?php
/**
 * Implementation of view class
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

require_once "inc.ClassHook.php";

/**
 * Parent class for all view classes
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Common {
	protected $theme;

	protected $params;

	protected $baseurl;

	protected $imgpath;

	public function __construct($params, $theme='bootstrap') {
		$this->theme = $theme;
		$this->params = $params;
		$this->baseurl = '';
		$this->imgpath = '../views/'.$theme.'/images/';
	}

	public function __invoke($get=array()) {
		$this->callHook('preRun', isset($get['action']) ? $get['action'] : 'show');
		if(isset($get['action']) && $get['action']) {
			if(method_exists($this, $get['action'])) {
				$this->{$get['action']}();
			} else {
				echo "Missing action '".htmlspecialchars($get['action'])."'";
			}
		} else
			$this->show();
		$this->callHook('postRun', isset($get['action']) ? $get['action'] : 'show');
	}

	public function setParams($params) {
		$this->params = $params;
	}

	public function setParam($name, $value) {
		$this->params[$name] = $value;
	}

	public function getParam($name) {
		if(isset($this->params[$name]))
			return $this->params[$name];
		return null;
	}

	/**
	 * Check if the view has a parameter with the given name
	 *
	 * @param string $name name of parameter
	 * @return boolean true if parameter exists otherwise false
	 */
	function hasParam($name) {
		return isset($this->params[$name]) ? true : false;
	}

	public function unsetParam($name) {
		if(isset($this->params[$name]))
			unset($this->params[$name]);
	}

	public function setBaseUrl($baseurl) {
		$this->baseurl = $baseurl;
	}

	public function getTheme() {
		return $this->theme;
	}

	public function show() {
	}

	/**
	 * Call a hook with a given name
	 *
	 * Checks if a hook with the given name and for the current view
	 * exists and executes it. The name of the current view is taken
	 * from the current class name by lower casing the first char.
	 * This function will execute all registered hooks in the order
	 * they were registered.
	 *
	 * Attention: as func_get_arg() cannot handle references passed to the hook,
	 * callHook() should not be called if that is required. In that case get
	 * a list of hook objects with getHookObjects() and call the hooks yourself.
	 *
	 * @params string $hook name of hook
	 * @return string concatenated string, merged arrays or whatever the hook
	 * function returns
	 */
	public function callHook($hook) { /* {{{ */
		$tmps = array();
		$tmp = explode('_', get_class($this));
		$tmps[] = $tmp[2];
		foreach(class_parents($this) as $pc) {
			$tmp = explode('_', $pc);
			$tmps[] = $tmp[2];
		}
		/* Run array_unique() in case the parent class has the same suffix */
		$tmps = array_unique($tmps);
		$ret = null;
		foreach($tmps as $tmp)
		if(isset($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp)])) {
			foreach($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp)] as $hookObj) {
				if (method_exists($hookObj, $hook)) {
					switch(func_num_args()) {
						case 1:
							$tmpret = $hookObj->$hook($this);
							break;
						case 2:
							$tmpret = $hookObj->$hook($this, func_get_arg(1));
							break;
						case 3:
							$tmpret = $hookObj->$hook($this, func_get_arg(1), func_get_arg(2));
							break;
						case 4:
							$tmpret = $hookObj->$hook($this, func_get_arg(1), func_get_arg(2), func_get_arg(3));
							break;
						default:
						case 5:
							$tmpret = $hookObj->$hook($this, func_get_arg(1), func_get_arg(2), func_get_arg(3), func_get_arg(4));
							break;
					}
					if($tmpret !== null) {
						if(is_string($tmpret)) {
							$ret = ($ret === null) ? $tmpret : (is_string($ret) ? $ret.$tmpret : array_merge($ret, array($tmpret)));
						} elseif(is_array($tmpret) || is_object($tmpret)) {
							$ret = ($ret === null) ? $tmpret : (is_string($ret) ? array_merge(array($ret), $tmpret) : array_merge($ret, $tmpret));
						} else
							$ret = $tmpret;
					}
				}
			}
		}
		return $ret;
	} /* }}} */

	/**
	 * Return all hook objects for the given or calling class
	 *
	 * <code>
	 * <?php
	 * $hookObjs = $this->getHookObjects();
	 * foreach($hookObjs as $hookObj) {
	 *   if (method_exists($hookObj, $hook)) {
	 *     $ret = $hookObj->$hook($this, ...);
	 *     ...
	 *   }
	 * }
	 * ?>
	 * </code>
	 *
	 * @params string $classname name of class (current class if left empty)
	 * @return array list of hook objects registered for the class
	 */
	public function getHookObjects($classname='') { /* {{{ */
		if($classname)
			$tmp = explode('_', $classname);
		else
			$tmp = explode('_', get_class($this));
		if(isset($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp[2])])) {
			return $GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp[2])];
		}
		return array();
	} /* }}} */

	/**
	 * Check if a hook is registered
	 *
	 * @param $hook string name of hook
	 * @return mixed false if one of the hooks fails,
	 *               true if all hooks succedded,
	 *               null if no hook was called
	 */
	public function hasHook($hook) { /* {{{ */
		$tmps = array();
		$tmp = explode('_', get_class($this));
		$tmps[] = $tmp[2];
		$tmp = explode('_', get_parent_class($this));
		$tmps[] = $tmp[2];
		/* Run array_unique() in case the parent class has the same suffix */
		$tmps = array_unique($tmps);
		$ret = null;
		foreach($tmps as $tmp) {
			if(isset($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp)])) {
				foreach($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp)] as $hookObj) {
					if (method_exists($hookObj, $hook)) {
						return true;
					}
				}
			}
		}
		return false;
	} /* }}} */

	public function jsTranslations($keys) { /* {{{ */
		echo "var trans = {\n";
		foreach($keys as $key) {
			echo "	'".$key."': '".str_replace("'", "\\\'", getMLText($key))."',\n";
		}
		echo "};\n";
	} /* }}} */
}
