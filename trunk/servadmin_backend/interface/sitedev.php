<?
/*
	$Id: sitedev.php,v 1.2 2005/11/12 02:25:41 ledjon Exp $

	The interface for creating/deleting sites

	by Jon Coulter
	11/1/2005
*/
	require_once('../api/interface/soap.php');

	$soap = new ServerAdmin_InterfaceSoap;

	// load in all the needed classes
	$soap->setType('sitedev');

	/* commands that can be called: */
	$commands = array(
		'createSite',
		'deleteSite'
	);

	$soap->registerServices( $commands );
	$sitedev = new ServerAdminSiteDev;
	$sitedev->doDebug = true;

	$sitedev->setExceptionHandler("SoapExceptionHandler");
	$sitedev->setFuncExceptionHandlers("SoapExceptionHandler");

	/* service the request */
	$soap->main( $sitedev );

	/* likely need to do the build */
	//$mail->build( );

	/*
	*
	*  Interface code starts here
	*
	*/
	function createSite( $domain, $username, $password )
	{
		global $sitedev;

		return $sitedev->createSite( $domain, $username, $password  );
	}

	function deleteSite( $domain, $username )
	{
		global $sitedev;

		return $sitedev->deleteSite( $domain, $username );
	}

?>
