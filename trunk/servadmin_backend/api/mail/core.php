<?

/*
	Mail Class
	by Jon Coulter
*/

require_once(dirname(__FILE__) . '/../core.php');

/* this defines which mail server we're going to use */
if(!defined('MAIL_SERVER'))
{
	// the default type of server
	// this can be overriden by setting:

	// [mail]
	// server = x
	// # where 'x' is the mail server class to use

	// in the sa.conf file

	// get an instance of the core
	$_core = new ServerAdminCore;
	$_type = $_core->getConfig('mail', 'server');

	if(!$_type)
	{
		$_type = 'vpopmail';
	}

	define('MAIL_SERVER', $_type);
}

require_once(dirname(__FILE__) . '/servers/' . MAIL_SERVER . '.php');

/* this class functions a bit different then
   the other classes in the api
*/
class ServerAdminMailBase extends ServerAdminMail
{
	// this is never really called
	// I just like to confuse anybody that is reading this :)
	function ServerAdminMailBase( )
	{
		/* init parent class */
		$this->ServerAdminMail( );
	}
}

?>
