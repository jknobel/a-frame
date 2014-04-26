<?
	/**
	 * This includes and runs Aframe, which processes the incoming request.
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

	
	error_reporting(E_ALL ^ E_NOTICE);

	require( 'library/php_error.php' );
	$options = array('catch_ajax_errors' => 0, 'enable_saving' => 0);
    \php_error\reportErrors($options);
	
	$app_base	=	dirname(__FILE__);
	$core_base	=	$app_base . '/a-frame';
	include_once $core_base . '/index.php';
	
?>