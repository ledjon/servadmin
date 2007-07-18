<?

/*
	Sample AJAX PHP Processing script
	by Jon Coulter
*/

require_once('class.ajax.php');

$ajax = new AJAX_Handler( );

// an AJAX_Data() object of incoming variables
$data = $ajax->GetData( );

// do something with $data
$keys = $data->Keys( );
foreach($keys as $k)
{
	$r = strtoupper($data->Get($k));
	$t = '';
	
	for($i = 0; $i < strlen($r); $i++)
	{
		$t .= $r[$i] . ' ';
	}
	$t = trim($t);
	
	$ret[$k] = $r;
}

$ajax->SendResponse( new AJAX_Data( $ret ) );

?>