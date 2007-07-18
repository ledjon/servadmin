<?

// $Id: stats.php,v 1.1 2005/11/14 01:49:20 ledjon Exp $

	$this->template->Set('section_title', 'Statistics and Logs');

	$this->template->loadTemplate("start", "templates/start.html");
	$this->template->Set('main_content', $this->template->Parse("start"));

	echo $this->template->Parse("main");

?>
