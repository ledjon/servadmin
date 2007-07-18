<?

class LedTemplate 
{
	var $_templates = array( );
	var $_args = array( );
	var $_parsed = array( );

	function LedTemplate( )
	{
		// nada
	}
	
	function loadTemplate( $key, $file )
	{
		if(file_exists($file)
			and filesize($file) > 0)
		{
			$this->_templates[$key] =
				fread(fopen($file, 'r'), filesize($file));
		}
	}
	
	function Set( $k, $v = null )
	{
		if(is_array($k))
		{
			foreach($k as $key => $val)
			{
				$this->Set($key, $val);
			}
		}
		else
		{
			$this->_args[$k] = $v;
		}
		
		return true;
	}
	
	function isValSet( $k )
	{
		return $this->_args[$k];
	}
	
	function Append( $k, $v )
	{
		$this->_args[$k] .= $v;
	}
	
	function Delete( $k )
	{
		unset($this->_args[$k]);
	}

	function Parsed( $key )
	{
		return $this->_parsed[$key];
	}
	
	function Parse( $key )
	{
		$tmp =  $this->_templates[$key];
		
		foreach($this->_args as $k => $v)
		{
			$tmp = str_replace('{' . $k . '}', $v, $tmp);
		}
		
		$this->_parsed[$key]++;
		return $tmp;
	}
}

?>
