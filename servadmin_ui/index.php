<?

/*
	$Id: index.php,v 1.3 2005/11/13 03:49:55 ledjon Exp $

	ServAdmin User Interface

	by Jon Coulter
	10/28/2005
*/

// start the session no matter what
session_start( );

define('SA_MAIN', 1);
define('SA_DIR', dirname(__FILE__));
require_once(SA_DIR . '/ui.php');

$ui = new ServAdminUI( );

$ui->template->loadTemplate("main", "templates/main.html");
$ui->template->Set('self', $ui->link( null, true ));
$ui->template->Set('interface_page', $ui->link( array('_type' => 'ajax' ), true ));
$ui->template->Set('section_title', 'by Ledscripts.com');
$ui->template->Set('error', $ui->getSessionOnce('error'));

if($ui->param->_type == 'ajax')
{
	$ui->handleAJAXRequest( );
}
else
{
	$ui->handlePageRequest( );
}

?>
