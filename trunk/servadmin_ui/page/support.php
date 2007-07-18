<?

// $Id: support.php,v 1.1 2005/11/13 19:49:54 ledjon Exp $

/*
	Email
*/	

	$this->template->Set('section_title', 'Support');

	$this->template->loadTemplate("start", "templates/start.html");
	$this->template->Set('main_content', $this->template->Parse("start"));

	echo $this->template->Parse("main");

?>
