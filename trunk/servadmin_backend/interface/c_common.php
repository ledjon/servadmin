<?
	//require_once('../lib/soap/class.nusoap.php');

	// service path
	$service = 'http://localhost:81/admin/interface/%s.php';

class c_soapclient extends SoapClient 
{
	//function c_soapclient( $s )
	function __construct( $s )
	{
		parent::__construct( null, array('location' => $s, 'uri' => 'http://test/'));
		//function c_soapclient( $s ) {
		//$this->soapclient( $s );
//		$this->client = new SoapClient(null, array('location' => $s, 'uri' => 'http://test/'));
	}

	function check_fault( ) {
		if($this->fault)
		{
			die("Error: " . $this->faultactor . "\n");
		}

		return true;
	}
}

?>
