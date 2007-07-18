<?

require_once(dirname(__FILE__) . '/posix.php');

class ServerAdminCrontab_Linux extends ServerAdminCrontab_POSIX 
{
	function __construct( &$p )
	{
		parent::__construct( $p );
	}

	// No methods needed:
	// All methods are POSIX-compliant (as in, all crontab stuff seems universal)
}

?>
