<?
	// TODO:
	// This is a version 2.0 type of things

	// wizard that lays on top of 
	// existing form items
	
	$this->template->loadTemplate('wizard', 'templates/wizard.html');

	$mod = $this->ajax->data->Get('m');

	$res = 'error (unknown)';
	
	switch($mod)
	{
		case 'email':
			$res = wizard_Email( $this );
			break;
		default:
			$res ="Need valid wizard (m) value ($mod)";
	}
	
	$this->ajax->response['content'] = $res;

function wizard_Email( $self )
{
	//return var_export($self->template);

	return "wizard page yet to come";
}

?>
