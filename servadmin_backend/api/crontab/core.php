<?
/*
	Crontab core class
	by Jon Coulter
*/

require_once(dirname(__FILE__) . '/../core.php');

class ServerAdminCrontab extends ServerAdminBase
{
	function ServerAdminCrontab( )
	{
		$this->core = new ServerAdminCore;
		$this->setOS( dirname(__FILE__) );
	}

	/* called when a user's crontab needs to be removed */
	function delUser( $user )
	{
		return $this->os->delUser( $user );
	}

	/* does the user have a crontab entry? */
	function userExists( $user )
	{
		return $this->os->userExists( $user );
	}

	function getEntries( $user, $ignore_no_crontab = false )
	{
		return $this->os->getEntries( $user, $ignore_no_crontab );
	}

	function setEntries( $user, $entries )
	{
		return $this->os->setEntries( $user, $entries );
	}

	function addEntry( $user, $times, $command )
	{
		return $this->os->addEntry( $user, $times, $command );
	}

	function getEmailTo( $user )
	{
		return $this->os->getEmailTo( $user );
	}

	function setEmailTo( $user, $emailto )
	{
		return $this->os->setEmailTo( $user, $emailto );
	}
}

?>
