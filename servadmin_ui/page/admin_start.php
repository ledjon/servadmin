<?

// $Id: admin_start.php,v 1.1 2005/11/03 15:29:31 ledjon Exp $

/*
	Admin Start page
*/	

	_check_is_admin( ) or die("invalid admin");

	$this->template->Set('section_title', 'Start Page');

	$this->template->loadTemplate("start", "templates/start.html");
	$this->template->Set('main_content', $this->template->Parse("start"));

	echo $this->template->Parse("main");

?>
