<pre>
<?
	require('core.php');

	$domain = 'footest.com';
	$ipaddr = '127.0.0.1'; // default ip for things, like www

	$dns = new ServerAdminDNS;

	/* clean house */
	$domains = $dns->getDomains( );

	if(count($domains) > 3) {
		foreach($domains as $d => $args) {
			echo "removing $d... ";
			$dns->deleteDomain( $d )
				or die("Unable to delete $d");
			echo "done\n";
			flush( );
		}
	}

	/* dump the current */
	//print_r( $dns->getDomains( ) );

for($i = 1; $i < 2; $i++) {
	$domain = 'footest' . $i . '.com';
	if(! $dns->domainExists( $domain ) ) { 
		echo "adding $domain... ";
		$dns->addDomain( $domain, array('ipaddr' => $ipaddr) )
			or die("Unable to add domain");
		echo "done\n";
		flush( );
	}

	// set the primary ip address (not default, bur primary)
	$dns->setPrimaryIP( $domain, '192.168.1.160' );

	/* set mx records (1, in thise case) */
	$dns->setTTL( $domain, (5 * 60) ); // ttl = 5 minutes now
	$dns->setNS( $domain, array('ns1.enhosting.com', 'ns2.enhosting.com') );
	$dns->setMX( $domain, array( '10' => $ipaddr ));

	$dns->setDefaultIP( $domain, $ipaddr );
		// set a default, i.e. '* IN A x.x.x.x'
		// or false to remove default ip usage

	/* add a subdomain */
	$dns->setSubDomain( $domain, 'realsub' );
		// 3rd optional arg: ip address, or flase to remove sub domain

	$dns->setSubDomain( $domain, 'www' ); // default web sub-domain
	
	$dns->setCName( $domain, 'fakesub', 'realsub.' . $domain );
		// last arg can be "a.b.c" format too (note LACK OF '.' on the end)
		// or false to remove cname entry
}

	/* dump again */
//	print_r( $dns->getDomains( ) );

	/* build (well, doBuid() really) logics all the stuff out
	   into the needed stuff */
	$dns->build( ) or die("Unable to rebuild named: " . $dns->errmsg);

	// $dns->deleteDomain( $domain ) or die("Unable to delete domain!");
?>
