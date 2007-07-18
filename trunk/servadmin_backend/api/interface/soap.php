<?
	/* base functions and such for the soap interface objects */
	require_once( dirname(__FILE__) . '/core.php' );
//	require_once( dirname(__FILE__) . '/../../lib/soap/class.nusoap.php' );

function SoapExceptionHandler( $msg )
{
	throw new SoapFault("Server", $msg);
}

class ServerAdmin_InterfaceSoap extends ServerAdmin_InterfaceBase
{
	var $myError = null;

	//function ServerAdmin_InterfaceSoap( ) {
	function __construct( )
	{
		// $this->server = new soap_server;
		$this->server = new SoapServer(null, array('uri' => 'http://test/'));
	}
	
	/* run the request */
	function main( &$obj )
	{
		/* service the request */
		//return $this->server->service( $GLOBALS["HTTP_RAW_POST_DATA"] );

		// check for service key
		if(!is_object($obj))
		{
			$this->raiseError("Need serivce object passed to main( )");
		}

		$realkey = $obj->getConfig('base', 'service_key');

		$key = $_GET['_k'];

		if($realkey)
		{
			if($key != $realkey)
			{
				//return $this->raiseError("Invalid service key passed. Access denied.");
				die("Invalid service key passed. Access denied.");
			}
		}

		return $this->server->handle( );
	}

	function server( )
	{
		//return new soap_server;
		//return new SoapServer(null, array('uri' => 'http://test/'));

		return $this->server;
	}

	function registerServices( $commands )
	{
		if(!is_array($commands))
			return false;

		foreach($commands as $cmd)
			//$this->server->register( $cmd );
			$this->server->addFunction( $cmd );

		return true;
	}

	function parseParts( $in, $asObj = false )
	{
		if(!is_array($in)) {
			return $this->setMyError( "Incoming value are not an array" );
		}

		if((count($in) % 2) != 0) {
			return $this->setMyError( "Need even number of array arguments" );
		}

		$ret = array( );
		while($k = array_shift($in) and $v = array_shift($in)) {
			$ret[$k] = $v;
		}

		return ($asObj ? (object)$ret : $ret);
	}

	function setMyError( $msg )
	{
		$this->myError = $msg;
		return false;
	}
	
	function raiseError( $msg )
	{
		//return new soap_fault(1, null, $msg);
		return new SoapFault( "Server", $msg );
	}
}

?>
