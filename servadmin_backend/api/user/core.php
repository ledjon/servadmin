<?

/*
	Core of (system) user account mgmt
	by Jon Coulter
*/

require_once(dirname(__FILE__) . '/../core.php');

class ServerAdminUser extends ServerAdminBase
{
	function ServerAdminUser( )
	{
		$this->core = new ServerAdminCore;
		$this->setOS( dirname(__FILE__) );

		// this sets 'defaults' and 'programs'
		// based on the sa.conf values
		$this->os->setProgramsAndDefaults( 'user' );

		//die("conf: " . $this->getConfig('user', 'defaults:home'));
	}

	/* return a list of all users > x (x = system's max id for system accounts) */
	function getUsers( )
	{
		return $this->os->getUsers( );
	}

	function getUser( $u )
	{
		return $this->os->getUser( $u );
	}

	function userExists( $user )
	{
		return $this->os->userExists( $user );
	}

	function groupExists( $group )
	{
		return $this->os->groupExists( $group );
	}

	/* query for a user's info */
	function queryUser( $user, $part )
	{
		return $this->os->queryUser( $user, $part );
	}
	
	function addUser( $user, $args = null )
	{
		if($err = $this->validate('user', $user))
		{
			return $this->raiseError( $err );
		}

		return $this->os->addUser( $user, $args );
	}

	function addGroup( $group )
	{
		return $this->os->addGroup( $group );
	}

	function passwd( $user, $passwd )
	{
		return $this->os->passwd( $user, $passwd );
	}

	function modUser( $user, $args )
	{
		return $this->os->modUser( $user, $args );
	}

	function delUser( $user, $opts = null )
	{
		/* you typically want to remove the home directory */
		if(!$opts['no_rmhome'])
			$opts['rmhome'] = true;
			
		if( $ret = $this->os->delUser( $user, $opts ) )
		{
			/* remove depend things */
			foreach($this->depends('user', 'delUser') as $obj)
			{
				$obj->delUser( $user, $opts );
			}

			return true; 
		}
		else
		{
			return $ret;
		}
	}

	function delGroup( $group )
	{
		foreach($this->depends('group', 'delGroup') as $obj)
		{
			$obj->delGroup( $group );
		}
		
		return $this->os->delGroup( $group );
	}

	/* find things that depend on the user,
		such as crontab
	*/
	function depends( $class )
	{
		return $this->core->depends( $class );
	}
}

?>
