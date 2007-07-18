<?

// $Id: email.php,v 1.1 2005/11/03 15:29:31 ledjon Exp $

/*
	Email
*/	

	$this->template->Set('section_title', 'Email');

	$this->template->loadTemplate("start", "templates/start.html");
	$this->template->Set('main_content', $this->template->Parse("start"));

	echo $this->template->Parse("main");

?>
