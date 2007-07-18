<?

/*
	DNS Class
	Designed for bind 9, of course
	by Jon Coulter

	... incomplete
*/

require_once(dirname(__FILE__) . '/../core.php');
require_once(SA_BASE . '/lib/mdb.php'); 

class ServerAdminDNS extends ServerAdminBase
{
	var $mdb = null;

	var $defaults = array(
		'ipaddr'=> '127.0.0.1',
		'ttl'	=> '24h',
		'ns'	=> array('localhost'),
		'soa'	=> 'localhost hostmaster.localhost.',
		'mx'	=> array( '10' => '127.0.0.1')
	);

	/* our location of the built files */
	var $sfile	= 'build/named.%s';
	var $conf	= 'build/named.sa.conf';

	/* pid file */
	var $pidfile	= '/var/run/named.pid';

	function __construct( )
	{
		$this->core = new ServerAdminCore;
		$this->sfile = sprintf("%s/%s", dirname(__FILE__), $this->sfile);
		$this->conf = sprintf("%s/%s", dirname(__FILE__), $this->conf);
		$this->mdb = new MDB( 'dns' );

		$this->setProgramsAndDefaults( 'dns' );

		if($pidfile = $this->getConfig('dns', 'pidfile'))
		{
			$this->pidfile = $pidfile;
		}

		if($sfile = $this->getConfig('dns', 'named_file_format'))
		{
			$this->sfile = $sfile;
		}

		if($conf = $this->getConfig('dns', 'named_conf'))
		{
			$this->conf = $conf;
		}
	}

	function getDomains( )
	{
		$ret = $this->mdb->get('domains');

		if(!is_array($ret))
			$ret = array( );

		return $ret;
	}

	/* warning!!!
	   do not use the return to this to send to
	   setDomains()... infact don't send the whole thing
	   directly to any of these functions!
	*/
	function getDomain( $domain )
	{
		if(!$this->validDomain( $domain ))
			return $this->raiseError("$domain does not exist");

		$ret = $this->getDomains( );

		return $ret[$domain];
	}

	function setDomains( $domains )
	{
		$this->mdb->set( 'domains', $domains );
		$this->needbuild = true;

		return true;
	}
	
	function addDomain( $domain, $args )
	{
		if($err = $this->validate('domain', $domain))
		{
			return $this->raiseError( $msg );
		}

		$domain = $this->cleanDomain($domain);
		if( $this->domainExists( $domain ) )
		{
			return $this->raiseError("Domain [$domain] exists");
		}

		$ret = $this->getDomains( );
		$ret[$domain] = $args;
		$ret[$domain]['mtime'] = time( );

		return $this->setDomains( $ret );
	}

	function domainExists( $domain )
	{
		$domain = $this->cleanDomain( $domain );
		$domains = $this->getDomains( );

		if(isset($domains[$domain]))
			return true;
		else
			return false;
	}

	function deleteDomain( $domain )
	{
		if(!$this->validDomain( $domain ))
			return false;
		
		$ret = $this->getDomains( );
		unset($ret[$domain]);

		// remove the zone file
		@unlink( sprintf($this->sfile, $domain) );
		$this->needbuild = true; // rebuild the named.conf file, at least

		return $this->setDomains( $ret );
	}

	function getAttribute( $domain, $attribute )
	{
		if(!$this->validDomain( $domain ))
			return false;

		$ret = $this->getDomains( );

		return $ret[$domain][$attribute];
	}

	function setAttribute( $domain, $attribute, $value )
	{
		if(!$this->validDomain( $domain ))
			return false;

		$ret = $this->getDomains( );
		if($value === false)
		{
			unset( $ret[$domain][$attribute] );
		}
		else
		{
			$ret[$domain][$attribute] = $value;
		}
		$ret[$domain]['mtime'] = time( );


		return $this->setDomains( $ret );
	}

	function setTTL( $domain, $ttl )
	{
		return $this->setAttribute( $domain, 'ttl', $ttl );
	}

	/* can accept multiple args for $ns */
	function setNS( $domain, $ns )
	{
		return $this->setAttribute( $domain, 'ns', $this->_expand_to_array( $ns ) );
	}

	/* takes multiple args, but they have to be assoc,
		or be expanded to such things */
	function setMX( $domain, $mx )
	{
		return $this->setAttribute( $domain,
				'mx', $this->_expand_to_array( $mx, 10 ) );
	}

	function setDefaultIP( $domain, $ipaddr )
	{
		/* if they send an ip, validate it */
		if($ipaddr)
		{
			if(! $this->validIP( $ipaddr ) )
			{
				return $this->raiseError("$ipaddr is not in correct format");
			}
		}

		return $this->setAttribute( $domain, 'defaultip', $ipaddr );
	}

	function setPrimaryIP( $domain, $ipaddr )
	{
		if(!$this->validDomain( $domain ))
			return false;

		if(! $this->validIP( $ipaddr ) )
		{
			return $this->raiseError("$ipaddr is not in correct format");
		}
		
		return $this->setAttribute( $domain, 'ipaddr', $ipaddr );
	}

	/* note that $opt can be an ip or 'false' to remove the subdomain */
	function setSubDomain( $domain, $sub, $opt = null )
	{
		if(!$this->validDomain( $domain ))
			return false;

		$ret = $this->getDomains( );

		if(isset($opt) and !$opt)
		{
			unset($ret[$domain]['subdomains'][$sub]);
		}
		else
		{
			/* set the ip = null, then fill it in with the
			   default value at build-time
			*/
			$ipaddr = ($opt ? $opt : null);
			if( isset($ipaddr) and !$this->validIP( $ipaddr ) )
			{
				return $this->raiseError("$ipaddr is not in correct format");
			}
			else
			{
				$ret[$domain]['subdomains'][$sub] = $ipaddr;
			}
		}
		$ret[$domain]['mtime'] = time( );

		return $this->setDomains( $ret );
	}

	function setCName( $domain, $sub, $opt = null )
	{
		if(!$this->validDomain( $domain ))
			return false;

		$ret = $this->getDomains( );

		if(isset($opt) and !$opt)
		{
			unset($ret[$domain]['cnames'][$sub]);
		}
		else
		{
			$ret[$domain]['cnames'][$sub] = $opt;
		}
		$ret[$domain]['mtime'] = time( );

		return $this->setDomains( $ret );
	}

	function validDomain( &$domain )
	{
		$domain = $this->cleanDomain( $domain );
		if(! $this->domainExists( $domain ) )
		{
			return $this->raiseError("Domain [$domain] does not exist");
		}

		// TODO: add interface to the validator classes here

		return true;
	}

	/* call doBuild() if needed */
	function build( )
	{
		$this->mdb->save( );

		if($this->needbuild or !is_file($this->conf))
		{
			return $this->doBuild( );
		}
		else
		{
			return true;
		}
	}

	/* rebuild data files */
	function doBuild( ) 
	{
		/* get current domaisn */
		$domains = $this->getDomains( );

		foreach($domains as $domain => $args)
		{
			unset($data);
			$file = sprintf($this->sfile, $domain);
			$incfile = $file . '.include';

			/* do we rebuild this domain? */
			$keepgoing = false;
			clearstatcache( );
			if(
				(is_file($incfile) and filemtime($incfile) != $args['mtime'])
				or
				(!is_file($file)) // or (is_file($file) and filemtime($file) != $args['mtime']))
			) {
				$keepgoing = true;
			}
			
			if(!$keepgoing)
				continue;

			$this->debug("going to build for $domain");

			/* ttl */
			$data = sprintf("\$TTL %s\n", $this->orDefault($args, 'ttl'));

			/* header stuff */
			$data .= sprintf(";\n; zone file for [%s]\n; build by ServerAdmin at [%s]\n;\n",
					$domain, date('Y-m-d H:i:s')
			);

			/* more header stuff */
			$data .= sprintf("; Do not manually edit this BIND file\n");
			$data .= sprintf("; if you want to include your own entries, create\n");
			$data .= sprintf("; a file of the same name with a .include suffix (%s.include)\n",
					basename($file)
			);
			$data .= sprintf("; and it will be included on the next build\n;\n\n");

			/* soa */
			$data .= sprintf("@\tIN\tSOA\t%s (\n", $this->orDefault($args, 'soa'));
			
			/* serial and such data */
			// fill with more dynamic info, later
			$data .= sprintf("\t\t\t%d\n", time());
			$data .= sprintf("\t\t\t10800\n");
			$data .= sprintf("\t\t\t3600\n");
			$data .= sprintf("\t\t\t604800\n");
			$data .= sprintf("\t\t\t86400 )\n");

			/* name servers */
			if(!is_array($args['ns']))
				$args['ns'] = (array)$this->orDefault($args, 'ns');
			foreach($args['ns'] as $ns)
				$data .= sprintf("@\t\tIN\tNS\t%s.\n", $ns);

			/* mx records */
			if(!is_array($args['mx']))
				$args['mx'] = $this->orDefault($args, 'mx');
			foreach($args['mx'] as $pri => $mx)
				$data .= sprintf("@\t\tIN\tMX\t%d\t%s\n", $pri, $mx);

			/* root ip address */
			$data .= sprintf("@\t\tIN\tA\t%s\n", $this->orDefault($args, 'ipaddr'));

			/* other subdomains (such as www) */
			if(is_array($args['subdomains']) and count($args['subdomains']) > 0)
			{
				foreach($args['subdomains'] as $sub => $ip)
				{
					if(!isset($ip))
						$ip = $this->orDefault($args, 'ipaddr');

					$data .= sprintf("%s\t\tIN\tA\t%s\n", $sub, $ip);
				}
			}

			/* cnames? */
			if(is_array($args['cnames']) and count($args['cnames']) > 0)
			{
				foreach($args['cnames'] as $sub => $ref)
				{
					$data .= sprintf("%s\t\tIN\tCNAME\t%s.\n", $sub, $ref);
				}
			}

			/* catch all? */
			if($defip = $args['defaultip']
				or $defip = $this->orDefault($args, 'defaultip'))
			{
				$data .= sprintf("*\t\tIN\tA\t%s\n", $defip);
			}

			$fp = fopen($file, 'w') or die("Unable to open zone file $file for write");
			flock($fp, LOCK_EX) or die("Unable to lock $file for write");
			fwrite($fp, $data);

			/* now, .include files? */
			if(is_file($incfile))
			{
				$f = fopen($incfile, 'r') or die("Unable to open $file");
				fwrite($fp, "\n; Data included from\n; [$incfile]\n");
				fwrite($fp, fread($f, filesize($incfile)));
				fclose($f);
			}
			
			flock($fp, LOCK_UN) or die("Unable to unlock $file");
			fclose($fp);

			/* set mtime */
			if($args['mtime'])
			{
				@touch($file, $args['mtime']); 
				if(is_file($incfile))
					@touch($incfile, $args['mtime']);
			}

		}

		$this->debug("going to rebuild $this->conf");
		ksort($domains);

		/* rewrite the named.conf include file */
		$fp = fopen($this->conf, 'w')
				or die("Unable to open $this->conf for writing");
		flock($fp, LOCK_EX) or die("Unable to lock $this->conf for writing");
		fwrite($fp,
			sprintf("// Rebuilt by ServerAdmin [%s]\n",
				date('Y-m-d H:i:s')
			)
		);
		foreach($domains as $domain => $args)
		{
			fwrite($fp,
				sprintf("// entry for [%s]\n", $domain)
			);
			fwrite($fp,
				sprintf("zone \"%s\" IN {\n" .
				        "	type master;\n" .
					"        file \"%s\";\n" .
					"};\n\n", $domain, sprintf($this->sfile, $domain)
				)
			);
		}
		flock($fp, LOCK_UN) or die("Unable to unlock $this->conf");
		fclose($fp);

		/* rehash named */
		if(! $this->reHash( ) )
		{
			return $this->raiseError("Unable to rehash named server: " . $this->errmsg);
		}

		return true;
	}

	/* make sure named is up and running */
	function reHash( )
	{
		if($this->isRunning( ))
		{
			if(!$this->sendHUP( ))
			{
				return $this->raiseError("Unable to HUP: " . $this->errmsg);
			}
		}
		else
		{
			if(!$this->restartServer( ))
			{
				return $this->raiseError("Unable to restart named: " . $this->errmsg);
			}
		}

		return true;
	}

	function stopServer( )
	{
		$this->debug("going to stop named server");
		$ret = $this->execute('namedctl',
			array('stop'),
			0,
			true
		);
		
		/* not a big deal if this doesn't cut it */
		return true;
	}

	function startServer( )
	{
		$this->debug("going to start named server");
		$ret = $this->execute('namedctl',
			array('start'),
			0,
			true
		);

		if($ret !== true)
		{
			return $this->raiseError("Unable to start named: " . $this->exec_msg);
		}

		return true;
	}

	function restartServer( )
	{
		$this->stopServer( );
		return $this->startServer( );
	}

	/* *very* cheap validation */
	function validIP( $ipaddr )
	{
		if(preg_match('/^\d+\.\d+\.\d+\.\d+$/', $ipaddr))
			return true;
		else
			return false;
	}

	/* $key == default 'key' value, of not an array */
	function _expand_to_array( $arg, $key = 0 )
	{
		return is_array($arg) ?
			$arg :
			array( $key => $arg );
	}
}

?>
