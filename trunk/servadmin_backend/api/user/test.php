<pre>
<?
	/* print the core, and the madness! */
	require('core.php');

	$user = 'foobar2';
	$group = 'myfakegroup';

	$api = new ServerAdminUser;

	if(! $api->groupExists( $group ) )
	{
		$api->addGroup( $group );
	}

	if(! $api->userExists( $user ) )
	{
		$api->addUser( $user ) or die("Unable to add user");
		$api->passwd( $user, "asdf" ) or die("Unable to change password");
		$api->modUser( $user, array('shell' => '/usr/local/bin/bash')) or die("Unable to modify user");
	}

	print_r( $api->getUsers( ) );

	$api->delUser( $user ) or die("Unable to delete user");
	$api->delGroup( $group ) or die("Unable to remove group");

	print_r( $api->getUsers( ) );
?>
