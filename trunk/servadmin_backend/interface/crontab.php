<?
	require_once('../api/interface/soap.php');

	$soap = new ServerAdmin_InterfaceSoap;

	$soap->setType('crontab');

	/* commands that can be called: */
	$commands = array(
		'delUser',
		'userExists',
		'getEntries',
		'setEntries',
		'addEntry',
		'getEmailTo',
		'setEmailTo'
	);

	// sub generator
	/*
	foreach($commands as $c )
		print("\t// Stub for [$c]\n\tfunction $c( )\n\t{\n\t\tglobal \$crontab;\n\t\t\n\t}\n\n");
	exit;
	*/

	$soap->registerServices( $commands );
	$crontab = new ServerAdminCrontab;
	$crontab->doDebug = true;

	$crontab->setExceptionHandler("SoapExceptionHandler");

	/* service the request */
	$soap->main( $crontab );

	/* likely need to do the build */
	//$mail->build( );

	/*
	*
	*  Interface code starts here
	*
	*/
	// Stub for [delUser]
	function delUser( $user )
	{
		global $crontab;
	
		return $crontab->delUser($user);
	}

	// Stub for [userExists]
	function userExists( $user )
	{
		global $crontab;
		
		return $crontab->userExists( $user );
	}

	// Stub for [getEntries]
	function getEntries( $user )
	{
		global $crontab;
		
		return $crontab->getEntries( $user );
	}

	// Stub for [setEntries]
	function setEntries( $user, $entries )
	{
		global $crontab;

		// soap turned some arrays into objects,
		// so we need to convert thme back
		for($i = 0; $i < count($entries); $i++)
		{
			$entries[$i] = (array) $entries[$i];
		}

		return $crontab->setEntries( $user, $entries );
	}

	// Stub for [addEntry]
	function addEntry( $user, $times, $command )
	{
		global $crontab;
		
		return $crontab->addEntry( $user, $times, $command );
	}

	// Stub for [getEmailTo]
	function getEmailTo( $user )
	{
		global $crontab;
		
		return $crontab->getEmailTo( $user );
	}

	// Stub for [setEmailTo]
	function setEmailTo( $user, $emailto )
	{
		global $crontab;
		
		return $crontab->setEmailTo( $user, $emailto );
	}

?>
