<?
	require_once('../api/interface/soap.php');

	$soap = new ServerAdmin_InterfaceSoap;

	//$soap->setType('mysql');

	/* commands that can be called: */
	$commands = array(
		'doPing'
	);

	$soap->registerServices( $commands );

	$core = new ServerAdminCore( );

	/* service the request */
	$soap->main( $core );

	/*
	*
	*  Interface code starts here
	*
	*/
	function doPing( )
	{
		return "PONG";
	}

?>
