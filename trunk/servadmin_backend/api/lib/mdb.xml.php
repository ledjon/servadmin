<?

/*
	Metadata storage module
	by Jon Coulter
*/

/*
	Updated 7/30/2003 to add 'smart' caching
	... which isn't needed, it turns out :)
*/

ini_set("include_path", dirname(__FILE__) . ":" . ini_get("include_path"));

require_once("XML/Serializer.php");
require_once("XML/Unserializer.php");

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
		//$file = $this->base . '/' . $tree;
		$file = $this->base . '.xml';

		$dir = dirname($file);

		if(!is_dir($dir))
			$this->mkpath($dir);

		$options = array(
                    "indent"         => "    ",
                    "linebreak"      => "\n",
                    "defaultTagName" => "unnamedItem",
                    "typeHints"      => true
                );
		$s = new XML_Serializer( $options );
		$s->serialize( $this->data );

		//echo($s->getSerializedData( ));
		//die($file);

		//$data = serialize( $this->data );
		$data = $s->getSerializedData( );
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
		//$file = $this->base . '/' . $tree;
		$file = $this->base . '.xml';

		if(!is_file($file))
			return array( );

		$data = fread(fopen($file, 'r'), filesize($file));

		$u = new XML_Unserializer( );
		$u->unserialize( $data );
		
		//$this->data = unserialize( $data );
		$this->data = $u->getUnserializedData( );

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
