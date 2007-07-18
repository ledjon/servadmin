<?
	require_once(dirname(__FILE__) . '/nusoap.php');

class ServAdminInterface extends soapclient
{
	//var $location = 'http://localhost:81/admin/interface/%s.php';

	function ServAdminInterface( $location, $type )
	{
		//$this->location = sprintf($location, $type);
		$this->location = sprintf($location, $type);

		parent::soapclient($this->location, false);
	}

	// generic soap fault exception handler
	function checkFault( )
	{
		if($this->fault)
		{
			// maybe alter this later to throw another exception
			die($this->faultstring);
		}

		// bad request?
		if($err = $this->getError( ))
		{
			// this needs to be logged, rather then shown the user
			die("Non-SOAP-Fault Error: " . $erri . "<br>" .
				$this->response);
		}
		
		// no faults
		return false;
	}
}

?>
