<?
	/*
		Apache Config/Control API
		Note that this specifically controls virtualhosts
		... for now
		by Jon Coulter
	*/

require_once(dirname(__FILE__) . '/../core.php');
require_once(SA_BASE . '/lib/mdb.php');


class ServerAdminHTTPD extends ServerAdminBase
{
	var $mdb	= null;

	/* default values */
	var $defaults	= array(
		'ipaddr'	=> '127.0.0.1',
		'documentroot'	=> '/dev/null',
		'serveradmin'	=> 'me@myhosti.ext'
	);

	/* just some cleaner-looking keys */
	var $cleankeys 	= array(
		'documentroot'	=> 'DocumentRoot',
		'serveradmin'	=> 'ServerAdmin',
		'scriptalias'	=> 'ScriptAlias',
		'serveralias'	=> 'ServerAlias'
	);

	/* protected from a general buid */
	var $protected	= array(
		'extralines', 'serveraliases'
	);
	
	var $conf	= 'conf/httpd.conf';

	/* pid file */
	var $pidfile	= '/var/run/httpd.pid';
	
	//function ServerAdminHTTPD( ) {
	function __construct( )
	{
		$this->core = new ServerAdminCore;

		$this->mdb = new MDB( 'httpd' );		

		$this->conf = sprintf("%s/%s", dirname(__FILE__), $this->conf);

		$this->setProgramsAndDefaults( 'httpd' );

		if($conf = $this->getConfig('httpd', 'buildconf'))
		{
			$this->conf = $conf;
		}

		if($pidfile = $this->getConfig('httpd', 'pidfile'))
		{
			$this->pidfile = $pidfile;
		}
	}

	/* get current vhosts */
	function getVirtualHosts( )
	{
		$ret = $this->mdb->get('vhosts');

		if(!is_array($ret))
			$ret = array( );

		return $ret;
	}

	/* set the values for all vhosts */
	function setVirtualHosts( $ret )
	{
		$this->mdb->set( 'vhosts', $ret );
		$this->needbuild++;

		return true;
	}
	
	/* add a virtual host */
	function addVirtualHost( $vhost, $args = null )
	{
		$vhost = $this->cleanDomain( $vhost );

		if($err = $this->validate('domain', $vhost))
		{
			return $this->raiseError( $err );
		}
		
		if($this->virtualHostExists( $vhost ))
		{
			return $this->raiseError("[$vhost] already exists");
		}

		$ret = $this->getVirtualHosts( );

		$ret[$vhost] = $args;

		return $this->setVirtualHosts( $ret );
	}

	function deleteVirtualHost( $vhost )
	{
		if(!$this->validVirtualHost( $vhost ))
			return false;

		$ret = $this->getVirtualHosts( );
		unset($ret[$vhost]);

		return $this->setVirtualHosts( $ret );
	}

	function getAttribute( $vhost, $attribute )
	{
		if(! $this->validVirtualHost( $vhost ) )
			return $this->raiseError("$vhost is an invalid host");

		$ret = $this->getVirtualHosts( );

		return $ret[$vhost][$attribute];
	}

	function setAttribute( $vhost, $attribute, $args )
	{
		if(! $this->validVirtualHost( $vhost ) )
			return $this->raiseError("$vhost is an invalid host");

		$ret  = $this->getVirtualHosts( );

		if($args === false)
		{
			unset($ret[$vhost][$attribute]);
		}
		else
		{
			$ret[$vhost][$attribute] = $args;
		}

		return $this->setVirtualHosts( $ret );
	}

	/* 'extra' line(s) routines */
	function getExtraLines( $vhost )
	{
		if(! $this->validVirtualHost( $vhost ) )
			return $this->raiseError("$vhost is an invalid virtualhost");

		$ret = $this->getVirtualHosts( );
		$return = $ret[$vhost]['extralines'];

		if(!is_array($return))
			$return = array( );

		return $return;
	}

	function setExtraLines( $vhost, $lines )
	{
		if(! $this->validVirtualHost( $vhost ) )
			return $this->raiseError("$vhost is an invalid virtualhost");

		if(!is_array($lines))
			return $this->raiseError("2nd argument to setExtraLines() must be an array (of lines)");

		return $this->setAttribute( $vhost, 'extralines', $lines);
	}

	function setServerAdmin( $vhost, $email )
	{
		if(! $this->validVirtualHost( $vhost ) )
			return $this->raiseError("$vhost is an invalid virtualhost");

		return $this->setAttribute( $vhost, 'serveradmin', $email );
	}

	function setDocumentRoot( $vhost, $docroot )
	{
		return $this->setAttribute( $vhost, 'documentroot', $docroot );
	}

	function setScriptAlias( $vhost, $path )
	{
		return $this->setAttribute( $vhost, 'scriptalias', $path );
	}

	/*
		ServerAlias's
	*/
	function getServerAliases( $vhost )
	{
		if(($aliases = $this->getAttribute( $vhost, 'serveraliases' ))
			=== false)
		{
			return false;
		}

		return $aliases;
	}

	function serverAliasExists( $vhost, $alias )
	{
		$alias = $this->cleanDomain( $alias );
		if(($aliases = $this->getServerAliases( $vhost )) === false)
			return false;

		if(is_array($aliases) and in_array($alias, $aliases))
			return true;
		else
			return false;
	}

	function addServerAlias( $vhost, $alias )
	{
		$alias = $this->cleanDomain( $alias );

		if($this->serverAliasExists( $vhost, $alias ))
			return $this->raiseError("alias already exists");
		
		$aliases = $this->getServerAliases( $vhost );
		//if(($aliases = $this->getServerAliases( $vhost )) === false)
		//	return false;

		$aliases[] = $alias;

		return $this->setAttribute( $vhost, 'serveraliases', $aliases );
	}

	function deleteServerAlias( $vhost, $alias )
	{
		$alias = $this->cleanDomain( $alias );
		
		if(($aliases = $this->getServerAliases( $vhost )) === false)
			return true;

		unset($aliases[$alias]);

		if(count($aliases) == 0)
			$aliases = false;

		return $this->setAttribute( $vhost, 'serveraliases', $aliases );
	}

	function virtualHostExists( $vhost )
	{
		$vhost = $this->cleanDomain( $vhost );
		$ret = $this->mdb->get('vhosts');

		if(isset($ret[$vhost]))
			return true;
		else
			return false;
	}

	function validVirtualHost( &$vhost )
	{
		$vhost = $this->cleanDomain( $vhost );
		if(! $this->virtualHostExists( $vhost ) )
		{
			return $this->raiseError("Virtualhost [$vhost] does not exist");
		}

		return true;
	}

	function build( )
	{
		/* since build should be called every time,
		   if there's any slight change at all */
		$this->mdb->save( );

		if($this->needbuild or !is_file($this->conf))
			return $this->doBuild( );
		else
			return true;
	}

	function fixCase( $name )
	{
		if(isset($this->cleankeys[ $name ]))
		{
			return $this->cleankeys[$name];
		}
		else
		{
			return $name;
		}
	}

	function isProtected( $name )
	{
		$this->debug("is_protectd( $name, ( " . implode(',', $this->protected) . " ) )");
		if(in_array( $name, $this->protected ))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function doBuild( )
	{
		$_records = 0;
		$_start = time( );

		$this->debug("going to rebuild $this->conf");
		/* go-time */
		$fp = fopen($this->conf, 'w')
			or die("Unabel to open $this->conf for write");
		flock($fp, LOCK_EX) or die("Unable to lock $this->conf for write");
		fwrite($fp, "#\n# Config file rebuilt by ServerAdmin\n");
		fwrite($fp, sprintf("# [%s]\n#\n\n", date('Y-m-d H:i:s')));

		$ret = $this->getVirtualHosts( );
		//var_dump($ret);

		foreach($ret as $domain => $args)
		{
			$_records++;
			$this->debug("... adding entry for $domain");
			fwrite($fp, "# Creating entry for [$domain]\n");
			fwrite($fp, "<VirtualHost *>\n");

			/* ServerName entry */
			$servName = $domain;

			if(!preg_match('/^www\./i', $servName))
			{
				$servName = 'www.' . $servName;

				// fix serveraliases
				$has_domain = false;

				if(is_array( $args['serveraliases'] ))
				{
					$final = array( );

#					for($i = 0; $i < count($args['serveraliases']); $i++)
#					{
#						// remove the www.domain version
#						if($args['serveraliases'][$i] != $servName)
#						{
#							$final[] = $args['serveraliases'][$i];
#						}
#
#						if($args['serveraliases'][$i] == $domain)
#						{
#							$has_domain = true;
#						}
#					}
					foreach($args['serveraliases'] as $k => $v)
					{
						if($v != $servName)
						{
							$final[] = $v;

							if($v == $domain)
							{
								$has_domain == true;
							}
						}
					}

					$args['serveraliases'] = $final;
				}

				if(! $has_domain )
				{
					// add the domain as a server aliasxe
					$args['serveraliases'][] = $domain;
				}
			}

			//fwrite($fp, "\tServerName $domain\n");
			fwrite($fp, "\tServerName $servName\n");

			/* the rest */
			foreach($args as $k => $v)
			{
				if($this->isProtected( $k ))
					continue; 

				/* fix the name */
				$name = $this->fixCase( $k );

				/* array of items */
				if(is_array($v))
				{
					foreach($v as $item)
					{
						fwrite($fp, "\t$name $item\n");
					}
				}
				else
				{
					fwrite($fp, "\t$name $v\n");
				}
			}

			/* server aliases */
			if(is_array( $args['serveraliases'] ))
			{
				//var_dump($args);
				if(count($args['serveraliases']) > 0)
				{
					fwrite($fp, "\tServerAlias " .
						implode(' ', $args['serveraliases']) . "\n"
					);
				}
			}

			/* extra lines */
			if(is_array( $args['extralines'] ))
			{
				foreach($args['extralines'] as $line)
				{
					fwrite($fp, "\t$line\n");
				}
			}

			fwrite($fp, "</VirtualHost>\n\n");
		}

		fwrite($fp, sprintf("# [%d records rebuilt in %d second(s)]\n",
					$_records, (time() - $_start)
			)
		);

		flock($fp, LOCK_UN) or die("Unable to unlock $this->conf");
		fclose($fp);

		if(! $this->reHash( ) )
		{
			return $this->raiseError("Unable to rehash apache: " . $this->errmsg);
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
			//$this->raiseError = true;
			if(!$this->restartServer( ))
			{
				return $this->raiseError("Unable to restart apache: " . $this->errmsg);
			}
		}

		return true;
	}

	function stopServer( )
	{
		$this->debug("going to stop apache server");

		$key = 'apacheinit';
		if(!$this->progs[$key])
		{
			$key = 'apachectl';
		}
		$ret = $this->execute($key,
			array('stop'),
			0,
			true
		);
		$this->debug("done trying to stop");
		
		/* not a big deal if this doesn't cut it */
		return $ret;
	}

	function startServer( )
	{
		$this->debug("going to start apache server");

		$key = 'apacheinit';
		if(!$this->progs[$key])
		{
			$key = 'apachectl';
		}

		$ret = $this->execute($key,
			array('start'),
			0,
			true
		);
		$this->debug("return from trying to start ($ret)");

		if($ret !== true)
		{
			return $this->raiseError("Unable to start apache: " . $this->errmsg . " ($this->exec_msg)");
		}

		return true;
	}

	function restartServer( )
	{
		$this->stopServer( );
		return $this->startServer( );
	}

	function sigHUPValue( )
	{
		$v = intval($this->getConfig('httpd', 'sigusr1_value'));

		// USR1
		return ($v ? $v : 10);
	}
}

?>
