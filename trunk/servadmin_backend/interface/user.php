<?
	require_once('../api/interface/soap.php');

	$soap = new ServerAdmin_InterfaceSoap;

	/* we're an user-interface, so pull in that core class */
	$soap->setType('user');

	/* commands that can be called: */
	$commands = array(
			'getUsers',
			'getUser',
			'userExists',
			'groupExists',
			'queryUser',
			'addUser',
			'addGroup',
			'passwd',
			'modUser',
			'delUser',
			'delGroup'
		);

	$soap->registerServices( $commands );
	$user = new ServerAdminUser;
	$user->doDebug = true;

	// set this so that exceptions are thrown as
	// soap faults, not regular exceptions
	//$user->setExceptionType("SoapFault");
	$user->setExceptionHandler("SoapExceptionHandler");
//	print_R($user->getExceptionHandler( ));
//	exit;

	/* service the request */
	$soap->main( $user );

	/* likely need to do the build */
	//$dns->build( );

	/*
	*
	*  Interface code starts here
	*
	*/
	function getUsers( )
	{
		global $user;

		return $user->getUsers( ); 
	}

	function getUser( $u )
	{
		global $user;

		return $user->getUser( $u );
	}

	function userExists( $u )
	{
		global $user;

		return $user->userExists($u);
	}

	function groupExists( $g )
	{
		global $user;

		return $user->groupExists($g);
	}

	function queryUser( $u, $part )
	{
		global $user;

		return $user->queryUser( $u, $part );
	}

	function addUser( $u, $in )
	{
		global $user;

		$in = (object)$in;

		/*
		ob_start();
		var_dump($in);
		$r = ob_get_contents();
		ob_end_clean();
		return $r;
		*/
		
		//return $user->getExceptionType();
		return $user->addUser($u, (array)$in);
		//	throw new SoapFault( "Server", $ex->getMessage( ) );
	}

	function addGroup( $group )
	{
		global $user;

		return $user->addGroup( $group );
	}

	function passwd( $u, $passwd )
	{
		global $user;

		return $user->passwd($u, $passwd);
	}

	function modUser( $u, $args )
	{
		global $user;

		return $user->modUser( $u, (array)$args );
	}

	function delUser( $u, $opts = null )
	{
		global $user;

		if(isset($opts))
		{
			$opts = (array) $opts;
		}

		return $user->delUser( $u, $opts );
	}

	function delGroup( $group )
	{
		global $user;

		return $user->delGroup( $group );
	}

?>
