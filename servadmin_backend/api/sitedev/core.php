<?

/*
	$Id: core.php,v 1.3 2006/01/29 02:55:55 ledjon Exp $

	SiteDev classes
	- Used to create/delete sites (and all dependencies)
	by Jon Coulter
*/

require_once(dirname(__FILE__) . '/../core.php');

class ServerAdminSiteDev extends ServerAdminBase
{
	private $func = null;

	function __construct( )
	{
		$this->core = new ServerAdminCore;

		$this->setProgramsAndDefaults('sitedev');

		// import our classes
		$needed_classes = array(
			'httpd',
			'user',
			'dns',
			'mail'
		);
		
		foreach($needed_classes as $c)
		{
			$this->core->importClass( $c );
		}
	
		// create all of our functional sub-objects
		$this->func = new stdClass( );
		$this->func->dns = new ServerAdminDNS;
		$this->func->httpd = new ServerAdminHTTPD;
		$this->func->user = new ServerAdminUser;
		$this->func->mail = new ServerAdminMail;
	}
	
	function createSite( $domain, $user, $pass )
	{
		// create the user
		if(!$this->func->user->userExists( $user ))
		{
			$this->debug("going to call user->addUser($user)");
			$this->func->user->addUser( $user, array( 'passwd' => $pass ) );
		}

		// get the user home (needed for httpd)
		$home = $this->func->user->queryUser( $user, 'home' )
			or $this->raiseError( "Unable to find the home of newly created user ($user)");

		// create dns for site
		if(!$this->func->dns->domainExists( $domain ))
		{
			$this->debug("going to call dns->addDomain( $domain )");
			$this->func->dns->addDomain( $domain , array( ) );
			$this->func->dns->build( );
		}

		// create the httpd entry
		if(!$this->func->httpd->virtualHostExists( $domain ))
		{
			$this->debug("going to call httpd->addVirtualHost( $domain )");
			$this->func->httpd->addVirtualHost( $domain,
				array(
					'documentroot'	=> sprintf("%s/htdocs", $home),
					//'serveralias'	=> sprintf("www.%s", $domain),
					'scriptalias'	=> sprintf("/cgi-bin %s/htdocs/cgi-bin", $home, $home)
				)
			);
			$this->func->httpd->addServerAlias( $domain, sprintf("www.%s", $domain) );
			$this->func->httpd->build( );
		}

		// add the mail server settings
		if(!$this->func->mail->domainExists( $domain ))
		{
			$this->debug("going to call mail->addDomain( $domain )");
			$this->func->mail->addDomain( $domain, $pass );
			$this->func->mail->build( );
		}

		return true;
	}

	function deleteSite( $domain, $user = null )
	{
		// delete the user
		if($user 
			and $this->func->user->userExists( $user ))
		{
			$this->debug("going to call user->delUser($user)");
			$this->func->user->delUser( $user );
		}

		// create dns for site
		if($this->func->dns->domainExists( $domain ))
		{
			$this->debug("going to call dns->deleteDomain( $domain )");
			$this->func->dns->deleteDomain( $domain );
			$this->func->dns->build( );
		}

		// create the httpd entry
		if($this->func->httpd->virtualHostExists( $domain ))
		{
			$this->debug("going to call httpd->deleteVirtualHost( $domain )");
			$this->func->httpd->deleteVirtualHost( $domain );
			$this->func->httpd->build( );
		}

		// add the mail server settings
		if($this->func->mail->domainExists( $domain ))
		{
			$this->debug("going to call mail->delDomain( $domain )");
			$this->func->mail->delDomain( $domain );
			$this->func->mail->build( );
		}
	
		return true;
	}

	// automatically give all of our
	// functional classes the same exception handler
	function setFuncExceptionHandlers( $ex_handler )
	{
		$this->setExceptionHandler( $ex_handler );
		foreach($this->func as $f)
		{
			$f->setExceptionHandler( $ex_handler );
		}
	}
}

?>
