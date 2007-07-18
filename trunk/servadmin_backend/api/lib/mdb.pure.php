<?

/*
	Metadata storage module
	by Jon Coulter
*/

/*
	Updated 7/30/2003 to add 'smart' caching
	... which isn't needed, it turns out :)
*/

class MDB
{
	var $tree = null;
	var $data = array( );
	var $base = null;

	/* should we keep an in-memory cache? */
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
	
	function mkpath( $path ) {
		$parts = explode('/',
				str_replace('//', '/', $path)
		);

		unset($path);
		foreach($parts as $part) {
			$path .= '/' . $part;

			if(!is_dir($path))
				mkdir($path) or die("Unable to make $path");
		}

		return true;
	}
}

?>
