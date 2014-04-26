<?
	/**
	 * This file houses the app_controller class, which draws its power from base_controller. All controllers
	 * should extend app_controller.
	 * 
	 * 
	 * Copyright (c) 2009, Lyon Bros Enterprises, LLC. (http://www.lyonbros.com)
	 * 
	 * Licensed under The MIT License. 
	 * Redistributions of files must retain the above copyright notice.
	 * 
	 * @copyright	Copyright (c) 2009, Lyon Bros Enterprises, LLC. (http://www.lyonbros.com)
	 * @package		aframe
	 * @subpackage	aframe.skeleton
	 * @license		http://www.opensource.org/licenses/mit-license.php
	 */
	
	/**
	 * App controller class.
	 * 
	 * Abstracts out application-specific functionality from the base controller.
	 * 
	 * @package		aframe
	 * @subpackage	aframe.skeleton
	 */
	class app_controller extends base_controller
	{
		/**
		 * Init function.
		 * 
		 * This function is called on every controller start, and can be used for any app-specific
		 * object initialization needed.
		 */
		function init()
		{
			// Should always call parent's init
			parent::init();

			date_default_timezone_set('America/Los_Angeles');

			$this->file_handler	=	&$this->event->object('file_handler', array(&$this->event));

			$this->layout('default');
		}

		function output($object, $raw = false)
		{

			header("Content-Type: application/json");			
			$has_errors	=	$this->event->get('app_has_errors');

			return output::do_output($object, $raw, $has_errors);
		}

		function throw_error($error_code, $message)
		{
			app_controller::ThrowError($error_code, $message);
		}

		static function ThrowError($code, $message, $severity = ERROR_SEVERITY_FATAL)
		{
			$error	=	array(
				'code'		=>	$code,
				'severity'	=>	$severity,
				'msg'		=>	$message
			);

			error_log("Error: (".$code.") ".$message." - ".$_SERVER['REQUEST_URI']);

			header("Content-Type: application/json");
			$output	=	output::format_data(null, $error);
			output::do_output($output, true, false);
			die();
		}
	}
?>