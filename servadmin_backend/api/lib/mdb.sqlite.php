<?

/*
	Metadata storage module
	by Jon Coulter
*/

/*
	Updated 7/30/2003 to add 'smart' caching
	... which isn't needed, it turns out :)
*/

/*
	Complete re-write 7/27/2004
	Now requires php5 w/sqlite
*/

class MDB
{
	private $tree;
	private $base;
	private $db;

	function __construct( $tree, $base = null )
	{
		$this->tree = $tree . '.db';
	
		if(empty($base))
		{
			$core = new ServerAdminCore;
			$base = $core->database . '/' . $this->tree;
		}

		$this->base = $base;

		$this->validateDatabase( );
	}

	function __get( $key )
	{
		return $this->get( $key );
	}

	function __set( $key, $val )
	{
		return $this->set($key, $val);
	}

	function get( $key )
	{
		$res = $this->db->query(
			sprintf("select val from data where key = '%s'",
				sqlite_escape_string($key)
			)
		);

		if($res === false)
		{
			throw new Exception("Unable to query db");
		}

		if($res->valid() == false)
		{
			return array( );
		}
		else
		{
			$row = $res->fetchObject( );
			return unserialize( $row->val );
		}
	}

	function set( $key, $val )
	{
		$this->db->query(
			sprintf("replace into data (key, val) values ('%s', '%s')",
				sqlite_escape_string($key),
				sqlite_escape_string(serialize($val))
			)
		);
	}

	function save( )
	{
		// nada
	}

	private function validateDatabase( )
	{
		$dir = dirname($this->base);
		if(!is_dir($dir))
		{
			$this->mkpath( $dir );
		}

		$this->db = new SQLiteDatabase($this->base);

		$res = $this->db->query("select count(*) as total from sqlite_master where name = 'data'");
		
		if($res === false)
		{
			throw new Exception("Error getting rows back");
		}

		$row = $res->fetchObject( );

		if($row->total <= 0)
		{
			$this->db->queryExec("
				create table data
				( key varchar primary key, val varchar )
			");
		}

		return true;
	}

	/*
	var $tree = null;
	var $data = array( );
	var $base = null;

	// should we keep an in-memory cache? 
	var $docache = true;
	var $cache = array( );
	var $mtime = array( );

	function MDB( $tree, $base = null, $docache = true ) {
		$this->tree = $tree;

		if(empty($base)) {
			$core = new ServerAdminCore;
			$base = $core->database . '/' . $tree;
		}

		$this->base = $base;
		$this->docache = $docache;
		$this->read( $tree );
	}

	function get( $key ) {
		$val = $this->data[$key];
		
		return $val;
	}

	function set( $key, $val ) {
		return $this->data[$key] = $val;
	}

	function save( $tree = null ) {
		if(! $tree)
			$tree = $this->tree;
		$file = $this->base . '/' . $tree;
		$dir = dirname($file);

		if(!is_dir($dir))
			$this->mkpath($dir);

		//echo "SERIALIZE!\n";
		$data = serialize( $this->data );
		$fp = fopen($file, 'w') or die("Unable to open $file for write");
		flock($fp, LOCK_EX) or die("Unable to lock $file for write");
		fwrite($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);
	
		return true;
	}

	function read( $tree = null ) {
		if(! $tree)
			$tree = $this->tree;
		$file = $this->base . '/' . $tree;

		if(!is_file($file))
			return array( );

		$data = fread(fopen($file, 'r'), filesize($file));
		//echo "UNSERIALIZE!\n";
		$this->data = unserialize( $data );

		return true;
	}
	*/

	private function mkpath( $path )
	{
		$parts = explode('/',
				str_replace('//', '/', $path)
		);

		unset($path);
		foreach($parts as $part)
		{
			$path .= '/' . $part;

			if(!is_dir($path))
				mkdir($path) or die("Unable to make $path");
		}

		return true;
	}
}

?>
