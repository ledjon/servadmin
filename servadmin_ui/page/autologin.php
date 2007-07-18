<?

// $Id: autologin.php,v 1.1 2005/11/28 20:43:27 ledjon Exp $

/*
	AutoLogin page
*/

$id = intval( $this->param->id );

if( ! $id )
{
	die("Invalid login id: $id");
}

if(! $this->isAdmin( ) )
{
	die("Only administrators can perform this action.");
}

// set myself up
//$this->session['uid'] = $id;
$this->setSession('uid', $id);
$this->setSession('is_admin', 0);

$this->redirect( $this->link(null, true) );

?>
