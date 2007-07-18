<?
	// rebuild httpd.conf

	require('core.php');

	$httpd = new ServerAdminHTTPD;

	/* build (well, doBuid() really) logics all the stuff out
	   into the needed stuff */
	//if(! $httpd->build( ))
	if(! $httpd->doBuild( ))
	{
		die("Error rehash apache or it's config: " . $httpd->errmsg);
	}

	// $httpd->deleteVirtualHost( $vhost ) or die("Unable to delete $vhost");
?>
