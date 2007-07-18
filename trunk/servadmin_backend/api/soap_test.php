<?
	require_once('../lib/soap/class.nusoap.php');

	$services = array('dns', 'httpd', 'mail', 'crontab', 'user');

	$server = new soap_server;

	foreach($services as $srv)
	{
		$server->register( $srv );
	}

	fwrite(fopen('/tmp/asdf', 'w'), $GLOBALS["HTTP_RAW_POST_DATA"] );
	@$server->service( $GLOBALS["HTTP_RAW_POST_DATA"] );

	function dns( $key )
	{
	//	print_r($key);
		if(is_array($key))
		{
			$str = null;
			foreach($key as $k => $v)
				$str .= sprintf("%s=%s,", $k, $v);
			return $str;
		}
		return sprintf("%s (%s)", $key, gettype($key));

		/* later */
		if($key != 'asdf')
		{
			return new soap_fault(1, null, "invalid key ($key)");
		}

		if(!is_array($values))
			$values = array($values);

		return sprintf("%s requested with (%s) arguments",
			$action, implode(', ', $values));
	}
?>
