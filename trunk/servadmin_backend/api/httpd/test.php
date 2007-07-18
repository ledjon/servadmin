<pre>
<?
	// test apache things 

	require('core.php');

	$vhost = 'www.foobar.com';
	$docroot = getcwd( );

	$httpd = new ServerAdminHTTPD;

	/* clean house */
	$vhosts = $httpd->getVirtualHosts( );

	if(count($vhosts) > 3) {
		foreach($vhosts as $v => $args) {
			echo "Removing $v\n";
			$httpd->deleteVirtualHost( $v )
				or die("Unable to delete $v");
		}

		$httpd->addVirtualHost( '_default_',
			array('documentroot' => '/usr/local/www/data')
		) or die("Unable to add default vhost");
	}

for($i = 1; $i < 100; $i++) {
	$vhost = 'footest' . $i . '.com';
	echo "going to add for $vhost\n";
	if(! $httpd->virtualHostExists( $vhost ) ) { 
		$httpd->addVirtualHost( $vhost, array('documentroot' => $docroot))
			or die("Unable to add vhost");
	} else {
		continue;
	}	

	/* update (extra) atributes via this method */
	/* note the order we add them *is* important */
	$httpd->setAttribute( $vhost, 'RewriteEngine', 'On');
	$httpd->setAttribute( $vhost, 'RewriteCond',
					array(
						'%{REQUEST_URI} !^/images',
						'%{REQUEST_URI} !^/index.php',
						'%{REQUEST_URI} !^/$'
					)
	);
	$httpd->setAttribute( $vhost, 'RewriteRule', '.* /index.php');
		// 2nd argument to 2nd argument (value of the array)
		// can be 'false' to remove that line all together

	/* just blind extra lines */
	/* note that *this* example could be done with
	   setAttribute, but without the comment line */
	$httpd->setExtraLines( $vhost,
			array(
				'# myline 1',
				'CustomLog /tmp/logs/' . $vhost . '-access combined'
			)
	);
		// 2nd arg can be 'false' to remove all lines
	
	/* update commone ones via methods that exist for them */
	$httpd->setServerAdmin( $vhost, 'you@yourdomain.com' );
	$httpd->setDocumentRoot( $vhost, $docroot );
	$httpd->setScriptAlias($vhost, '/cgi-bin ' . $docroot . '/cgi-bin');

	for($x = 0; $x < 5; $x++) {
		$alias = 'sub' . $x . '.' . $vhost;
		if(! $httpd->serverAliasExists( $vhost, $alias ) ) {
			$httpd->addServerAlias( $vhost, $alias );

			// remove above (almost)
		//	$httpd->deleteServerAlias( $vhost, $alias );
		}
	}
}

	/* all server alias's for vhost */
	//print_r( $httpd->mdb );

	/* build (well, doBuid() really) logics all the stuff out
	   into the needed stuff */
	if(! $httpd->build( )) {
		die("Error rehash apache or it's config: " . $httpd->errmsg);
	}

	// $httpd->deleteVirtualHost( $vhost ) or die("Unable to delete $vhost");
?>
