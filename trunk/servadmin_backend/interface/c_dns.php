<pre>
<?
	/*
		Run testing with soapserver.php
		by Jon Coulter
	*/

	require_once('c_common.php');
	$client = new c_soapclient( sprintf($service, 'dns') );

	$domain = 'footest.com';
	$ipaddr = '127.0.0.1';

	$domains = $client->getDomains( );

	var_dump( $domains );

	if(count($domains) > 3) {
		foreach($domains as $d) {
			echo "removing $d... ";
			echo $client->deleteDomain( $d );
			echo " ";
			$client->check_fault( );
			echo "done\n";
			flush( );
		}
	}


	/* domain exists? */
	$ret = (bool) $client->domainExists( $domain );

	if($ret == true)
	{
		echo "going to delete $domain... ";
		$client->deleteDomain( $domain );
		echo "done\n";
	}

	echo "Going to add $domain... ";
	// add domain
	try
	{
		$ret = $client->addDomain(
			$domain,
			serialize(
				array(
					'ipaddr' => $ipaddr
				)
			)
		);
	}
	catch(SoapFault $ex)
	{
		die($ex->faultstring);
	}
//	$client->check_fault( );

	echo "done ($ret)\n";

	echo "setting primary ip... ";
	$ret = (bool) $client->setPrimaryIP($domain, '192.168.1.160');
	//print_r($client);
	$client->check_fault( );
	echo "done ($ret)\n";

	echo "setting ttl... ";
	$ret = (bool) $client->setTTL($domain, (5 * 60));
	$client->check_fault( );
	echo  "done ($ret)\n";

	echo "setting name server(s)... ";
	$ret = (bool) $client->setNS($domain, 'ns1.enhosting.com', 'ns2.enhosting.com');
	$client->check_fault( );
	echo "done ($ret)\n";

	echo "seting mx record(s)... ";
	$ret = (bool) $client->setMX(
				$domain,
				serialize(
					array(
						'10', $ipaddr,
						'20', '4.5.6.7'
					)
				)
		);
	$client->check_fault( );
	echo "done ($ret)\n";

	echo "setting default ip... ";
	$ret = (bool) $client->setDefaultIP($domain, $ipaddr);
	$client->check_fault( );
	echo "done ($ret)\n";
?>
