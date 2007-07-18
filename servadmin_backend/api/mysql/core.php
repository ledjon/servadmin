<?

// notes:
// SET FOREIGN_KEY_CHECKS = 0;

/*
	$Id: core.php,v 1.2 2005/11/23 17:56:01 ledjon Exp $

	MySQL Configuration
	by Jon Coulter
*/

require_once(dirname(__FILE__) . '/../core.php');

// this needs to be found in the include_path
require("adodb/adodb.inc.php");

/*
 Note to self:
 adodb FUCKS with error handling
 (how much more annoying can that lib get?)
 so SoapFaults (or any exception) thrown from all
 of this may be ignored at times
*/	

class ServerAdminMySQL extends ServerAdminBase
{
	private $db = null;

	function __construct( )
	{
		$this->core = new ServerAdminCore;

		$this->setProgramsAndDefaults('mysql');

		// need to connect to the database
		$this->_connect( );
	}

	private function _connect( )
	{
		$this->db = ADONewConnection(
			sprintf("mysqlt://%s:%s@%s/mysql",
				$this->getConfig('mysql', 'user'),
				$this->getConfig('mysql', 'password'),
				$this->getConfig('mysql', 'host')
			)
		) or $this->raiseError("Unable to connect to mysql");
		$this->db->hideErrors = false;

		// set a debug level
		$this->db->debug = $this->doDebug;

		return true;
	}

	function listDatabases( $search )
	{
		$ret = array( );

		if(empty($search))
		{
			return $this->raiseError("Need search key");
		}

		$sql = sprintf("show databases like '%s\\_%%'", $search);

		$res = $this->db->Execute($sql)
			or $this->raiseError( $this->db->ErrorMsg( ) );

		while($row = $res->FetchRow( ))
		{
			$ret[] = $row[0];
		}

		return $ret;
	}

	function listAccess( $search )
	{
		$ret = array( );
		if(empty($search))
		{
			return $this->raiseError("need search key");
		}

		$sql = sprintf("select distinct Db, User from db where Db like '%s\\_%%' or Db like '%s\\_%%'",
					$search, $search
				);
		$res = $this->db->Execute($sql)
			or $this->raiseError( $this->db->ErrorMsg( ) );

		while($row = $res->FetchRow( ))
		{
			$ret[$row[0]][] = $row[1];
		}

		return $ret;
	}

	function listUsers( $search )
	{
		$ret = array( );

		if(empty($search))
		{
			return $this->raiseError("need search key");
		}

		$sql = sprintf("select distinct User from user where User like '%s\\_%%'", $search);

		$res = $this->db->Execute( $sql )
			or $this->raiseError( $this->db->ErrorMsg( ) );

		while($row = $res->FetchRow( ))
		{
			$ret[] = $row[0];
		}

		return $ret;
	}

	function createDatabase( $database )
	{
		if(! $database )
		{
			return $this->raiseError("Need database name to create!");
		}

		$sql = "create database " . $database;

		if(! $this->db->Execute($sql) )
		{
			return $this->raiseError( $this->db->ErrorMsg( ) );
		}

		return true;
	}

	function dropDatabase( $database )
	{
		if(! $database )
		{
			return $this->raiseError("Need database name to drop.");
		}

		$sql = "drop database " . $database;

		$this->db->Execute( $sql )
			or $this->raiseError( $this->db->ErrorMsg( ) );

		return true;
	}

	function createUser( $user, $pass )
	{
		if($err = $this->validate('user', $user))
		{
			return $this->raiseError( $err );
		}

		$sql = sprintf("grant usage on *.* to `%s`@localhost identified by ?", $user);

		$this->db->Execute( $sql, array( $pass ))
			or $this->raiseError( $this->db->ErrorMsg( ) );

		$this->flushPrivs( );

		return true;
	}

	function dropUser( $user )
	{
		$sql = array(
			'delete from user where User = ?',
			'delete from db where User = ?'
		);

		foreach($sql as $s)
		{
			$this->db->Execute( $s, array( $user ) )
				or $this->raiseError( $this->db->ErrorMsg( ) );
		}

		$this->flushPrivs( );

		return true;
	}

	function grantAccess( $user, $database, $access = 'all' )
	{
		if(! $access )
		{
			$access = 'all';
		}

		$sql = sprintf("grant %s on `%s`.* to `%s`@localhost",
				$access,
				$database,
				$user
			);

		$this->debug("Grant: $sql");

		$this->db->Execute($sql)
			or $this->raiseError( $this->db->ErrorMsg( ) );
		
		return true;
	}

	function revokeAccess( $user, $db, $access = 'all' )
	{
		if(! $access )
		{
			$access = 'all';
		}
	
		$this->db->Execute(
			sprintf("revoke %s on `%s`.* from `%s`@localhost",
				$access,
				$db,
				$user
			)
		) or $this->raiseError( $this->db->ErrorMsg( ) );

		$this->db->Execute("delete from db where User = ? and Db = ?", array( $user, $db ) )
			or $this->raiseError( $this->db->ErrorMsg( ) );

		$this->flushPrivs( );

		return true;
	}

	function changePass( $user, $pass  )
	{
		$this->db->Execute("update user set Password = password(?) where User = ?",
				array( $pass, $user )
			) or $this->raiseError( $this->db->ErrorMsg( ) );
		
		$this->flushPrivs( );
		
		return true;
	}

	function optimizeDatabase( )
	{
		// later 
		return false;
	}

	function repairDatabase( )
	{
		// later
		return false;
	}

	function dropTables( )
	{
		return false;
	}

	function dumpDatabase( )
	{
		return false;
	}

	private function flushPrivs( )
	{
		$this->db->Execute("flush privileges");
	}

	// this is used by the 'depends' methods
	// to delete databases and users that exist for a given user
	function delUser( $user )
	{
		$this->debug("DEPENDS CALLED FOR MYSQL ($user)");
		$users = $this->listUsers( $user );

		foreach($users as $u)
		{
			$this->dropUser( $u );
		}

		$dbs = $this->listDatabases( $user );

		foreach($dbs as $d)
		{
			$this->dropDatabase( $d );	
		}

		$this->flushPrivs( );

		return true;
	}
}

?>
