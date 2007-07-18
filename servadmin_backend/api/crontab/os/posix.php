<?

class ServerAdminCrontab_POSIX extends ServerAdminBase
{
	var $emailTo = array( );
	var $entries = array( );

	function __construct( &$p )
	{
		$this->setParent( $p );

		$this->setProgramsAndDefaults( 'crontab' );
	}

	function delUser( $user )
	{
		$this->debug("CRONTAB USER DEL CALLED FOR [$user]");

		/* don't care too much if it fails */
		$ret = $this->execute('delcrontab',
				array( $user ),
				0,
				true
		);

		return true;
	}

	function userExists( $user )
	{
		$ret = $this->execute('crontab',
				array('-l', '-u', $user),
				0,
				true
		);

		return ($ret ? true : false);
	}

	function getEntries( $user, $ignore_no_crontab = false )
	{
		$return = array( );
		$ret = $this->execute('crontab',
				array('-l', '-u', $user),
				0,
				true
		);

		if($ret !== true)
		{
			if(! $ignore_no_crontab )
			{
				return $this->raiseError("Unable to get entries for $user: $this->exec_msg");
			}
		}

		$lines = explode("\n", $this->exec_msg);
		foreach($lines as $line)
		{
			$line = trim($line);
			if(empty($line) or preg_match('/^\s*#/', $line))
				continue;

			// emailto line
			if(preg_match('/^MAILTO=(.*?)$/i', $line, $m))
			{
				$this->emailTo[$user] = $m[1];
				continue;
			}

			$parts = array( );
			list(
				$parts['minute'],
				$parts['hour'],
				$parts['day'],
				$parts['month'],
				$parts['weekday'],
				$parts['command']
			) = preg_split('/\s+/', $line, 6);

			$return[] = $parts;
		}

		return $this->entries = $return;
	}

	function setEntries( $user, $entries )
	{
		// read in existing entries,
		// include any mailto=* type headers
		$this->getEntries( $user, true );

		$this->entries = $entries;

		return $this->saveCrontab( $user );
	}

	function saveCrontab( $user )
	{
		$lines = array( );

		foreach($this->entries as $entry) 
		{
			$lines[] = sprintf("%s %s %s %s %s %s\n",
					$entry['minute'],
					$entry['hour'],
					$entry['day'],
					$entry['month'],
					$entry['weekday'],
					$entry['command']
			);
		}

		$file = tempnam('/tmp', 'crontab');

		$fp = fopen($file, 'w') or die("Unable to open $file for write");

		// EMAIL=*
		if($emailto = $this->emailTo[$user])
		{
			fputs($fp, sprintf("MAILTO=%s\n", $emailto));
		}
		
		foreach($lines as $line)
		{
			fputs($fp, $line);
		}
		fclose($fp);

		$ret = $this->execute('crontab',
			array('-u', $user, $file),
			0,
			true
		);

		@unlink($file);
		
		if($ret !== true)
		{
			return $this->raiseError("Unable to insert crontab: $this->exec_msg");
		}

		/* done with it */
		return true;
	}

	function addEntry( $user, $times, $command ) {
		/* current entries */
		if($this->userExists( $user ))
		{
			$entries = $this->getEntries( $user, true );
		}
		else
		{
			$entries = array( );
		}

		if(count($times) != 5)
		{
			return $this->raiseError("Need 5 time entries... not " .
						count($times));
		}

		$entries[] = array(
				'minute'	=> array_shift($times),
				'hour'		=> array_shift($times),
				'day'		=> array_shift($times),
				'month'		=> array_shift($times),
				'weekday'	=> array_shift($times),
				'command'	=> $command
		);

		if(! $this->setEntries( $user, $entries ) )
		{
			return $this->raiseError("Unable to set entries for $user");
		}

		/* continue here */
		return true;
	}

	function getEmailTo( $user )
	{
		// this reads in the email line, among other things
		$this->getEntries( $user, true );

		return $this->emailTo[$user];
	}

	// pass null, false or '' to remove emailto entry
	function setEmailTo( $user, $emailto )
	{
		if( $emailto )
		{
			if($err = $this->validate('email', $emailto))
			{
				return $this->raiseError( $err );
			}
		}
		// this reads in the email line, among other things
		$this->getEntries( $user, true );

		$this->emailTo[$user] = $emailto;

		return $this->saveCrontab( $user );
	}
}

?>
