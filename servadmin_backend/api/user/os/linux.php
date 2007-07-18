<?

require_once(dirname(__FILE__) . '/posix.php');

class ServerAdminUser_Linux extends ServerAdminUser_POSIX
{
	var $defaults = array(
			'shell'	=> '/sbin/nologin',
			'home' 	=> '/home/%s',
			'group'	=> 'users'
	);

	var $_parts = array(
			'username' 	=> 0,
			'uid'		=> 2,
			'fullname'	=> 4,
			'home'		=> 5,
			'shell'		=> 6
	);

	protected $sysfiles = array(
		'passwd'	=> '/etc/passwd',
		'group'	=> '/etc/group'
	);


	function __construct( &$p )
	{
		parent::__construct( $p );
	}

	function getUsers( )
	{
		$fp = fopen($this->sysfiles['passwd'], 'r')
				or $this->raiseError("Unable to open passwd file for reading: " . $this->sysfiles['passwd']);

		while($line = fgets($fp, 1024*10))
		{
			$line = trim($line);

			$parts = explode(':', $line);

			// system-level user 
			if($parts[$this->_parts['uid']] < 1000)
				continue;

			$user = $parts[$this->_parts['username']];

			foreach($this->_parts as $k => $v)
			{
				$return[$user][$k] = $parts[$v];
			}
		}

		return $return;
	}

	function getUser( $u )
	{
		$users = $this->getUsers( );

		return $users[$u];
	}

	/* see if system user exists */
	function userExists( $user )
	{
		return ($this->getUser( $user ) ? true : false);
	}

	/* see fi a system group exists */
	function groupExists( $group )
	{
		$groups = $this->getGroups( );	

		return (in_array($group, $groups) ? true : false);
	}

	function getGroups( )
	{
		$fp = fopen($this->sysfiles['group'], 'r')
			or $this->raiseError("Unable to open group file for reading: " . $this->sysfiles['group']);

		$ret = array( );

		while($line = fgets($fp, 1024*10))
		{
			$parts = explode(':', $line);

			if(!$parts[0])
			{
				break;
			}

			$ret[] = $parts[0];
		}

		return $ret;
	}

	function queryUser( $user, $parts )
	{
		if(! $this->userExists( $user ) ) {
			return $this->raiseError("Unable to find user $user");
		}

		$p = $this->getUser( $user );
		$this->debug("full parts ($user): " . var_export($p));

		if(!is_array($parts)) {
			$parts = array($parts);
			$wantarray = false;
		} else {
			$wantarray = true;
		}

		$ret = array( );
		foreach($parts as $part) {
			if(! $this->_parts[$part] ) {
				return $this->raiseError("Unknown part [$part]");
			}
			
			//$ret[$part] = $p[$this->_parts[$part]];
			$ret[$part] = $p[$part];
		}

		$this->debug("parts for user ($user): " . var_export( $ret )); 

		return ($wantarray ? $ret : array_shift($ret));
	}

	function addUser( $user, $args = null )
	{
		/* does the user exist? */
		if($this->userExists( $user ))
		{
			return $this->raiseError("User already exists");
		}
		
		/* match up with defaults */
		$args = $this->fillDefaults( $args, $this->defaults, $user );
		
		$ret = $this->execute('useradd',
			array(
				'-s', $args['shell'],
				'-d', $args['home'],
				'-g', $args['group'],
				'-m',
				$user
			),
			0, // return to expect
			true // include '2>&1' in exec_msg
		);

		/* false return ? */
		if($ret !== true)
		{
			return $this->raiseError("Error adding user: [$this->exec_ret] [$this->exec_msg]");
		}

		$this->log("Added user $user");

		/* password? */
		if($pass = $args['pass']
			or $pass = $args['password']
			or $pass = $args['passwd'])
		{
			if(! $this->passwd( $user, $pass ) )
			{
				return $this->raiseError("Unable to set password: [$this->exec_msg]");
			}
		}

		return true;
	}

	function addGroup( $group )
	{
		if($this->groupExists( $group ))
		{
			return $this->raiseError("Group $group exists already");
		}

		$ret = $this->execute('groupadd',
				array(
					$group
				),
				0,
				true
		);

		if($ret !== true)
		{
			return $this->raiseError("Error adding group: $this->exec_msg");
		}

		return true;
	}

	function passwd( $user, $passwd )
	{
		//$this->debug("setting password for `$user' to `$passwd'");
		if(! $this->userExists( $user ) )
		{
			return $this->raiseError("Unknown user ($user) -- Unable to change password.");
		}

		$ret = $this->execute('mkpasswd',
				array( $passwd ),
				0,
				true
			);

		if(! $ret )
		{
			$this->raiseError("Unable to mkpasswd the password: $this->exec_msg");
		}

		$hash = trim($this->exec_msg);

		$this->debug("hash: `$hash'");
		
		$ret = $this->execute('usermod',
				array( '-p', $this->exec_msg, $user ),
				0,
				true
			);
	
		if($ret !== true)
		{
			return $this->raiseError("Error chaning pass: [$this->exec_ret] [$this->exec_msg]");
		}

		// check to be sure! 
		//if(! preg_match('/passwd: done/', $this->exec_msg) ) {
		//	return $this->raiseError("passwd seemed to fail: $this->exec_msg");
		//}

		return true;
	}

	function modUser( $user, $args )
	{
		if(!is_array( $args ))
		{
			return $this->raiseError("Need an array to modUser()");
		}

		// user doesn't exist? 
		if(! $this->userExists( $user ) )
		{
			return $this->raiseError("$user is not a valid system user");
		}

		$parts = array( );
	
		/* which things are we changing? */
		if($shell = $args['shell']) {
			$parts[] = '-s';
			$parts[] = $shell;
		}
		
		if($home = $args['home']) {
			$parts[] = '-d';
			$parts[] = $home;
		}

		if($group = $args['group']) {
			$parts[] = '-g';
			$parts[] = $group;
		}

		if($groups = $args['groups']) {
			$parts[] = '-G';
			$parts[] = (
					is_array($groups) ?
					implode(',', $groups) :
					$groups
				);
		}

		if($pass = $args['pass'])
		{
			/* need to do passwd for this args */
			if(! $this->passwd( $user, $passwd ) )
			{
				return $this->raiseError("Unable to change password");
			}
		}

		/* some real modifying to do */
		if(count($parts) > 0)
		{
			$parts[] = $user;
			$ret = $this->execute('usermod',
					$parts,
					0,
					true
			);

			if($ret !== true)
			{
				return $this->raiseError("Unable to make changes: " .
						"[$this->exec_ret] [$this->exec_msg]");
			}
		}

		return true;
	}

	/* remove user (and all things about said user) */
	function delUser( $user, $opts = null )
	{
		if(! $this->userExists( $user ) )
		{
			return $this->raiseError("User $user is not a valid system user");
		}

		$parts = array( $user );

		if($opts['rmhome'])
			array_unshift($parts, '-r');

		$ret = $this->execute('userdel', $parts, 0, true);

		if($ret !== true)
		{
			return $this->raiseError("Unable to remove user ($user): $this->exec_msg");
		}

		return true;
	}

	/* remove a group */
	function delGroup( $group )
	{
		if(! $this->groupExists( $group ) )
		{
			return $this->raiseError("Group $group does not exist!");
		}

		$ret = $this->execute('groupdel',
				array( $group ),
				0,
				true
		);

		if($ret !== true)
		{
			return $this->raiseError("Unable to remove group ($group): $this->exec_msg");
		}

		return true;
	}
}

?>
