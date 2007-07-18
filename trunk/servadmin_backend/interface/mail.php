<?
	require_once('../api/interface/soap.php');

	$soap = new ServerAdmin_InterfaceSoap;

	/* we're an dns-interface, so pull in that core class */
	$soap->setType('mail');

	/* commands that can be called: */
	$commands = array(
        'domainInfo',
        'getDomains',
        'domainExists',
        'addDomain',
        'userExists',
        'getUsers',
        'addUser',
        'passwd',
        'catchAll',
        'listDomainAliases',
        'domainAliasExists',
        'addDomainAlias',
        'delDomainAlias',
        'listUserAliases',
        'userAliasExists',
        'addUserAlias',
        'delUserAlias',
        'delUser',
        'delDomain'
	);

	// sub generator
	/*
	foreach($commands as $c )
		print("\t// Stub for [$c]\n\tfunction $c( )\n\t{\n\t\tglobal \$mail;\n\t\t\n\t}\n\n");
	exit;
	*/

	$soap->registerServices( $commands );
	$mail = new ServerAdminMail;
	$mail->doDebug = true;

	$mail->setExceptionHandler("SoapExceptionHandler");

	/* service the request */
	$soap->main( $mail );

	/* likely need to do the build */
	$mail->build( );

	/*
	*
	*  Interface code starts here
	*
	*/
	// Stub for [domainInfo]
	function domainInfo( $domain, $flags )
	{
		global $mail;

		if(isset($flags))
		{
			$flags = (array) $flags;
		}

		return $mail->domainInfo( $domain, $flags );
	}

	// Stub for [getDomains]
	function getDomains( )
	{
		global $mail;
		
		return $mail->getDomains( );
	}

	// Stub for [domainExists]
	function domainExists( $domain )
	{
		global $mail;
		
		return $mail->domainExists( $domain );
	}

	// Stub for [addDomain]
	function addDomain( $domain, $passwd = null )
	{
		global $mail;
		
		return $mail->addDomain( $domain, $passwd );
	}

	// Stub for [userExists]
	function userExists( $domain, $user )
	{
		global $mail;
		
		return $mail->userExists( $domain, $user );
	}

	// Stub for [getUsers]
	function getUsers( $domain )
	{
		global $mail;

		return $mail->getUsers( $domain );
	}

	// Stub for [addUser]
	function addUser( $domain, $user, $passwd = null, $args = null )
	{
		global $mail;

		if(isset($args))
		{
			$args = (array) $args;
		}
		
		//throw new SoapFault("server", "$domain -> $user -> $passwd");
		return $mail->addUser( $domain, $user, $passwd, $args );
	}

	// Stub for [passwd]
	function passwd( $domain, $user, $passwd )
	{
		global $mail;
	
		return $mail->passwd($domain, $user, $passwd);
	}

	// Stub for [catchAll]
	function catchAll( $domain, $user = null )
	{
		global $mail;
		
		return $mail->catchAll( $domain, $user );
	}

	// Stub for [listDomainAliases]
	function listDomainAliases( $domain = null )
	{
		global $mail;
		
		return $mail->listDomainAliases( $domain );
	}

	// Stub for [domainAliasExists]
	function domainAliasExists( $domain, $alias )
	{
		global $mail;
	
		return $mail->domainAliasExists( $domain, $alias );
	}

	// Stub for [addDomainAlias]
	function addDomainAlias( $domain, $alias )
	{
		global $mail;
		
		return $mail->addDomainAlias( $domain, $alias );
	}

	// Stub for [delDomainAlias]
	function delDomainAlias( $domain, $alias )
	{
		global $mail;
		
		return $mail->delDomainAlias( $domain, $alias );
	}

	// Stub for [listUserAliases]
	function listUserAliases( $domain, $u = null )
	{
		global $mail;
		
		return $mail->listUserAliases( $domain, $u );
	}

	// Stub for [userAliasExists]
	function userAliasExists( $domain, $user, $alias )
	{
		global $mail;
		
		return $mail->userAliasExists( $domain, $user, $alias );
	}

	// Stub for [addUserAlias]
	function addUserAlias( $domain, $alias, $addr )
	{
		global $mail;
		
		return $mail->addUserAlias( $domain, $alias, $addr );
	}

	// Stub for [delUserAlias]
	function delUserAlias( $domain, $alias, $addr = false )
	{
		global $mail;
		
		return $mail->delUserAlias( $domain, $alias, $addr );
	}

	// Stub for [delUser]
	function delUser( $domain, $user )
	{
		global $mail;
		
		return $mail->delUser( $domain, $user );
	}

	// Stub for [delDomain]
	function delDomain( $domain )
	{
		global $mail;
		
		return $mail->delDomain( $domain );
	}

?>
