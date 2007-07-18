<?

/*
	$Id: posix.php,v 1.3 2005/11/04 03:54:58 ledjon Exp $

	Basic POSIX user admin functions
	... the stuff that is common to all *nix system 
*/

class ServerAdminUser_POSIX extends ServerAdminBase
{
	var $defaults = array(
			'shell'	=> '/sbin/nologin',
			'home' 	=> '/home/users/%s',
			'group'	=> 'users'
	);

	function __construct( &$p )
	{
		$this->setParent( $p );
	}

	function getUsers( )
	{
		return $this->notImplemented( );
	}

	function getUser( $u )
	{
		$users = $this->getUsers( );

		return $users[$u];
	}

	function userExists( $user )
	{
		return $this->notImplemented( );
	}

	function groupExists( $group )
	{
		return $this->notImplemented( );
	}

	function queryUser( $user, $parts )
	{
		return $this->notImplemented( );
	}

	function addUser( $user, $args = null )
	{
		return $this->notImplemented( );
	}

	function addGroup( $group )
	{
		return $this->notImplemented( );
	}

	// this is actually kept
	// as it calls an execpt script that should
	// do the work no matter what the os is
	function passwd( $user, $passwd ) {
		if(! $this->userExists( $user ) )
		{
			return $this->raiseError("Unknown user ($user) -- Unable to change password.");
		}

		$ret = $this->execute('passwd',
				array($user, $passwd),
				10, // looking for a 10 return code
				true
		);	
		
		if($ret !== true) {
			return $this->raiseError("Error chaning pass: [$this->exec_ret] [$this->exec_msg]");
		}

		/* check to be sure! */
		/*
		if(! preg_match('/passwd: done/', $this->exec_msg) ) {
			return $this->raiseError("passwd seemed to fail: $this->exec_msg");
		}
		*/

		return true;
	}

	function modUser( $user, $args )
	{
		return $this->notImplemented( );
	}

	function delUser( $user, $opts = null )
	{
		return $this->notImplemented( );
	}

	function delGroup( $group )
	{
		return $this->notImplemented( );
	}

}

?>
