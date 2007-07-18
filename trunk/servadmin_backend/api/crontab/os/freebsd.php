<?

require_once(dirname(__FILE__) . '/posix.php');

class ServerAdminCrontab_FreeBSD extends ServerAdminCrontab_POSIX 
{
	function __construct( &$p )
	{
		parent::__construct( $p );
	}

	// Works the same on freebsd as any other POSIX system
}

?>
