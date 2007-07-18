<?
	/*
		VPopMail Mail-Handling class
		by Jon Coulter

		This is probably the most-beta of the packages so far

		// TODO
		- Add better error handling of failed events,
		  such as modifying a user (throw exceptions, instead of just returnign false)
	*/

class ServerAdminMail extends ServerAdminBase
{
	/* vpopmail base location */
	var $vbase	= '/usr/local/vpopmail';

	/* apps */
	/*
	var $_apps	= array(
                "vaddaliasdomain",
                "vadddomain",
                "vadduser",
                "valias",
                "vdeldomain",
                "vdeluser",
                "vdominfo",
                "vmkpasswd",
                "vmoduser",
                "vpasswd",
                "vpopbull",
                "vsetuserquota",
                "vuserinfo"
	);
	*/

	var $qassign = '/var/qmail/users/assign';

	/* constructor */
	function ServerAdminMail( )
	{
		// call our conf reader
		$this->core = new ServerAdminCore;
		//var_dump($this);
		$this->setProgramsAndDefaults( 'mail' );

		if($vbase = $this->getConfig('mail', 'vbase'))
		{
			$this->vbase = $vbase;
		}

		if($qassign = $this->getConfig('mail', 'qassign'))
		{
			$this->qassign = $qassign;
		}
		
		/*
		foreach($this->_apps as $app)
		{
			$sapp = preg_replace('/^v/', '', $app);

			$this->addProg( $app, $this->vbase . '/bin/' . $app );
			$this->addProg( $sapp, $this->vbase . '/bin/' . $app );
		}
		*/
	}

	function domainInfo( $domain, $flags = null ) {
		$ret = $this->execute('vdominfo',
				array_merge(
					(is_array($flags) ? $flags : array( )),
					array( $domain )
				),
				0,
				true
		);

		if($ret !== true) {
			if(!strstr( $this->exec_msg, "does not exist" ))
				return $this->raiseError("Unable to get domain info: " . $this->exec_msg);
			else
				return false;
		}

		return $this->exec_msg;
	}

	function _getDomainDirectory( $domain ) {
		$data = $this->domainInfo( $domain, array('-d') );

		if(empty($data))
			return $this->raiseError("Unable to get the domain directory of $domain: ".
							$this->exec_msg);
		
		return trim($data);
	}

	function getDomains( ) {
		$ret = $this->execute('vdominfo',
				array('-n'),
				0,
				true
		);

		if($ret !== true)
			return $this->raiseError("Unable to get domains: " . $this->exec_msg);

		$domains = array( );
		foreach(explode("\n", $this->exec_msg) as $line) {
			if(!strstr($line, '.'))
				continue;

			$line = trim($line);
			if(!in_array($line, $domains))
				$domains[] = $line;
		}

		return $domains;
	}

	function domainExists( $domain ) {
		$ret = $this->domainInfo( $domain );

		return ($ret ? true : false);
	}

	function addDomain( $domain, $passwd = null ) {
		if($err = $this->validate('domain', $domain))
		{
			return $this->raiseError( $err );
		}

		/* random password */
		if(empty($passwd))
			$passwd = '-r';

		$ret = $this->execute('vadddomain',
				array($domain, $passwd),
				0,
				true
		);

		if($ret !== true)
			return $this->raiseError("Unable to add domain: " . $this->exec_msg);

		return true;
	}

	function userExists( $domain, $user ) {
		$u = $this->mkUser( $domain, $user );

		$ret = $this->execute('vuserinfo',
				array( '-d', $u ),
				0,
				true
		);

		/* retuns true if they exist, false if not */
		return $ret;
	}

	function getUsers( $domain ) {
		$ret = $this->execute('vuserinfo',
			array('-n', '-D', $domain),
			0,
			true
		);

		if($ret !== true)
			return $this->raiseError("Unable to get users: " . $this->exec_msg);

		return explode("\n", $this->exec_msg);
	}

	function addUser( $domain, $user, $passwd = null, $args = null ) {
		$u = $this->mkUser( $domain, $user );
		$opts = array($u); 

		if($err = $this->validate('email', $u))
		{
			return $this->raiseError( $err );
		}

		if(!isset($passwd))
			$args['randompasswd'] = true;

		/* arguments, options */
		if(is_array($args)) {
			if($q = intval( $args['quota'] )) {
				$opts[] = '-q';
				$opts[] = $q;
			}

			if($opts['randompasswd'])
				$opts[] = '-r';
			else
				$opts[] = $passwd;
		} else {
			$opts[] = $passwd;
		}

		$ret = $this->execute('vadduser', $opts, 0, true);

		if($ret !== true)
		{
			$this->raiseError("Unable to add user ($user@$domain): " . $this->exec_msg);
		}
		
		return $ret;
	}

	function passwd( $domain, $user, $passwd ) {
		$u = $this->mkUser( $domain, $user );

		if(! $this->userExists( $domain, $user ) ) {
			return $this->raiseError("User does not exist");
		}

		$ret = $this->execute('vpasswd',
				array( $u, $passwd ),
				0,
				true
		);

		return ($ret === true ? true : false);
	}

	/* catchall */
	/* come back later */
	function catchAll( $domain, $user = null )
	{
		$str = sprintf("| %s/bin/vdelivermail '' ", $this->vbase);
		$dir = $this->_getDomainDirectory( $domain );
		
		if(! $dir )
		{
			return $this->raiseError("Unable to get file directory for domain ($domain)");
		}

		$file = sprintf("%s/.qmail-default", $dir);

		if(!file_exists($file))
		{
			return $this->raiseError("Unable to determine default delivery method.  Please contact system administrator.");
		}
		
		if(!isset($user))
		{
			/* want the current one */
			$fp = fopen($file, 'r') or $this->raiseError("Unable to open ($file)");
			while($line = fgets($fp, 1024))
			{
				$line = trim($line);
				$this->debug("in-line: " . $line);
				if(preg_match("/^\s*\|\s*\/(\S+)\/vdelivermail\s+['\"]{2}\s+(.*)$/i", $line, $m))
				{
					$this->debug("++match: " . $m[2]);
					if($m[2] != 'bounce-no-mailbox')
					{
						return trim($m[2]);
					}
				}
			}
			fclose($fp);
		}
		else
		{
			if($user === false)
			{
				/* want to remove it all together */
				return $this->catchAll( $domain, 'bounce-no-mailbox' );
			}
			else 
			{
				// special case here
				if($user != 'bounce-no-mailbox')
				{
					if($err = $this->validate('email', $user))
					{
						return $this->raiseError( $err );
					}
				}

				/* want to set it */
				$lines = file($file);
				$lines[count($lines)-1] = sprintf("%s%s\n", $str, $user);

				# write it now
				fwrite(fopen($file, 'w'), implode('', $lines));

				return true;
			}
		}

		return '';
	}

	function listDomainAliases( $domain = null ) {
		if(!is_file($this->qassign)) {
			return $this->raiseError("Unable to find file ($this->qassign)");
		}

		$aliases = array( );

		$fp = fopen($this->qassign, 'r') or $this->raiseError("Unable to open $this->qassign)");
		while($line = fgets($fp, 1024 * 10)) {
			list($alias, $dom) = explode(':', $line, 3);

			if(!($alias and $dom))
				continue;

			if(preg_match('/^\+(\S+)\-$/', $alias, $m)) {
				if($m[1] != $dom)
					$aliases[$dom][] = $m[1]; 
			}
		}
		fclose($fp);

		if(isset($domain)) {
			return (is_array($aliases[$domain]) ? $aliases[$domain] : array( ));
		} else {
			return $aliases;
		}
	}
	
	function domainAliasExists( $domain, $alias ) {
		$list = $this->listDomainAliases( $domain );

		if($list === false)
			// pass error through
			return $this->raiseError( );

		return (count($list) > 0 ? true : false);
	}
	
	function addDomainAlias( $domain, $alias ) {
		if($err = $this->validate('domain', $domain))
		{
			return $this->raiseError( $err );
		}

		if($this->domainAliasExists( $domain, $alias ))
			return $this->raiseError("Domain alias already exists");

		$ret = $this->execute('vaddaliasdomain',
				array( $alias, $domain ),
				0,
				true
		);

		if($ret !== true)
			return $this->raiseError("Error in adding domain alias: " .
							$this->exec_msg);

		return true;
	}

	function delDomainAlias( $domain, $alias ) {
		if(!$this->domainAliasExists( $domain, $alias ))
			return $this->raiseError("Domain alias does not exist in the first place"); 

		$ret = $this->execute('vdeldomain',
				array( $alias ),
				0,
				true
		);

		if($ret !== true)
			return $this->raiseError("Error removing alias: " . $this->exec_msg);

		return true;
	}

	function listUserAliases( $domain, $u = null ) {
		$who = (isset($u) ? $this->mkUser($domain, $u) : $domain);
		/* get all the aliases */
		$ret = $this->execute('valias',
				array('-s', $who),
				0,
				true
		);

		/*
		if($ret !== true)
			return $this->raiseError("Unable to get alias list: " . $this->exec_msg);
		*/

		$aliases = array( );
		$lines = explode("\n", $this->exec_msg);
		foreach($lines as $line) {
			$line = trim($line);

			// goes to a command
			if(strstr($line, '|'))
				continue;

			// is a comment
			if(strstr($line, ' -> #'))
			{
				continue;
			}

			list($addr, $to) = explode(' -> ', $line, 2);

			// non-fully qualified address
			if(!strstr($line, '@'))
				continue;

			// get 'real' user
			list($user) = explode('@', $addr, 2);
			if(!$user)
				continue;

			$aliases[strtolower($user)][$to]++;
		}

		// return stripped-down version
		foreach($aliases as $key => $parts) {
			print_r($parts);
			foreach($parts as $k => $v) {
				if(empty($k))
					continue;

				$stripped[$key][] = $k;
				$this->debug("$key -> $parts -> $k ($v)");
			}
		}

		return (isset($u) ? $stripped[$u] : $stripped);
	}

	function userAliasExists( $domain, $user, $alias ) {
		$aliases = $this->listUserAliases( $domain, $user );

		if($aliases === false)
			return $this->raiseError( );

		/* no aliases for said account */
		if(!is_array($aliases))
			return false;

		$this->debug("Existing aliases: " . implode(' ', $aliases) . " in?($alias)");

		// if $alias is false, then
		// simply the fact that we've gotten this far means the alias
		// exists fine.
		if(! $alias )
		{
			return true;
		}

		return (in_array($alias, $aliases) ? true : false);
	}

	function addUserAlias( $domain, $alias, $addr ) {
		$u = $this->mkUser($domain, $alias);

		// main alias
		if($err = $this->validate('email', $u))
		{
			return $this->raiseError( $err );
		}

		// address to forward it to
		if($err = $this->validate('email', $addr))
		{
			return $this->raiseError( $err );
		}

		if($this->userAliasExists( $domain, $alias, $addr ))
			return $this->raiseError("Alias already exists");

		$ret = $this->execute('valias', 
				array( '-i', $addr, $u ),
				0,
				true
		);

		if($ret !== true)
			return $this->raiseError("Unable to add alias: " . $this->exec_msg);

		return true;
	} 

	// pass the 3rd false of 'false' to remove the whole alias
	function delUserAlias( $domain, $alias, $addr = false ) {
		$this->debug("delUserAliase->in: ($domain) ($alias) ($addr)");

		if(! $this->userAliasExists( $domain, $alias, $addr ) )
			return $this->raiseError("Alias does not exist");

		/* remove whole address */
		if(! $addr )
		{
			$ret = $this->execute('valias',
					array('-d', $this->mkUser( $domain, $alias )),
					0,
					true
			);

			if($ret !== true)
				return $this->raiseError( $this->exec_msg );

			return true;
		}

		/* boooo... remove the single line */
		$dir = $this->_getDomainDirectory( $domain );
		if($dir === false)
			return $this->raiseError( );

		$file = sprintf("%s/.qmail-%s", $dir, $alias);

		if(!is_file($file))
			return $this->raiseError("$file does not exist");

		$final = array( );
		/* need to add file locking in a later version */
		$fp = fopen($file, 'r+') or $this->raiseError("Unable to open $file");
		while($line = fgets($fp, 1024 * 10)) {
			// remove the stupid '&' that may be leading the line
			$line = preg_replace('/^\&/', $line, '');
			
			$line = trim($line);
			if(empty($line))
				continue;

			$this->debug("'$line' != '$addr'");
			if($line != $addr)
				$final[] = $line;
		}
		fseek($fp, 0, 0);
		ftruncate($fp, 0);

		if(count($final) > 0) {
			fwrite($fp, implode("\n", $final));
			fwrite($fp, "\n");
		}
		fflush($fp);
		fclose($fp);

		/* size too small? */
		clearstatcache( );
		$this->debug("filesize: " . filesize($file));
		if(filesize($file) <= 1)
			unlink($file);

		/* finally all done */
		return true;
	}

	function delUser( $domain, $user ) {
		if(! $this->userExists( $domain, $user ))
			return $this->raiseError("User does not exist");

		$ret = $this->execute('vdeluser',
				array( $this->mkUser( $domain, $user )),
				0,
				true
		);

		if($ret !== true)
			return $this->raiseError( );

		return true;
	}

	function delDomain( $domain ) {
		if(! $this->domainExists( $domain ) )
			return $this->raiseError("Domain does not exist");

		/* remove aliased domains */
		$aliases = $this->listDomainAliases( $domain );

		/* pretty much ignore if it fails */
		foreach($aliases as $alias)
			$this->delDomainAlias( $domain, $alias );

		$ret = $this->execute('vdeldomain',
				array( $domain ),
				0,
				true
		);

		if($ret !== true)
			return $this->raiseError( );
		
		return true;
	}

	function mkUser( $domain, $user ) {
		return sprintf("%s@%s", $user, $domain);
	}

	/* just here incase it's needed in the future */
	function build( )
	{
		return true;
	}
}

?>
