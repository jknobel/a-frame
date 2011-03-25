<?
	/**
	 * Holds aframe's data validation class, mainly used by models for data validation.
	 * For the most part, replaces the input_validation class.
	 * 
	 * 
	 * Copyright (c) 2009, Lyon Bros Enterprises, LLC. (http://www.lyonbros.com)
	 * 
	 * Licensed under The MIT License. 
	 * Redistributions of files must retain the above copyright notice.
	 * 
	 * @copyright	Copyright (c) 2009, Lyon Bros Enterprises, LLC. (http://www.lyonbros.com)
	 * @package		aframe
	 * @subpackage	aframe.core
	 * @license		http://www.opensource.org/licenses/mit-license.php
	 */
	
	/**
	 * Used by models for validating data structures recursively. Has the ability to remove
	 * unwanted data and also validate passed in data using a colleciton of simple types 
	 * (string, integer, array w/ sub-objects) OR specify callbacks for validation
	 * 
	 * Callbacks MUST return boolean true/false.
	 * 
	 * @package		aframe
	 * @subpackage	aframe.core
	 * @author		Andrew Lyon
	 */
	class data_validation extends base
	{
		public static $fake_types	=	array(
			'number',
			'date'
		);

		public static $numeric_types	=	array(
			'int',
			'integer',
			'float',
			'double',
			'real'
		);

		public function validate(&$data, $format, $remove_extra_data, $cast_data = true, $breadcrumbs = '')
		{
			$errors	=	array();

			// if asked of us, remove items in the passed-in data that are not present as
			// items in the $format array
			if($remove_extra_data && !empty($data))
			{
				foreach($data as $key => $validate)
				{
					if(!isset($format[$key]))
					{
						unset($data[$key]);
					}
				}
			}

			// loop over the data validation and apply the comparisons/transformations to our data
			foreach($format as $key => $validate)
			{
				// pull out some default params
				$required	=	isset($validate['required']) ? $validate['required'] : false;
				$type		=	isset($validate['type']) ? $validate['type'] : 'string';

				// the breadcrumb keeps track of how deep the rabbit hole goes
				$breadcrumb	=	empty($breadcrumbs) ? $key : $breadcrumbs . ':' . $key;

				// if required and not found, add it to errors
				if(!isset($data[$key]))
				{
					if($required)
					{
						$errors[]	=	data_validation::error($breadcrumb, 'missing');
					}

					// NEXT!
					continue;
				}

				// cast our types
				if($cast_data && !in_array($type, data_validation::$fake_types))
				{
					settype($data[$key], $type);
				}

				// process any transformations (typical things would be strtolower() or uhh hmm, strotoupper()
				if(isset($validate['transform']) && !empty($validate['transform']) && (!is_string($validate['transform']) || function_exists($validate['transform'])))
				{
					$transform	=	$validate['transform'];
					$data[$key]	=	call_user_func_array($transform, array($data[$key]));
				}

				// process our callback, if it exists
				if(isset($validate['callback']) && !empty($validate['callback']))
				{
					$check	=	call_user_func_array(
						$validate['callback'],
						array(
							$data[$key],
							$validate
						)
					);

					if(!$check)
					{
						$callback	=	$validate['callback'];
						if(is_array($callback) && is_object($callback[0]))
						{
							if(is_object($callback[0]))
							{
								$callback[0]	=	get_class($callback[0]);
							}
						}
						$errors[]	=	data_validation::error(
							$breadcrumb,
							'callback failed: '. $callback[0] . '::' . $callback[1] .'('. print_r($data[$key], true) .')'
						);
					}

					// no need to do more processing if we got a callback
					continue;
				}

				// validate the type. especially useful if $cast_data is false
				if($type == 'number' || !in_array($type, data_validation::$fake_types))
				{
					$type_fn	=	'is_' . $type;
					if($type == 'number')
					{
						$type_fn	=	'is_numeric';
					}

					if(!$type_fn($data[$key]))
					{
						$errors[]	=	data_validation::error($breadcrumb, 'not_' . $type);
						continue;
					}
				}

				// do advanced type checking beyond just the normal "is_[type]()" functions
				switch($type)
				{
					case 'number':
					case 'string':
					case 'int':
					case 'float':
					case 'double':
					case 'real':
					case 'date':
						$fn	=	'validate_' . $type;

						// we really only need one number validation function, so if we get ANY numbers, pass them
						// along to it. this especially true since all of the type checking has already happened
						// above.
						if(in_array($type, data_validation::$numeric_types))
						{
							$fn	=	'validate_number';
						}

						if(($error = data_validation::$fn($data[$key], $validate)) !== true)
						{
							$errstr		=	'invalid_' . $type;
							if(is_string($error))
							{
								$errstr	.=	':'.$error;
							}
							$errors[]	=	data_validation::error($breadcrumb, $errstr);
						}
						break;
					case 'object':
					case 'array':
						if(isset($validate['format']))
						{
							if($type == 'object')
							{
								$errors[]	=	data_validation::validate($data[$key], $validate['format'], $remove_extra_data, $cast_data, $breadcrumb);
							}
							else
							{
								$err	=	array();
								for($i = 0, $n = count($data[$key]); $i < $n; $i++)
								{
									$breadcrumb_a	=	$breadcrumb . ':'.$i;
									$error_a	=	data_validation::validate($data[$key][$i], $validate['format'], $remove_extra_data, $cast_data, $breadcrumb_a);
									if(!empty($error_a))
									{
										// only add it to our local errors of we got an error (not just an empty array)
										$err[]	=	$error_a;
									}
								}

								if(!empty($err))
								{
									// only merge in our errors if we got any =]
									$errors	=	array_merge($errors, $err);
								}
							}
						}
						break;
				}
			}

			return $errors;
		}

		public function validate_string($value, $validate)
		{
			// process length meta language
			if(isset($validate['length']) && preg_match('/^([><]=?)?[0-9]+$/', $validate['length']))
			{
				$length		=	$validate['length'];
				$compare	=	$length[0];
				$equal		=	$length[1];
				$len		=	strlen($value);
				if($equal == '=')
				{
					$length		=	(int)substr($length, 2);

					// note the sign reversla in the following ifs...because we're testing for a NOT match
					if($compare == '<' && $len > $length)
					{
						return 'length-long';
					}
					else if($compare == '>' && $len < $length)
					{
						return 'langth-short';
					}
				}
				else
				{
					$length		=	(int)substr($length, 1);

					// note the sign reversla in the following ifs...because we're testing for a NOT match
					if($compare == '<' && $len >= $length)
					{
						return 'length-long';
					}
					else if($compare == '>' && $len <= $length)
					{
						return 'langth-short';
					}
				}
			}

			// process regex patterns
			if(isset($validate['pattern']) && !empty($validate['pattern']))
			{
				if(!preg_match($validate['pattern'], $value))
				{
					return 'pattern';
				}
			}

			// YESSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSsssSSsssSSSssSSs!!!
			return true;
		}

		public function validate_number($value, $validate)
		{
			if(isset($validate['min']) && $value < $validate['min'])
			{
				return 'min';
			}

			if(isset($validate['max']) && $value > $validate['max'])
			{
				return 'max';
			}
			return true;
		}

		public function validate_date($value, $validate)
		{
			if(strtotime($value) === false)
			{
				return false;
			}
			return true;
		}

		public function error($key, $type)
		{
			return array('key' => $key, 'type' => $type);
		}
	}
?>