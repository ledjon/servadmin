<?
	require('core.php');
	
	$httpd = new ServerAdminHTTPD;
	
	$httpd->addVirtualHost( '_default_',
		array('documentroot' => '/usr/local/www/data')
	) or die("Unable to add default vhost");

	$httpd->build( );

?>
