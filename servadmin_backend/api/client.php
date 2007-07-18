<?
	require_once('../lib/soap/class.nusoap.php');

	$service = 'http://localhost:81/admin/api/soap_test.php';
	$client = new soapclient( $service );

	$ret = $client->call("dns",
			array(
				array(
					'key' => 'asdf',
					'action' => 'addDomain',
					'test' => 'foo'
				)
			)
	);

	if($client->fault) {
		printf("Error: %s\n", $client->faultstring);
	} else {
		printf("Success: %s [%s]\n", $ret, $client->getError());
	}

?>
