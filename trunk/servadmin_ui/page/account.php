<?

// $Id: account.php,v 1.1 2005/11/03 15:29:31 ledjon Exp $

/*
	Accounts
*/	

	$this->template->Set('section_title', 'Account');

	$this->template->loadTemplate("start", "templates/start.html");
	$this->template->Set('main_content', $this->template->Parse("start"));

	echo $this->template->Parse("main");

?>
