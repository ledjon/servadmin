<?

// $Id: admin_sites.php,v 1.2 2005/11/07 20:51:38 ledjon Exp $

/*
	Database
*/	

	$this->template->Set('section_title', 'Sites');

	$this->template->loadTemplate("start", "templates/start.html");
	$this->template->Set('main_content', $this->template->Parse("start"));

	echo $this->template->Parse("main");

?>
