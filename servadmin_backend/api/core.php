<?

/*
	Core of the API
	by Jon Coulter
*/

/* define our api-root */
define('SA_BASE' , dirname(__FILE__));

/* hup signal handler */
define('SIGHUP', 1);

class ServerAdminCore extends ServerAdminBase
{
	var $config = array( );
	var $depends 	= array(
			'user'	=> array(
				'crontab',
				'mysql'
			)
	);

	var $database	= null; 

	// existing objects 
	var $objs	= array( );
	
//	function ServerAdminCore( )
	function __construct( )
	{
		// $this->loadDeps( );
		$this->dir = dirname(__FILE__);

		// change this to be configurable on-the-fly at a later date 
		$this->database = $this->dir . '/.metadata';

		$this->readConfig( );
	}

	function readConfig( $file = null )
	{
		if(!isset($file))
		{
			$file = realpath(dirname(__FILE__) . '/../etc/sa.conf');
		}

		if(!is_file($file))
		{
			$this->raiseError("Unable to locate configuration file: $file");
		}

		$section = '';
		$fp = fopen($file, 'r');
		while($line = fgets($fp, 4096))
		{
			$line = trim($line);

			// skip comments
			if($line[0] == '#'
				|| empty($line))
			{
				continue;
			}

			// $this->debug("line: " . $line);

			// match section header
			if(preg_match('/\[([^\]]+)\]/', $line, $m))
			{
				$section = strtolower(trim($m[1]));
				continue;
			}
			
			// no section title?
			if(! $section )
			{
				continue;
			}

			$parts = preg_split('/\s*=\s*/', $line, 2);

			list($key, $value) = $parts;

			// variables for the value
			$value = preg_replace('/\$([a-z\_]+):([a-z\_]+)/e', '$this->config[\'\1\'][\'\2\']', $value);
			//$this->debug(" $key : $value ");

			// handle @ and % pieces
			// arrays
			if($value[0] == '@')
			{
				$value = trim(substr($value, 1));

				$value = preg_split('/\s+/', $value);
			}
			else
			{
				// hashes
				if($value[0] == '%')
				{
					$new_value = array( );
					$value = trim(substr($value, 1));

					$p = preg_split('/\s+/', $value);
					
					foreach($p as $item)
					{
						list($k, $v) = explode(':', $item);
						//$this->debug("HASH: $k -> $v");

						$new_value[$k] = $v;
					}

					$value = $new_value;
				}
			}
			

			$this->config[$section][$key] = $value;
		}
		fclose($fp);
	}


	/* go down through the dep tree
		and return things that depend on a given class
	*/
	function depends( $class, $method = null )
	{
		$ret = array( );

		if(is_array($this->depends[$class]))
		{
			foreach($this->depends[$class] as $cls)
			{
				$this->importClass( $cls );
				//$file = $this->dir . '/' . $cls . '/core.php';
				//if(is_file( $file ))
				//{
					//require_once($file);
					$cls = 'ServerAdmin' . $cls; 

					//print_r(get_declared_classes());
					$this->debug("seeing if $cls exists");
					if(class_exists( $cls ))
					{
						$this->debug("calling new $cls()");
						$obj = new $cls;

						// now, does the method exist?
						if(isset($method) and !method_exists($obj, $method))
							continue;

						$ret[] = $obj;
					}
				//}
			}
		}

		return $ret;
	}

	function importClass( $cls )
	{
		$file = $this->dir . '/' . strtolower($cls) . '/core.php';

		if(!file_exists($file))
		{
			$this->raiseError("Unable to find file: $file ($cls)");
		}

		// now just bring it in
		require_once($file);
	}
}

class ServerAdminBase
{
	var $doDebug	= true;
	var $raiseError = true;
	var $progs	= array( );

	//function ServerAdminBase( ) {
	function __construct( )
	{
		/* nothing here... yet */
	}

	function getValidator( $type )
	{
		require_once(dirname(__FILE__) . '/validators/' . $type . '.php');

		$cls = 'Validator_' . $type;

		return new $cls( $this );
	}

	function validate( $type, $param )
	{
		$obj = $this->getValidator( $type );

		return $obj->doValidation( $param );
	}

	function log( $msg )
	{
		// nothing yet
	}

	function debug( $msg )
	{
		if(! $this->doDebug)
			return;
		if(!is_resource($this->fp_stderr))
		{
			$this->fp_stderr = @fopen('php://stderr', 'w');
		}

		$parts = explode("\n", $msg);

		$i = 0;
		foreach($parts as $line)
		{
			if($i++ > 0)
				$line = "\t... $line";
			if(is_resource($this->fp_stderr))
				fputs($this->fp_stderr, "*** DEBUG: $line\n");
			else
				echo "*** DEBUG: $line\n";
		}
	}

	// read the configuration that was
	// parsed by our parent
	function getConfig( $section, $key = null )
	{
		if($this->p)
		{
			return $this->p->getConfig( $section, $key );
		}

		if($this->core)
		{
			return $this->core->getConfig( $section, $key );
		}

		if(! isset($key) )
		{
			return is_array($this->config[$section]) ? $this->config[$section] : array( );
		}

		return $this->config[$section][$key];
	}

	// set the OS class for the given api
	function setOS( $dir )
	{
		$dir .= '/os';

		if(!is_dir($dir))
		{
			// no directory for this
			return true;
		}

		// let the config override posix()
		$type = $this->getConfig('global', 'os_override');


		$this->osname = $type;
		if(! $this->osname )
		{
			$uname = posix_uname( );
			$this->osname = $uname['sysname'];
		}

		// unable to get an os name at all!
		if(!$this->osname)
		{
			return $this->raiseError( "Unable to find the OS name" );
		}

		$this->osname = strtolower($this->osname);

		$file = $dir . '/' . strtolower($this->osname) . '.php';

		if(!is_file($file))
		{
			return $this->raiseError(
				"Unable to find (file) OS-specific class for this partent class ($this->osname)"
			);
		}

		// do the require
		require_once($file);

		$class = get_class( $this );
		$class .= '_' . $this->osname;

		if(!class_exists( $class ))
		{
			return $this->raiseError(
				"Unable to find class ($class) for this partent class"
			);
		}

		$this->debug("new $class( \$this )");
		$this->os = new $class( $this );
		return true;
	}

	function addProg( $prog, $cmd )
	{
		$this->progs[$prog] = $cmd;
	}

	function execute( $inprog, $args = null, $code = 0, $stderr = false )
	{
		if(! $prog = $this->progs[$inprog] )
		{
			return $this->raiseError(
				"Unable to find executable for $inprog"
			);
		}

		if(!is_executable( $prog ))
		{
			return $this->raiseError("Unable to execute needed program ($prog)");
		}

		//$this->debug(var_export($args, true));

		$parts = array_merge(
			array( $prog ),
			(is_array($args) ? $args : array( ))
		);

		foreach($parts as $k => $v) 
		{
			$parts[$k] = escapeshellarg( $v );
		}

		$cmd = implode(' ', $parts);

		if($stderr)
			$cmd .= ' 2>&1';

		$this->debug("Going to run \"$cmd\"");
		unset($msg);
		exec($cmd, $msg, $this->exec_ret);

		$this->exec_msg = implode("\n", $msg);

		$this->debug("return message (want=$code : got=$this->exec_ret): \n" . $this->exec_msg);
		
		return ($this->exec_ret == $code ? true : false); 
	}

	function setParent( &$p )
	{
		$this->p =& $p;
	}

	function getPID( $file = null )
	{
		if(!isset($file))
			$file = $this->pidfile;

		if(empty($file) or !is_file($file))
		{
			$this->debug("no pid file ($file)");
			return 0;
		}

		$fp = fopen($file, 'r') or die("Unable to open $file");
		$pid = intval(fgets($fp, 100));
		fclose($fp);

		return ($pid == 0 ? false : $pid);
	}

	function isRunning( $pid = null ) {
		if(!isset($pid))
			$pid = $this->getPID( );

		if($pid <= 0)
			return false;

		$this->debug("going to see if process is running");
		if(!posix_kill($pid, 0))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	function sendHUP( $pid = null )
	{
		if(!isset($pid))
			$pid = $this->getPID( );

		$hup = SIGHUP;
		if(method_exists( $this, 'sigHUPValue' ))
		{
			$hup = $this->sigHUPValue( );
		}

		$this->debug("going to send HUP to ($pid): with sig value of: " . $hup);

		if(!posix_kill($pid, $hup))
		{
			return $this->raiseError("Unable to HUP service (no access, or not running?)");
		}

		return true;
	}

	function setExceptionHandler( $h )
	{
		$this->exceptionHandler = $h;
	}

	function getExceptionHandler( )
	{
		$ret = $this->exceptionHandler;

		// recurse to pickup parent exception handlers
		if((!$ret) && $this->p)
		{
			$ret = $this->p->getExceptionHandler( );
		}

		return $ret;
	}

	// if no error is passed, the old one is kept
	function raiseError( $msg = null )
	{
		if(isset($msg))
			$this->errmsg = $msg;

		if($this->raiseError)
		{
			$this->debug("going to raiseError($msg)");

			//die("Error:\n$msg\n");
			$ex_handler = $this->getExceptionHandler( );

			if( $ex_handler )
			{
				$this->debug("sending to handler ($ex_handler)");
				return call_user_func($ex_handler, $msg);
			}
			else
			{
				$this->debug("using default exception handler");
				throw new Exception("Error:\n$msg\n");
			}
		}

		return false;
	}

	function fillDefaults( $args, $defaults, $arg )
	{
		foreach($defaults as $k => $v)
		{
			if( empty($args[$k]) )
			{
				$val = $v;
			}
			else
			{
				$val = $args[$k];
			}

			$args[$k] = sprintf($val, $arg);
		}

		return $args;
	}

	function cleanDomain( $domain )
	{
		$domain = strtolower(trim($domain));

		return $domain;
	}

	function orDefault( $args, $key )
	{
		if(!isset($args[$key])
			and isset($this->defaults[$key]))
		{
			return $this->defaults[$key];
		}
		else
		{
			return $args[$key];
		}
	}

	function save( ) {
		if(is_object($this->mdb))
			return $this->mdb->save( );

		return true;
	}

	function setProgramsAndDefaults( $section )
	{
		// configuration values imported heer
		$conf = $this->getConfig($section);

		foreach($conf as $key => $val)
		{
			list( $k, $v ) = explode(':', $key);

			if($v)
			{
				if($k == 'program')
				{
					$this->addProg( $v, $val );
				}

				if($k == 'defaults')
				{
					$this->defaults[$v] = $val;
				}
			}
		}
	}

	private function notImplemented( )
	{
		return $this->raiseError("Unimplemented for this OS");
	}
}

?>
