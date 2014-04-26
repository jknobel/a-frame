<?
	/**
	 * Class (read: namespace) for outputting data and errors through XML and JSON format.
	 * 
	 * @author andrew lyon
	 */
	class output
	{
		/**
		 * Wrapper around output::output(). Formats data into a standard format.
		 * 
		 * @param array $object			our data
		 * @param string $format		xml|json
		 * @param bool $raw				if true, don't touch the data, just output it
		 * @param bool $has_errors		if we have errors, pull them out and put into output
		 * @param bool $print_output	whether or not to print or return output
		 */
		public static function do_output($object, $raw = false, $has_errors = false, $print_output = true)
		{
			if(!$raw)
			{
				if($has_errors)
				{
					$object	=	output::format_data(null, $this->event->get('app_errors'));
				}
				else
				{
					$object	=	output::format_data($object, null);
					if(QUERY_TEST_MODE)
					{
						$data = array('num' => count($GLOBALS['sql']), 'queries' => $GLOBALS['sql']);
						if(is_array($object))
							$object['query_log'] = $data;
					}
				}
			}
			
			$output	=	output::out($object, $print_output);
			
			return $output;
		}
		/**
		 * Provides a single location to output an object in a certain format. Currently 
		 * supports JSON and XML.
		 * 
		 * @param mixed $object			Array or object containing our data
		 * @param string $type			output type: json|xml
		 * @param bool $print_output	true = print the output directly, false = return 
		 * 								output for further processing or storage
		 * @return mixed				true if $print_output == true, string of encoded
		 * 								object if $print_output == false
		 * @uses						xml_encode()
		 */
		public static function out($object, $print_output = true)
		{
			$output	=	json_encode($object);
			
			if($print_output)
			{
				ob_end_clean();
				header('x-yahoo-api-response: true');
				//header('Content-Type: application/' . $type);
				echo $output;
				return true;
			}
			else
			{
				return $output;
			}
		}
		
		/**
		 * Centralized function for formatting output data. 
		 * 
		 * @param array $data	data in the return
		 * @param array $errors	errors in the return
		 * @return array		formatted data: array('data' => $data, 'errors' => $errors)
		 * 						this seems too simple for a function, but was happening in
		 * 						many places to it makes sense to centralize
		 */
		public static function format_data($data, $errors)
		{
			$result	=	array(
				'response'	=>	array(
					'data'		=>	$data,
					'error'		=>	$errors
				)
			);
			return $result;
		}
		
		/**
		 * Outputs an object into XML (much like json_encode). Attempts to use the primary
		 * key as an attribute in each "item", if an item has a primary key, this function
		 * names it <item> instead of trying to do any smart name conversions or bullshit.
		 * 
		 * 	example input:
		 * 	array(
		 * 		'users'	=>	array(
		 * 			array('id' => 34, 'name' => 'joe mama', 'gender' => 'male'),
		 * 			array('id' => 102, 'name' => 'jack mama', 'gender' => 'male')
		 * 		)
		 * 	)
		 * 
		 * 	example output (tabbing added manually):
		 * 	<users>
		 * 		<item id="34">
		 * 			<name>joe mama</name>
		 * 			<gender>male</gender>
		 * 		</item>
		 * 		<item id="102">
		 * 			<name>jack mama</name>
		 * 			<gender>male</gender>
		 * 		</item>
		 * 	</users>
		 * 
		 * @param mixed $object		An object or array we're converting to XML
		 * @return string			XML representation of our $object
		 */
		public static function xml_encode($object)
		{
			$output	=	'';
			
			// loop over our object and parse the values
			foreach($object as $key => $val)
			{
				if(is_array($val))
				{
					// we have an array
					
					// check if current item is a single object (has an id) 
					if(array_key_exists('id', $val))
					{
						// we have an id, make sure we let the following code know
						$use_id		=	true;
						$id			=	$val['id'];
						
						unset($val['id']);
					}
					else
					{
						// no id, just use the object as a normal item
						$use_id		=	false;
					}
					
					if(is_numeric($key))
					{
						// we have an id'ed item (or a generic item in a numbered array), use our generic item name
						$output	.=	'<item'. ($use_id ? ' id="'. $id .'"' : '') . '>' . "\n";
					}
					else
					{
						// no id, just use the key name as the item name
						$output	.=	'<' . $key . ($use_id ? ' id="'. $id .'"' : '') . '>' . "\n";
					}
					
					// recursive call to format the array items
					$output	.=	output::xml_encode($val);
					
					// close our tag
					if(is_numeric($key))
					{
						$output	.=	'</item>' . "\n";
					}
					else
					{
						// recursive call to excode the array
						$output	.=	'</' . $key .'>' . "\n";
					}
				}
				else
				{
					// not an array, its just a key => value item. 
					$output	.=	'<'. $key .'>';
					$output	.=	$val;
					$output	.=	'</'. $key .'>' . "\n";
				}
			}
			
			return $output;
		}
		
		/**
		 * Changes 'this string & this one' to 'this string &#038; this one' (unicode entities)
		 * 
		 * Makes for valid HTML and XML output.
		 * 
		 * @param string $string		string to convert special chars to unicode
		 * @param bool $run_entities	whether or not to run htmlentities on string before converting 
		 * 								entities (should be true unless string is already entitied)
		 * @return string				$string with special chars converted to unicode
		 */
		public static function htmlentities_numbered($string, $run_entities = true)
		{
			if($run_entities)
			{
				$string	=	htmlentities($string, ENT_COMPAT, 'UTF-8');
			}
			
			$table	=	get_html_translation_table(HTML_ENTITIES);
			$trans	=	array();
			foreach($table as $char => $ent)
			{
				$trans[$ent]	=	'&#'. ord($char) .';';
			}
			
			$trans['&euro;']	=	'&#8364;';
			$trans['&sbquo;']	=	'&#8218;';
			$trans['&fnof;']	=	'&#402;';
			$trans['&bdquo;']	=	'&#8222;';
			$trans['&hellip;']	=	'&#8230;';
			$trans['&dagger;']	=	'&#8224;';
			$trans['&Dagger;']	=	'&#8225;';
			$trans['&circ;']	=	'&#710;';
			$trans['&permil;']	=	'&#8240;';
			$trans['&Scaron;']	=	'&#352;';
			$trans['&lsaquo;']	=	'&#8249;';
			$trans['&OElig;']	=	'&#338;';
			$trans['&lsquo;']	=	'&#8216;';
			$trans['&rsquo;']	=	'&#8217;';
			$trans['&ldquo;']	=	'&#8220;';
			$trans['&rdquo;']	=	'&#8221;';
			$trans['&bull;']	=	'&#8226;';
			$trans['&ndash;']	=	'&#8211;';
			$trans['&mdash;']	=	'&#8212;';
			$trans['&tilde;']	=	'&#732;';
			$trans['&trade;']	=	'&#8482;';
			$trans['&scaron;']	=	'&#353;';
			$trans['&rsaquo;']	=	'&#8250;';
			$trans['&oelig;']	=	'&#339;';
			$trans['&Yuml;']	=	'&#376;';
			
			$string	=	strtr($string, $trans);
			
			// fuck any non-numbered entity we haven't whitelisted above. ex: &lrm;
			$string	=	preg_replace('/&(?!#[0-9]+).{1,8};[\s]?/', '', $string);
			
			return $string;
		}
	}	
?>