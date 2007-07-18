<?
	/*
		Base-class for interface objects
	*/

require_once(dirname(__FILE__) . '/../core.php');

class ServerAdmin_InterfaceBase
{
	var $_type = null;

	function setType( $type )
	{
		/* relative to this physical file */
		require_once( dirname(__FILE__)  . '/../' . $type . '/core.php' );

		$this->_type = $type;

		return true;
	}
}
?>
