<?

// $Id: login.php,v 1.1 2005/11/03 15:29:31 ledjon Exp $

/*
	Login page
*/

$this->template->Set('v_username', '');

if($this->html->posting( ))
{
	$u = $this->param->username;
	$p = $this->param->password;

	if(!$this->loginUser($u, $p))
	{
		$this->setError('Invalid login details');
		$this->setSession('v_username', $this->param->username);
		$this->redirect( $this->link( ) );
	}
	else
	{
		$this->redirect($this->link(null, true));
	}
}
else
{
	$this->template->Set('v_username', $this->getSessionOnce('v_username'));
	$this->template->Set('section_title', 'Login');
	$this->template->Set('top_menu', '');
	//$this->template->Set('left_menu', 'Please login to the right.');
	$this->template->Set('left_menu', '');

	$this->template->Set('form_action', $this->link(array('a' => 'login'), true));

	$this->template->loadTemplate("login", "templates/login.html");
	$this->template->Set('main_content', $this->template->Parse("login"));

	echo $this->template->Parse("main");
}

?>
