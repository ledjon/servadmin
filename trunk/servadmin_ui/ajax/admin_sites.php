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

function welcome( $this )
{
	return $this->getHelpNote('admin_sites/welcome');
}

function newsite( $this )
{
	$exec = $this->ajax->data->Get('exec');

	if($exec)
	{
		// new site
		$servid = intval($this->ajax->data->Get('servid'));

		$domain = trim($this->ajax->data->Get('domain'));
		$domain = strtolower($domain);
		$domain = preg_replace('/^www\./i', '', $domain);

		$username = trim($this->ajax->data->Get('username'));
		$username = strtolower($username);

		$password = trim($this->ajax->data->Get('password'));

		$email = $this->ajax->data->Get('email');

		if(! $password )
		{
			die("Invalid password provided ($password)");
		}

		$interface = $this->getInterface('sitedev', $servid);

		if($this->domainExists( $domain ))
		{
			die("That domain appears to already existing in the system ($domain)");
		}

		if($this->usernameExists( $username ))
		{
			die("That username is already taken ($username)");
		}

		// now simply call it
		$interface->call('createSite', array( $domain, $username, $password ) );
		$interface->checkFault( );

		// insert the new values into the database
		$uid = $this->addAccount(
			array(
				'username'	=> $username,
				'password'	=> $password,
				'domain'	=> $domain,
				'servid'	=> $servid,
				'ownername'	=> $this->ajax->data->Get('ownername'),
				'email'		=> $email
			)
		);

		if(! $uid )
		{
			die("Unable to create acount (Unknown error)");
		}

		// display the notice message
		list($subject, $body) = $this->getWelcomeMessage( $uid,
				array(
					'password' => $password,
					'cp_url' => 'http://' . $_SERVER['HTTP_HOST'] . dirname($this->link( null, true ))
				)
			);

		$ret = $this->tableHeader("Site Created!");

		$ret .= $this->html->form_start(
				array(
					'id' => 'frmMain',
					'name'	=> 'frmMain',
					'action' => 'sendwelcome'
				)
			) .
			$this->tableHeader("Send Welcome Letter (Optional)") .
			$this->html->table(
				$this->html->tr(
					$this->html->td(
						'To:',
						array('width' => 100)
					) .
					$this->html->td(
						$this->html->textfield('to', 
							array(
								'value'	=> $email,
								'class'	=> 'input',
								'size'	=> 25
							)
						)
					) 
				) .
				$this->html->tr(
					$this->html->td(
						'Subject:',
						array('width' => 100)
					) .
					$this->html->td(
						$this->html->textfield('subject', 
							array(
								'value'	=> $subject,
								'class'	=> 'input',
								'size'	=> 50
							)
						)
					) 
				) .
				$this->html->tr(
					$this->html->td(
						'Body:<br>' .
						$this->html->textarea('body', 
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
			$this->tableHeader(
				$this->ajaxSubmitButton('Send Email', 'frmMain')
			) 
			.
			$this->html->form_end( );

		return $ret;
	}
	else
	{
		$servers = $this->getServerList( );

		// do our own array_merge (key-safe)
		$res_servers = array( '--Select A Server--' );
		foreach($servers as $k => $v)
		{
			$res_servers[$k] = $v;
		}

		// show html table
		$ret = 
			$this->html->form_start(
				array(
					'id'	=> 'frmMain',
					'name'	=> 'frmMain',
					'action'	=> 'newsite',
					'onsubmit'	=> 'return findRealSubmit(this);'
				)
			) .
			$this->tableHeader("Create New Site") .
			$this->html->table(
				$this->html->tr(
					$this->html->td(
						'Owner Name',
						array('width' => '50%')
					) .
					$this->html->td(
						$this->html->textfield('ownername',
							array(
								'class'	=> 'input',
								'size'	=> 30
							)
						),
						array('width' => '50%')
					)
				) .
				$this->html->tr(
					$this->html->td(
						'Contact Email'
					) .
					$this->html->td(
						$this->html->textfield('email',
							array(
								'class'	=> 'input',
								'size'	=> 25 
							)
						)
					)
				) .
				$this->html->tr(
					$this->html->td(
						'Domain Name (no "www" prefix)'
					) .
					$this->html->td(
						$this->html->textfield('domain',
							array(
								'class'	=> 'input',
								'size'	=> 30,
								'onblur'	=> 'fixDomainName(this); generateUsername(this);'
							)
						)
					)
				) .
				$this->html->tr(
					$this->html->td(
						'Username (keep it short)'
					) .
					$this->html->td(
						$this->html->textfield('username',
							array(
								'class'	=> 'input',
								'size'	=> 25 ,
								'onchange'	=> 'verifyNewUsername(this)'
							)
						) . ' <font color="red"><span id="verifyUsernameSpan"></span></font>'
					)
				) .
				$this->html->tr(
					$this->html->td(
						'Password'
					) .
					$this->html->td(
						$this->html->textfield('password',
							array(
								'class'	=> 'input',
								'size'	=> 20
							)
						) . ' [ <a href="javascript:void(0)" onclick="generatePassword(\'password\')">Generate</a> ]'
					)
				) .
				$this->html->tr(
					$this->html->td(
						'Server'
					) .
					$this->html->td(
						$this->html->popup_menu('servid',
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
			$this->tableHeader(
				$this->ajaxSubmitButton('Create New Site', 'frmMain', 'validateNewSite()')
			) .
			$this->html->form_end( );

		// execute this after the page is showing
		$this->ajax->response['onLoad'] = '_findObj(\'ownername\').focus()';

		return $ret;
	}
}

function sendwelcome( $this )
{
	$exec = $this->ajax->data->Get('exec');

	if($exec)
	{
		$to = $this->ajax->data->Get('to');
		$subject = $this->ajax->data->Get('subject');
		$body = $this->ajax->data->Get('body');

		// send the email
		// TODO -- make these values configurable!
		$body = str_replace("\r", "", $body);
		mail( $to, $subject, $body,
			"From: LedHosting.com Support <support@ledhosting.com>"
		);

		return $this->tableHeader("Email sent: site creation complete.");
	}
	else
	{
		return "why are you here?";
	}
}

// this function returns a unique username based
// on the input domain name (initval)
function genusername( $this )
{
	$init = $this->ajax->data->Get('initval');	

	if(! $init )
	{
		die("Unable to get initial value ($init)");
	}

	$user = $this->genUsername( $init );

	return $user;
}

function verifyusername( $this )
{
	$user = trim($this->ajax->data->Get('username'));

	if(!$user)
	{
		die("Invalid username");
	}

	return ($this->usernameExists( $user ) ? "bad" : "good");
}

function modsites( $this )
{
	// list all fo the site

	$ret = $this->tableHeader("Existing Sites");

	$sql = "select a.accountid, username, domain, ownername, s.servname
			from account a
				join account_server ac on (a.accountid = ac.accountid)
				join server s on (s.serverid = ac.serverid)
			order by domain";

	$res = $this->db->Execute( $sql )
		or $this->raiseError( $this->db->ErrorMsg( ) );

	$tbl = '';
	while($row = $res->FetchNextObj( ))
	{
		if($this->isUserAdmin( $row->username ))
		{
			continue;
		}

		$tbl .=
			$this->html->tr(
				$this->html->td(
					' [&nbsp;' .
					$this->html->ahref(
						'javascript:void(0)',
						'D',
						array(
							'onclick' =>
								"if(confirm('Are you *sure* you want to delete this site!?'))
									{ loadRight(action, 'delsite', " . $row->accountid . ") }"
						)
					) . '|' .

					$this->html->ahref(
						$this->link( array('a' => 'autologin', 'id' => $row->accountid ), true ),
						'L',
						array(
							'onclick'	=>
								"if(!confirm('WARNING: This will log you out.  Are you sure you want to continue?'))
									{ return false; }"
						)
					) . '|' .
					$this->html->ahref(
						'javascript:void(0)',
						'M',
						array(
							'onclick' => "loadRight(action, 'modsite', " . $row->accountid . ")"
						)
					) . ' ]',
					array('width' => 2)
				) .
				/*
				$this->html->td(
					' [&nbsp;' .
					$this->html->ahref(
						$this->link( array('a' => 'autologin', 'id' => $row->accountid ), true ),
						'L',
						array(
							'onclick'	=>
								"if(!confirm('WARNING: This will log you out.  Are you sure you want to continue?'))
									{ return false; }"
						)
					) . '&nbsp;] ',
					array('width' => 2)
				) .
				$this->html->td(
					' [&nbsp;' .
					$this->html->ahref(
						'javascript:void(0)',
						'M',
						array(
							'onclick' => "loadRight(action, 'modsite', " . $row->accountid . ")"
						)
					) . '&nbsp;] ',
					array('width' => 2)
				) .
				*/
				$this->html->td(
					$row->servname
				) .
				$this->html->td(
					$row->domain
				) .
				$this->html->td(
					$row->username
				)
			);
	}

	if($tbl)
	{
		$tbl =
			$this->html->tr(
				$this->html->td(
					'[ D|L|M ]',
					array('class' => 'topcells')
				) .
				/*
				$this->html->td(
					'Login',
					array('class' => 'topcells')
				) .
				$this->html->td(
					'Mod',
					array('class' => 'topcells')
				) .
				*/
				$this->html->td(
					'Server',
					array('class' => 'topcells')
				) .
				$this->html->td(
					'Domain',
					array('class' => 'topcells')
				) .
				$this->html->td(
					'Username',
					array('class' => 'topcellsRight')
				)
			) .
			$tbl;
		
		$ret .=
			$this->html->table(
				$tbl, 
				array('class' => 'maintable')
			);
	}

	return $ret;
}

function delsite( $this )
{
	$uid = intval($this->ajax->data->Get('extra'));

	if(! $uid )
	{
		die("Unable to find valid uid ($uid)");
	}

	$u = $this->userDetails( $uid );

	if($this->isUserAdmin( $u->username ))
	{
		die("attempting to delete an administrator (something seriously wrong!)");
	}
	
	// remove from the server first
	$interface = $this->getInterface( 'sitedev', $u->serverid );

	$interface->call('deleteSite', array( $u->domain, $u->username ));
	$interface->checkFault( );

	// remove from user database
	$this->delAccount( $uid );

	$this->ajax->response['reloadContent'] = 'modsites';
	
	return "Site Deleted...";
}

function modsite( $this )
{
	$exec = $this->ajax->data->Get('exec');
	$uid = intval($this->ajax->data->Get('extra'));

	if(! $uid )
	{
		$uid = intval($this->ajax->data->Get('mod_uid'));

		if(! $uid )
		{
			die("Invalid uid ($uid)");
		}
	}

	$site = $this->userDetails( $uid );

	if($exec)
	{
		// make requested changes

		// new password?
		if($pass = trim($this->ajax->data->Get('new_password')))
		{
			$i = $this->getInterface( 'user', $site->serverid );
			/*
			if(! $i->call('passwd', array( $site->username, $pass )) )
			{
				$this->raiseError("Problem with 'passwd': " . $i->getError());
			}
			*/
			$i->call('passwd', array( $site->username, $pass ));
			$i->checkFault( );

			$this->setUserDetail(
				'password',
				md5($pass),
				$uid
			);
		}

		// update these values no matter what
		$this->setUserDetail(
			'ownername',
			$this->ajax->data->Get('ownername'),
			$uid
		);

		$this->setUserDetail(
			'email',
			$this->ajax->data->Get('email'),
			$uid
		);
		
		$this->ajax->response['reloadContent'] = 'modsites';

		return "Changes saved...";
	}
	else
	{

		$ret = $this->html->form_start(
			array(
				'id'	=> 'frmMain',
				'name'	=> 'frmMain',
				'action'	=> 'modsite',
				'onsubmit'	=> 'return findRealSubmit(this);'
			)
		) .
		$this->html->hidden('mod_uid', array('value' => $uid));

		$ret .= $this->tableHeader("Modify Site: [$site->domain]");

		$ret .=
			$this->html->table(
				$this->html->tr(
					$this->html->td(
						'Owner Name:'
					) .
					$this->html->td(
						$this->html->textfield('ownername',
							array(
								'class'	=> 'input',
								'size'	=> 30,
								'value'	=> $site->ownername
							)
						)
					) 
				) .
				$this->html->tr(
					$this->html->td(
						'Contact Email:'
					) .
					$this->html->td(
						$this->html->textfield('email',
							array(
								'class'	=> 'input',
								'size'	=> 30,
								'value'	=> $site->email
							)
						)
					) 
				) .
				$this->html->tr(
					$this->html->td(
						'Domain:'
					) .
					$this->html->td(
						$site->domain
					) 
				) .
				$this->html->tr(
					$this->html->td(
						'Username:'
					) .
					$this->html->td(
						$site->username
					) 
				) .
				$this->html->tr(
					$this->html->td(
						'New Password:'
					) .
					$this->html->td(
						$this->html->textfield('new_password',
							array(
								'class'	=> 'input',
								'size'	=> 20
							)
						) 
					)
				) .
				$this->html->tr(
					$this->html->td(
						'Server:'
					) .
					$this->html->td(
						$site->servname
					) 
				) ,
				array('class' => 'maintable')
			);

		$ret .= '<hr size=1>' .
				$this->tableHeader(
					$this->ajaxSubmitButton('Save Changes', 'frmMain')
				) .
				$this->html->form_end( );

		return $ret;
	}
}

?>
