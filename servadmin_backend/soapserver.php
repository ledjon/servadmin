<?
	/*
		Test SOAP Server
		by Jon Coutler
	*/

	if(1 == 0)
	{
		require_once('lib/soap/class.nusoap.php');
	
		$server = new soap_server;
	
		$server->register('reverse');
		$server->register('whoami');
	
		/* service the request */
		$server->service( $GLOBALS["HTTP_RAW_POST_DATA"] );
	}
	else
	{
		// new build-in php stuff
		$server = new SoapServer(null, array('uri' => 'http://test/'));
		$server->addFunction(array('reverse', 'whoami'));
		$server->handle();
	}
	
	function reverse( $incoming, $incoming2 = null ) {
		#return is_array($incoming) ? 'array' : 'not array';
		#return strrev( $incoming2 );
		#return strrev( $incoming );

		if(is_array($incoming)) {
			return "(array) " . strrev( $incoming['a'] ); 
		} else {
			return strrev( $incoming );
		}
	}

	function whoami( ) {
		return trim(`whoami`);
	}
?>
