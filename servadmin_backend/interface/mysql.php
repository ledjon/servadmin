<?
	require_once('../api/interface/soap.php');

	$soap = new ServerAdmin_InterfaceSoap;

	$soap->setType('mysql');

	/* commands that can be called: */
	$commands = array(
		'listDatabases',
		'listAccess',
		'listUsers',
		'createDatabase',
		'dropDatabase',
		'createUser',
		'dropUser',
		'grantAccess',
		'revokeAccess',
		'changePass',
		'optimizeDatabase',
		'repairDatabase',
		'dropTables',
		'dumpDatabase'
	);

	// sub generator
	/*
	foreach($commands as $c )
		print("\t// Stub for [$c]\n\tfunction $c( )\n\t{\n\t\tglobal \$mysql;\n\t\t\n\t}\n\n");
	exit;
	*/

	$soap->registerServices( $commands );
	$mysql = new ServerAdminMySQL;
	$mysql->doDebug = true;

	$mysql->setExceptionHandler("SoapExceptionHandler");

	/* service the request */
	$soap->main( $mysql );

	/* likely need to do the build */
	//$mail->build( );

	/*
	*
	*  Interface code starts here
	*
	*/
	// Stub for [listDatabases]
	function listDatabases( $search )
	{
		global $mysql;
		
		return $mysql->listDatabases( $search );
	}

	// Stub for [listAccess]
	function listAccess( $search )
	{
		global $mysql;
		
		return $mysql->listAccess( $search );
	}

	// Stub for [listUsers]
	function listUsers( $search )
	{
		global $mysql;

		return $mysql->listUsers( $search );
	}

	// Stub for [createDatabase]
	function createDatabase( $db )
	{
		global $mysql;

		return $mysql->createDatabase( $db );
	}

	// Stub for [dropDatabase]
	function dropDatabase( $db )
	{
		global $mysql;
		
		return $mysql->dropDatabase( $db );
	}

	// Stub for [createUser]
	function createUser( $user, $pass )
	{
		global $mysql;
		
		return $mysql->createUser( $user, $pass );
	}

	// Stub for [dropUser]
	function dropUser( $user )
	{
		global $mysql;
		
		return $mysql->dropUser( $user );
	}

	// Stub for [grantAccess]
	function grantAccess( $user, $db, $access )
	{
		global $mysql;
		
		return $mysql->grantAccess( $user, $db, $access );
	}

	// Stub for [revokeAccess]
	function revokeAccess( $user, $db, $access )
	{
		global $mysql;
		
		return $mysql->revokeAccess( $user, $db, $access );
	}

	// Stub for [changePass]
	function changePass( $user, $pass )
	{
		global $mysql;
		
		return $mysql->changePass( $user, $pass );
	}

	// Stub for [optimizeDatabase]
	function optimizeDatabase( )
	{
		global $mysql;
		
		return false;
	}

	// Stub for [repairDatabase]
	function repairDatabase( )
	{
		global $mysql;
		
		return false;
	}

	// Stub for [dropTables]
	function dropTables( )
	{
		global $mysql;
		
		return false;
	}

	// Stub for [dumpDatabase]
	function dumpDatabase( )
	{
		global $mysql;
		
		return false;
	}

?>
