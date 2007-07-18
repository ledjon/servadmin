<?
	/*
		Run testing with soapserver.php
		by Jon Coulter
	*/

	$service = 'http://localhost:81/admin/soapserver.php';

	require_once('lib/soap/class.nusoap.php');

	//$client = new soap_client( $service );
	$client = new SoapClient(null, array('location' => $service, 'uri' => 'http://test/'));

	$start = time();
	
	printf("Soap server running as: [%s]<br>\n",
			//$client->call('whoami')
			$client->whoami()
	);
	
for($i = 0; $i < 50; $i++) {
	$string = "My String";
	echo "the reverse of [$string] is [" .
	//$client->call("reverse", array( array('a' => $string) )) . "]";
	//$client->reverse('reverse', array('a' => $string)) . "]";
	$client->reverse($string) . "]";
	echo "<br>\n";
	flush();
}

	printf("<hr>Time to do actions: " . (time() - $start));
?>
