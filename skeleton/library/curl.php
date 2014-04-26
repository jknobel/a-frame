<?
	class curllib
	{
		static public $follow_redir	=	true;
		static public $max_redir	=	10;
		static public $timeout		=   200;
		
		static function get($url, $request = array(), $extraheaders = array())
		{
			return curllib::call('GET', $url, $request, $extraheaders);
		}
		
		static function post($url, $request = array(), $extraheaders = array())
		{
			return curllib::call('POST', $url, $request, $extraheaders);
		}
		
		static function call($method, $url, $request = array(), $extraheaders = array())
		{
			if($method == 'GET')
			{
				$query	=	http_build_query($request, null, '&');
				$url	=	$url . (strpos($url, '?') === false ? '?' : '') . $query;
			}
			
			$headers	=	array(
				'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
				'Accept-Language: en-us,en;q=0.5',
				'Accept-Charset: utf-8;q=0.7,*;q=0.7'
			);
			
			if(!empty($extraheaders) && is_array($extraheaders))
			{
				foreach($extraheaders as $name => $val)
				{
					$headers[]	=	$name .': '. $val;
				}
			}
			
			//open connection
			$ch	=	curl_init();
			
			//set the url, number of POST vars, POST data
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_TIMEOUT, curllib::$timeout);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_VERBOSE, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			if($method == 'POST')
			{
				if(is_array($request))
				{
					$req	=	array();
					foreach($request as $key => $val)
					{
						$req[]	=	urlencode($key) . '=' . urlencode($val);
					}
					$req	=	implode('&', $req);
					//die($url.'?'.$req);
				}
				else
				{
					$req	=	$request;
				}
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
			}
			
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.9) Gecko/20100315 Firefox/3.5.9'); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, curllib::$follow_redir);
			curl_setopt($ch, CURLOPT_MAXREDIRS, curllib::$max_redir);
			
			//execute post
			$response	=	curl_exec($ch);
			
			//close connection
			curl_close($ch);
			
			// easy debugging
			//print_r($headers);
			//echo $url.'<br/>'.$req;
			//echo 'cmd: '.$url . '   '. json_encode($request) ."\n";
			//echo $response . "\n\n\n";
			
			return $response;
		}
		
		/**
		 * Finds the final URL at the end of a string os from 0 to $max_redir redirects are involved. 
		 */
		static function grab_final_url($url)
		{
			$headers	=	array(
				'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
				'Accept-Language: en-us,en;q=0.5',
				'Accept-Charset: utf-8;q=0.7,*;q=0.7'
			);
			
			//open connection
			$ch	=	curl_init();
			
			//set the url, number of POST vars, POST data
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.9) Gecko/20100315 Firefox/3.5.9'); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, curllib::$max_redir);
			
			//execute post & get our headers
			$response	=	curl_exec($ch);
			$headers	=	curl_getinfo($ch);
			
			//close connection
			curl_close($ch);
			
			// america....FUCK YEAHHH
			return $headers['url'];
		}
	}
?>