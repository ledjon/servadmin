<?

/*

	A Set of classes to handle
	javascript requests
	
	Primarily the parsing of incoming data and forming of outgoing data

*/

// should be at the top of *every* page that returns data
if(!defined('AJAX_DISABLE_SEND_NO_CACHE'))
{
	AJAX_Base::sendNoCache( );
}

class AJAX_Handler extends AJAX_Base
{
	function AJAX_Handler( )
	{
	
	}
	
	function GetData( )
	{
		$in = $_POST['_ajax_data'];
		
		$s = new AJAX_Serializer( );
		
		return $s->Deserialize( $in );
	}
	
	function SendResponse( $obj )
	{
		$s = new AJAX_Serializer( );
		
		$data = $s->Serialize( $obj );
		
		$d = new AJAX_Data( );
		
		$d->Add('_ajax_response', $data);
		
		echo $d->as_string( );
	}
}

class AJAX_Data extends AJAX_Base
{
	var $_parts = array( );
	
	function AJAX_Data( $data = null )
	{
		if(is_array($data))
		{
			foreach($data as $k => $v)
			{
				$this->Add( $k, $v );
			}
		}
	}
	
	function as_string( )
	{
		// this actually just re-seralizes the incoming data
		$s = new AJAX_Serializer( );
		
		return $s->Serialize( $this );
	}
	
	function Add( $k, $v )
	{
		$this->_parts[$k] = $v;
	}
	
	function Exists( $k )
	{
		return isset($this->_parts[$k]);
	}
	
	function Remove( $k )
	{
		$ret = $this->_parts[$k];
		
		unset($this->_parts[$k]);
		
		return $ret;
	}
	
	function Get( $k )
	{
		return $this->_parts[$k];
	}
	
	function Size( )
	{
		return count($this->_parts);
	}
	
	function keys( )
	{
		return array_keys($this->_parts);
	}
}

class AJAX_Serializer extends AJAX_Base
{
	function AJAX_Serializer( )
	{
	
	}
	
	function Deserialize( $in )
	{
		$d = new AJAX_Data( );
		
		$in = str_replace("\r", '', $in);
		$parts = explode("\n", $in);

		for($i = 0; $i < count($parts); $i++)
		{
			$kv = explode('=', $parts[$i], 2);

			$k = $kv[0];
			$v = rawurldecode($kv[1]);
			$d->Add($k, $v);
		}
		
		return $d;
	}
	
	function Serialize( $obj )
	{
		// 'obj' needs to be an AJAX_Data object
		$ret = '';

		$keys = $obj->Keys( );

		foreach($keys as $k)
		{
			if($ret != '')
			{
				$ret .= "\n";
			}
			
			$ret .= $k . '=' . rawurlencode($obj->Get($k));
		}

		return $ret;
	}
}

class AJAX_Base
{
	function sendNoCache( )
	{
		// Date in the past
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	
		// always modified
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	
		// HTTP/1.1
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
	
		// HTTP/1.0
		header("Pragma: no-cache");
	}
}

?>