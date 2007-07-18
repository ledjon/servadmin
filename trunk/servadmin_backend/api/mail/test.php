<?
	require('core.php');

	$mail = new ServerAdminMail;
	
	$domain = 'qwerty.com';

	/* current domains */
	print_r($mail->getDomains( ));

	if(! $mail->domainExists( $domain ) ) {
		/* create the domain, and optionally set the master password */
		$mail->addDomain( $domain, 'password' );
	}

	/* list users for the domain */
	print_r( $mail->getUsers( $domain ) );

	$user = 'myuser';

	if(! $mail->userExists( $domain, $user ) ) {
		$mail->addUser( $domain, $user, 'password' )
			or die("Unable to add user");
			// optional last arg for options (quota is only current one)
	}

	/* set new password */
	$mail->passwd( $domain, $user, 'mynewpassword' );

	/* catchall address */
	$catchall = $mail->catchAll( $domain );

	echo ($catchall ? "Catchall address: $catchall\n" : "no catch all set\n");

	/* set it now */
	$mail->catchAll( $domain, $user .'@'. $domain )
		or die("Unable to set catchall");
	
	/* now remove it */
	$mail->catchAll( $domain, false )
		or die("Unable to remove catchall");
	/* aliases */
	/* domain first */
	print_r( $mail->listDomainAliases( ) );
	
	if(! $mail->domainAliasExists( $domain, 'querymyfakedomain.com' ) ) {
		$mail->addDomainAlias( $domain, 'querymyfakedomain.com' )
			or die("Unable to create domain alais");

		// remove it now
		$mail->delDomainAlias( $domain, 'querymyfakedomain.com' )
			or die("Unable to remote fake alias");
	}

	/* user aliases */
	print_r( $mail->listUserAliases( $domain ) );
	
	$alias = 'staff';
	echo "TEST: " . $alias . '@' . $domain . "\n";
	if(! $mail->userAliasExists( $domain, $alias, 'fakeuser@fakedomain.com' ) ) {
		$mail->addUserAlias( $domain, $alias, 'fakeuser@fakedomain.com' )
			or die("Unable to add user alias");

		$mail->delUserAlias( $domain, $alias, 'fakeuser@fakedomain.com' );
	}

	/* remove user */
	$mail->delUser( $domain, $user )
		or die("Unable to remove user $user");

	/* remove domai */
	$mail->delDomain( $domain )
		or die("Unable to remove domain $domain");
	
	/* some modules in the future may require this */
	$mail->build( );
?>
