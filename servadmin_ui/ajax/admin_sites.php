<?
	//var_dump($this->ajax->data);
	$mod = $this->ajax->data->Get('m');

	$res = 'error (uknown)';

	switch($mod)
	{
		case 'welcome':
			$res = welcome( $this );
			break;
		case 'newsite':
			$res = newsite( $this );
			break;
		case 'sendwelcome':
			$res = sendwelcome( $this );
			break;
		case 'modsites':
			$res = modsites( $this );
			break;
		case 'delsite':
			$res = delsite( $this );
			break;
		case 'modsite':
			$res = modsite( $this );
			break;
		case 'genusername':
			$res = genusername( $this );
			break;
		case 'verifyusername':
			$res = verifyusername( $this );
			break;
		default:
			$res ="Need valid mod (m) value ($mod)";
	}
	
	$this->ajax->response['content'] = $res;

function welcome( $self )
{
	return $self->getHelpNote('admin_sites/welcome');
}

function newsite( $self )
{
	$exec = $self->ajax->data->Get('exec');

	if($exec)
	{
		// new site
		$servid = intval($self->ajax->data->Get('servid'));

		$domain = trim($self->ajax->data->Get('domain'));
		$domain = strtolower($domain);
		$domain = preg_replace('/^www\./i', '', $domain);

		$username = trim($self->ajax->data->Get('username'));
		$username = strtolower($username);

		$password = trim($self->ajax->data->Get('password'));

		$email = $self->ajax->data->Get('email');

		if(! $password )
		{
			die("Invalid password provided ($password)");
		}

		$interface = $self->getInterface('sitedev', $servid);

		if($self->domainExists( $domain ))
		{
			die("That domain appears to already existing in the system ($domain)");
		}

		if($self->usernameExists( $username ))
		{
			die("That username is already taken ($username)");
		}

		// now simply call it
		$interface->call('createSite', array( $domain, $username, $password ) );
		$interface->checkFault( );

		// insert the new values into the database
		$uid = $self->addAccount(
			array(
				'username'	=> $username,
				'password'	=> $password,
				'domain'	=> $domain,
				'servid'	=> $servid,
				'ownername'	=> $self->ajax->data->Get('ownername'),
				'email'		=> $email
			)
		);

		if(! $uid )
		{
			die("Unable to create acount (Unknown error)");
		}

		// display the notice message
		list($subject, $body) = $self->getWelcomeMessage( $uid,
				array(
					'password' => $password,
					'cp_url' => 'http://' . $_SERVER['HTTP_HOST'] . dirname($self->link( null, true ))
				)
			);

		$ret = $self->tableHeader("Site Created!");

		$ret .= $self->html->form_start(
				array(
					'id' => 'frmMain',
					'name'	=> 'frmMain',
					'action' => 'sendwelcome'
				)
			) .
			$self->tableHeader("Send Welcome Letter (Optional)") .
			$self->html->table(
				$self->html->tr(
					$self->html->td(
						'To:',
						array('width' => 100)
					) .
					$self->html->td(
						$self->html->textfield('to', 
							array(
								'value'	=> $email,
								'class'	=> 'input',
								'size'	=> 25
							)
						)
					) 
				) .
				$self->html->tr(
					$self->html->td(
						'Subject:',
						array('width' => 100)
					) .
					$self->html->td(
						$self->html->textfield('subject', 
							array(
								'value'	=> $subject,
								'class'	=> 'input',
								'size'	=> 50
							)
						)
					) 
				) .
				$self->html->tr(
					$self->html->td(
						'Body:<br>' .
						$self->html->textarea('body', 
							array(
								'value'	=> $body,
								'class'	=> 'input',
								'cols'	=> 70,
								'rows'	=> 20,
								'style' => 'width: 100%'
							)
						),
						array(
							'colspan' => 2
						)
					)
				),
				array('class' => 'maintable')
			) .
			'<hr size=1>' .
			$self->tableHeader(
				$self->ajaxSubmitButton('Send Email', 'frmMain')
			) 
			.
			$self->html->form_end( );

		return $ret;
	}
	else
	{
		$servers = $self->getServerList( );

		// do our own array_merge (key-safe)
		$res_servers = array( '--Select A Server--' );
		foreach($servers as $k => $v)
		{
			$res_servers[$k] = $v;
		}

		// show html table
		$ret = 
			$self->html->form_start(
				array(
					'id'	=> 'frmMain',
					'name'	=> 'frmMain',
					'action'	=> 'newsite',
					'onsubmit'	=> 'return findRealSubmit(this);'
				)
			) .
			$self->tableHeader("Create New Site") .
			$self->html->table(
				$self->html->tr(
					$self->html->td(
						'Owner Name',
						array('width' => '50%')
					) .
					$self->html->td(
						$self->html->textfield('ownername',
							array(
								'class'	=> 'input',
								'size'	=> 30
							)
						),
						array('width' => '50%')
					)
				) .
				$self->html->tr(
					$self->html->td(
						'Contact Email'
					) .
					$self->html->td(
						$self->html->textfield('email',
							array(
								'class'	=> 'input',
								'size'	=> 25 
							)
						)
					)
				) .
				$self->html->tr(
					$self->html->td(
						'Domain Name (no "www" prefix)'
					) .
					$self->html->td(
						$self->html->textfield('domain',
							array(
								'class'	=> 'input',
								'size'	=> 30,
								'onblur'	=> 'fixDomainName(this); generateUsername(this);'
							)
						)
					)
				) .
				$self->html->tr(
					$self->html->td(
						'Username (keep it short)'
					) .
					$self->html->td(
						$self->html->textfield('username',
							array(
								'class'	=> 'input',
								'size'	=> 25 ,
								'onchange'	=> 'verifyNewUsername(this)'
							)
						) . ' <font color="red"><span id="verifyUsernameSpan"></span></font>'
					)
				) .
				$self->html->tr(
					$self->html->td(
						'Password'
					) .
					$self->html->td(
						$self->html->textfield('password',
							array(
								'class'	=> 'input',
								'size'	=> 20
							)
						) . ' [ <a href="javascript:void(0)" onclick="generatePassword(\'password\')">Generate</a> ]'
					)
				) .
				$self->html->tr(
					$self->html->td(
						'Server'
					) .
					$self->html->td(
						$self->html->popup_menu('servid',
							$res_servers,
							'',
							array(
								'class' => 'input'
							)
						)
					)
				) 
				,
				array('class' => 'maintable')
			) .
			'<hr size=1>' .
			$self->tableHeader(
				$self->ajaxSubmitButton('Create New Site', 'frmMain', 'validateNewSite()')
			) .
			$self->html->form_end( );

		// execute this after the page is showing
		$self->ajax->response['onLoad'] = '_findObj(\'ownername\').focus()';

		return $ret;
	}
}

function sendwelcome( $self )
{
	$exec = $self->ajax->data->Get('exec');

	if($exec)
	{
		$to = $self->ajax->data->Get('to');
		$subject = $self->ajax->data->Get('subject');
		$body = $self->ajax->data->Get('body');

		// send the email
		// TODO -- make these values configurable!
		$body = str_replace("\r", "", $body);
		mail( $to, $subject, $body,
			"From: LedHosting.com Support <support@ledhosting.com>"
		);

		return $self->tableHeader("Email sent: site creation complete.");
	}
	else
	{
		return "why are you here?";
	}
}

// this function returns a unique username based
// on the input domain name (initval)
function genusername( $self )
{
	$init = $self->ajax->data->Get('initval');	

	if(! $init )
	{
		die("Unable to get initial value ($init)");
	}

	$user = $self->genUsername( $init );

	return $user;
}

function verifyusername( $self )
{
	$user = trim($self->ajax->data->Get('username'));

	if(!$user)
	{
		die("Invalid username");
	}

	return ($self->usernameExists( $user ) ? "bad" : "good");
}

function modsites( $self )
{
	// list all fo the site

	$ret = $self->tableHeader("Existing Sites");

	$sql = "select a.accountid, username, domain, ownername, s.servname
			from account a
				join account_server ac on (a.accountid = ac.accountid)
				join server s on (s.serverid = ac.serverid)
			order by domain";

	$res = $self->db->Execute( $sql )
		or $self->raiseError( $self->db->ErrorMsg( ) );

	$tbl = '';
	while($row = $res->FetchNextObj( ))
	{
		if($self->isUserAdmin( $row->username ))
		{
			continue;
		}

		$tbl .=
			$self->html->tr(
				$self->html->td(
					' [&nbsp;' .
					$self->html->ahref(
						'javascript:void(0)',
						'D',
						array(
							'onclick' =>
								"if(confirm('Are you *sure* you want to delete this site!?'))
									{ loadRight(action, 'delsite', " . $row->accountid . ") }"
						)
					) . '|' .

					$self->html->ahref(
						$self->link( array('a' => 'autologin', 'id' => $row->accountid ), true ),
						'L',
						array(
							'onclick'	=>
								"if(!confirm('WARNING: This will log you out.  Are you sure you want to continue?'))
									{ return false; }"
						)
					) . '|' .
					$self->html->ahref(
						'javascript:void(0)',
						'M',
						array(
							'onclick' => "loadRight(action, 'modsite', " . $row->accountid . ")"
						)
					) . ' ]',
					array('width' => 2)
				) .
				/*
				$self->html->td(
					' [&nbsp;' .
					$self->html->ahref(
						$self->link( array('a' => 'autologin', 'id' => $row->accountid ), true ),
						'L',
						array(
							'onclick'	=>
								"if(!confirm('WARNING: This will log you out.  Are you sure you want to continue?'))
									{ return false; }"
						)
					) . '&nbsp;] ',
					array('width' => 2)
				) .
				$self->html->td(
					' [&nbsp;' .
					$self->html->ahref(
						'javascript:void(0)',
						'M',
						array(
							'onclick' => "loadRight(action, 'modsite', " . $row->accountid . ")"
						)
					) . '&nbsp;] ',
					array('width' => 2)
				) .
				*/
				$self->html->td(
					$row->servname
				) .
				$self->html->td(
					$row->domain
				) .
				$self->html->td(
					$row->username
				)
			);
	}

	if($tbl)
	{
		$tbl =
			$self->html->tr(
				$self->html->td(
					'[ D|L|M ]',
					array('class' => 'topcells')
				) .
				/*
				$self->html->td(
					'Login',
					array('class' => 'topcells')
				) .
				$self->html->td(
					'Mod',
					array('class' => 'topcells')
				) .
				*/
				$self->html->td(
					'Server',
					array('class' => 'topcells')
				) .
				$self->html->td(
					'Domain',
					array('class' => 'topcells')
				) .
				$self->html->td(
					'Username',
					array('class' => 'topcellsRight')
				)
			) .
			$tbl;
		
		$ret .=
			$self->html->table(
				$tbl, 
				array('class' => 'maintable')
			);
	}

	return $ret;
}

function delsite( $self )
{
	$uid = intval($self->ajax->data->Get('extra'));

	if(! $uid )
	{
		die("Unable to find valid uid ($uid)");
	}

	$u = $self->userDetails( $uid );

	if($self->isUserAdmin( $u->username ))
	{
		die("attempting to delete an administrator (something seriously wrong!)");
	}
	
	// remove from the server first
	$interface = $self->getInterface( 'sitedev', $u->serverid );

	$interface->call('deleteSite', array( $u->domain, $u->username ));
	$interface->checkFault( );

	// remove from user database
	$self->delAccount( $uid );

	$self->ajax->response['reloadContent'] = 'modsites';
	
	return "Site Deleted...";
}

function modsite( $self )
{
	$exec = $self->ajax->data->Get('exec');
	$uid = intval($self->ajax->data->Get('extra'));

	if(! $uid )
	{
		$uid = intval($self->ajax->data->Get('mod_uid'));

		if(! $uid )
		{
			die("Invalid uid ($uid)");
		}
	}

	$site = $self->userDetails( $uid );

	if($exec)
	{
		// make requested changes

		// new password?
		if($pass = trim($self->ajax->data->Get('new_password')))
		{
			$i = $self->getInterface( 'user', $site->serverid );
			/*
			if(! $i->call('passwd', array( $site->username, $pass )) )
			{
				$self->raiseError("Problem with 'passwd': " . $i->getError());
			}
			*/
			$i->call('passwd', array( $site->username, $pass ));
			$i->checkFault( );

			$self->setUserDetail(
				'password',
				md5($pass),
				$uid
			);
		}

		// update these values no matter what
		$self->setUserDetail(
			'ownername',
			$self->ajax->data->Get('ownername'),
			$uid
		);

		$self->setUserDetail(
			'email',
			$self->ajax->data->Get('email'),
			$uid
		);
		
		$self->ajax->response['reloadContent'] = 'modsites';

		return "Changes saved...";
	}
	else
	{

		$ret = $self->html->form_start(
			array(
				'id'	=> 'frmMain',
				'name'	=> 'frmMain',
				'action'	=> 'modsite',
				'onsubmit'	=> 'return findRealSubmit(this);'
			)
		) .
		$self->html->hidden('mod_uid', array('value' => $uid));

		$ret .= $self->tableHeader("Modify Site: [$site->domain]");

		$ret .=
			$self->html->table(
				$self->html->tr(
					$self->html->td(
						'Owner Name:'
					) .
					$self->html->td(
						$self->html->textfield('ownername',
							array(
								'class'	=> 'input',
								'size'	=> 30,
								'value'	=> $site->ownername
							)
						)
					) 
				) .
				$self->html->tr(
					$self->html->td(
						'Contact Email:'
					) .
					$self->html->td(
						$self->html->textfield('email',
							array(
								'class'	=> 'input',
								'size'	=> 30,
								'value'	=> $site->email
							)
						)
					) 
				) .
				$self->html->tr(
					$self->html->td(
						'Domain:'
					) .
					$self->html->td(
						$site->domain
					) 
				) .
				$self->html->tr(
					$self->html->td(
						'Username:'
					) .
					$self->html->td(
						$site->username
					) 
				) .
				$self->html->tr(
					$self->html->td(
						'New Password:'
					) .
					$self->html->td(
						$self->html->textfield('new_password',
							array(
								'class'	=> 'input',
								'size'	=> 20
							)
						) 
					)
				) .
				$self->html->tr(
					$self->html->td(
						'Server:'
					) .
					$self->html->td(
						$site->servname
					) 
				) ,
				array('class' => 'maintable')
			);

		$ret .= '<hr size=1>' .
				$self->tableHeader(
					$self->ajaxSubmitButton('Save Changes', 'frmMain')
				) .
				$self->html->form_end( );

		return $ret;
	}
}

?>
