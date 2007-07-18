<pre>
<?
	require('core.php');

	$user = 'vpopmail';
	
	$api = new ServerAdminCrontab;

	if(! $api->userExists( $user ) ) {
		echo "... add $user's crontab entry!\n";

		/* add entry */
		$api->addEntry( $user,
			array('0', '*', '*', '*', '*'),
			'/bin/cat /dev/null'
		) or die("Unable to add entry");
	} 

	$entries = $api->getEntries( $user );
	print_r($entries);

	var_dump($api->getEmailTo( $user ));
	$api->setEmailTo( $user, 'ledjon@ledjon.com' );
	var_dump($api->getEmailTo( $user ));
	$api->setEmailTo( $user, null );
	var_dump($api->getEmailTo( $user ));

	/* add entry */
	$api->addEntry( $user,
		array('0', '*', '*', '*', '*'),
		'/bin/cat /dev/null'
	) or die("Unable to add entry");

	print_r($api->getEntries( $user ));

	/* remove user's crontab */
	$api->delUser( $user ) or die("Unable to remove crontab for user");

?>
