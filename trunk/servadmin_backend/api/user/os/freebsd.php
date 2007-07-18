<?

require_once(dirname(__FILE__) . '/posix.php');

class ServerAdminUser_FreeBSD extends ServerAdminUser_POSIX
{
	var $defaults = array(
			'shell'	=> '/sbin/nologin',
			'home' 	=> '/usr/home/users/%s',
			'group'	=> 'users'
	);

	var $_parts = array(
			'username' 	=> 0,
			'uid'		=> 2,
			'fullname'	=> 7,
			'home'		=> 8,
			'shell'		=> 9
	);


	//function ServerAdminUser_FreeBSD( &$p )
	function __construct( &$p )
	{
		parent::__construct( $p );
		//$this->setParent( $p );
	}

	function getUsers( )
	{
		$ret = $this->execute('pw',
				array(
					'user', 'show', '-a'
				),
				0,
				true
		);

		if($ret !== true) {
			return $this->raiseError("Unable to get list of all users");
		}

		$return = array( );
		$lines = explode("\n", $this->exec_msg);
		foreach($lines as $line) {
			$parts = explode(':', $line);

			/* system-level user */
			if($parts[$this->_parts['uid']] < 1000)
				continue;

			$user = $parts[$this->_parts['username']];

			foreach($this->_parts as $k => $v) {
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
	function userExists( $user ) {
		$ret = $this->execute('pw',
				array('user', 'show', '-n', $user),
				0,
				true
		);

		if($ret !== true) {
			return false;
		} else {
			return true;
		}
	}

	/* see fi a system group exists */
	function groupExists( $group ) {
		$ret = $this->execute('pw',
				array('group', 'show', '-n', $group),
				0,
				true
		);

		if($ret !== true) {
			return false;
		} else {
			return true;
		}
	}

	function queryUser( $user, $parts ) {
		/* userExists() will query for the info anyway */
		if(! $this->userExists( $user ) ) {
			return $this->raiseError("Unable to find user $user");
		}

		$p = explode(':', trim($this->exec_msg));

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
			
			$ret[$part] = $p[$this->_parts[$part]];
		}

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
		
		$ret = $this->execute('pw',
			array(
				'user',
				'add',
				'-s', $args['shell'],
				'-d', $args['home'],
				'-g', $args['group'],
				'-m',
				'-n', $user
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

	function addGroup( $group ) {
		if($this->groupExists( $group ))
		{
			return $this->raiseError("Group $group exists already");
		}

		$ret = $this->execute('pw',
				array(
					'group',
					'add',
					'-n', $group
				),
				0,
				true
		);

		if($ret !== true) {
			return $this->raiseError("Error adding group: $this->exec_msg");
		}

		return true;
	}

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

	function modUser( $user, $args ) {
		if(!is_array( $args )) {
			return $this->raiseError("Need an array to modUser()");
		}

		/* user doesn't exist? */
		if(! $this->userExists( $user ) ) {
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

		if($pass = $args['pass']) {
			/* need to do passwd for this args */
			if(! $this->passwd( $user, $passwd ) ) {
				return $this->raiseError("Unable to change password");
			}
		}

		/* some real modifying to do */
		if(count($parts) > 0) {
			array_unshift($parts, 'user', 'mod');
			$parts[] = '-n';
			$parts[] = $user;
			$ret = $this->execute('pw',
					$parts,
					0,
					true
			);

			if($ret !== true) {
				return $this->raiseError("Unable to make changes: " .
						"[$this->exec_ret] [$this->exec_msg]");
			}
		}

		return true;
	}

	/* remove user (and all things about said user) */
	function delUser( $user, $opts = null ) {
		if(! $this->userExists( $user ) )
		{
			return $this->raiseError("User $user is not a valid system user");
		}

		$parts = array(
			'user', 'del',
			'-n', $user
		);

		if($opts['rmhome'])
			$parts[] = '-r';

		$ret = $this->execute('pw', $parts, 0, true);

		if($ret !== true)
		{
			return $this->raiseError("Unable to remove user ($user): $this->exec_msg");
		}

		return true;
	}

	/* remove a group */
	function delGroup( $group ) {
		if(! $this->groupExists( $group ) ) {
			return $this->raiseError("Group $group does not exist!");
		}

		$ret = $this->execute('pw',
				array(
					'group', 'del',
					'-n', $group
				),
				0,
				true
		);

		if($ret !== true) {
			return $this->raiseError("Unable to remove group ($group): $this->exec_msg");
		}

		return true;
	}
}

?>
