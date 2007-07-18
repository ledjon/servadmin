<?
	require_once("core.php");

	$site = new ServerAdminSiteDev;

	$site->deleteSite( 'happy.com', 'happycom' );
	$site->createSite( 'happy.com', 'happycom', 'password' );

?>
