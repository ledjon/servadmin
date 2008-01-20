<?
	//var_dump($this->ajax->data);
	$mod = $this->ajax->data->Get('m');

	$res = 'error (uknown)';

	switch($mod)
	{
		case 'welcome':
			$res = welcome( $this );
			break;
		case 'modusers':
			$res = modusers( $this );
			break;
		case 'defaultaddress':
			$res = defaultAddress( $this );
			break;
		case 'forwards':
			$res = forwards( $this );
			break;
		default:
			$res ="Need valid mod (m) value ($mod)";
	}
	
	$this->ajax->response['content'] = $res;

function welcome( $self )
{
	//$ret = $self->wizardLink('email');
	$ret .= $self->getHelpNote('email/welcome');

	return $ret;
}

function modusers( $self )
{
	$exec = $self->ajax->data->Get('exec');

	$u = $self->userDetails( );
	$interface = $self->getInterface('mail');

	if($exec)
	{
		// executing
		//var_dump($self->ajax->data);
		//die("did submit");
		$keys = $self->ajax->data->Keys( );

		if($user = $self->ajax->data->Get('new_address')
			and $pass = $self->ajax->data->Get('new_password')
		)
		{
			//die("user: $user");
			$interface->call('addUser', array( $u->domain, $user, $pass ));	
			$interface->checkFault( );
		}

		// changed passwords?
		foreach($self->matchKeys( $keys, '/^newpass_(.*)$/' ) as $k)
		{
			// change password
			$key = 'newpass_' . $k;
			if($pass = $self->ajax->data->Get($key))
			{
				$pass = trim($pass);

				$interface->call('passwd', array( $u->domain, $k, $pass ));
				$interface->checkFault( );
			}
		}

		// deleted users?
		foreach($self->matchKeys( $keys, '/^del_(.*)$/' ) as $k)
		{
			if($k)
			{
				$interface->call('delUser', array( $u->domain, $k ));
				$interface->checkFault( );
			}
		}

		// this tells it to reload the page (right side) with something
		$self->ajax->response['reloadContent'] = 'modusers';

		return "Completed...";
	}
	else
	{
		$ret = '';
		$res = $interface->call('getUsers', array( $u->domain ));	
		$interface->checkFault( );

		if(is_array($res))
		{
			sort($res);
			$tbl = '';
			foreach($res as $user)
			{
				//$ret .= sprintf("%s@%s<br>", $user, $u->domain);
				$tbl .= $self->html->tr(
					$self->html->td(
						($user == 'postmaster' ?
							$self->html->checkbox('', 0, false, array('disabled' => 1)) :
							$self->html->checkbox('del_' . $user, 1, false,
								array(
									'onclick'	=> 
										'if(this.checked) { return confirm(\'Are you sure you want to delete this user?\') }'
								)
							)
						),
						array('width' => 0)
					) .
					$self->html->td(
						sprintf("%s@%s", $user, $u->domain),
						array('width' => '50%')
					) .
					$self->html->td(
						$self->html->password_field('newpass_' . $user, 
							array(
								'class' => 'input'
							)
						),
						array('width' => '50%')
					)
				);
			}

			if($tbl)
			{
				// header
				$tbl = 
				$self->html->tr(
					$self->html->td(
						'DEL&nbsp;',	
						array('width' => 0, 'class' => 'topcells')
					) .
					$self->html->td(
						'Email Account',
						array('width' => '50%', 'class' => 'topcells')
					) .
					$self->html->td(
						'New Passowrd',
						array('width' => '50%', 'class' => 'topcellsRight')
					)
				) . $tbl;

				$tbl = $self->html->table(
					$tbl,
					array('class' => 'maintable')
				);
			}
			
			$ret .= $self->tableHeader("Existing Email Accounts") . $tbl . '<hr size=1>';
		}

		// new accounts
		$ret .=
			$self->tableHeader("New Email Account");

		$ret .=
			$self->html->table(
				$self->html->tr(
					$self->html->td(
						'Email Address',
						array('width' => '50%', 'class' => 'topcells')
					) .
					$self->html->td(
						'Password',
						array('width' => '50%', 'class' => 'topcellsRight')
					) 
				) .
				$self->html->tr(
					$self->html->td(
						$self->html->textfield('new_address', 
							array(
								'class' => 'input',
								'size'	=> '10'
							)
						) . '@' . $u->domain
					) .
					$self->html->td(
						$self->html->password_field('new_password',
							array(
								'class'	=> 'input',
								'size'	=> '30'
							)
						)
					)  
				),
				array('class' => 'maintable')
			);

		// submit button
		$ret .= '<hr size=1>' .
			$self->tableHeader(
				$self->ajaxSubmitButton('Save Changes', 'frmMain')
			);

		// wrap it in a form
		$ret = $self->html->form_start(
					array(
						'name'	=> 'frmMain',
						'id'	=> 'frmMain',
						'action'	=> 'modusers',
						'onsubmit'	=> 'return findRealSubmit(this);'
					)
				) . $ret .
				$self->html->form_end( );

		return $ret;
	}
}

function defaultAddress( $self )
{
	$u = $self->userDetails( );
	$interface = $self->getInterface( "mail" );

	// is executing?
	$exec = $self->ajax->data->Get('exec');

	if($exec)
	{
		$addr = trim($self->ajax->data->Get('catchall'));

		if($self->ajax->data->Get('nocatchall')
			|| empty($addr))
		{
			// set to false
			$addr = false;
		}

		$res = $interface->call('catchAll', array( $u->domain, $addr ));
		$interface->checkFault( );

		if(! $res )
		{
			die("Unable to set default (unknown reason)");
		}

		$self->ajax->response['reloadContent'] = 'defaultaddress';

		return "Completed...";
	}
	else
	{
		$res = $interface->call('catchAll', array( $u->domain ));
		$interface->checkFault( );

		$ret = $self->html->form_start(
			array(
				'id'	=> 'frmMain',
				'name'	=> 'frmMain',
				'action'	=> 'defaultaddress',
				'onsubmit'	=> 'return findRealSubmit(this);'
			)
		) .
		$self->tableHeader("Default Address Management") .
		// '<hr size=1>' .
		// $self->getHelpNote('email/catchall') .
		$self->html->table(
			$self->html->tr(
				$self->html->td(
					'Unrouted emails to go: ' .
					$self->html->textfield('catchall',
						array(
							'value'	=> $res,
							'size'	=> 25,
							'class'	=> 'input',
							'onkeydown'	=>
								'_findObj(\'nocatchall\').checked = false;'
						)
					),
					array('align' => 'center')
				) .
				$self->html->td(
					'No Default Address ' .
					$self->html->checkbox('nocatchall', 1,
						($res ? false : true),
						array(
							'onclick'	=> 'if(this.checked) { _findObj(\'catchall\').value = \'\'; }'
						)
					)
				)
			),
			array('class' => 'maintable')
		) . '<hr size=1>' .
		$self->tableHeader(
			$self->ajaxSubmitButton('Save Changes', 'frmMain')
		)
		. $self->html->form_end( );

		return $ret;
	}
}

function forwards( $self )
{
	$exec = $self->ajax->data->Get('exec');

	$u = $self->userDetails( );
	$interface = $self->getInterface('mail');

	if($exec)
	{
		// executing
		//var_dump($self->ajax->data);
		//die("did submit");
		$keys = $self->ajax->data->Keys( );

		if($user = $self->ajax->data->Get('new_forward')
			and $list = $self->ajax->data->Get('new_forward_list')
		)
		{
			//die("user: $user : $list");
			$parts = explode("\n", $list);
			
			foreach($parts as $addr)
			{
				$addr = trim($addr);

				if(empty($addr))
				{
					continue;
				}

				$interface->call('addUserAlias', array( $u->domain, $user, $addr ));	
				$interface->checkFault( );
			}
		}

		// change users?
		foreach($self->matchKeys( $keys, '/^changed_(.*)$/' ) as $k)
		{
			// value not set t 1
			if($self->ajax->data->Get('changed_' . $k ) != '1')
			{
				continue;
			}

			// which user set?
			$key = 'users_' . $k;
			$list = $self->ajax->data->Get($key);

			// are we just going to delete it anyway?
			if(! $self->ajax->data->Get('del_' . $k ) )
			{
				// delete it first, then recreate it
				$interface->call('delUserAlias', array( $u->domain, $k ));
				$interface->checkFault( );

				$parts = explode("\n", $list);
				foreach($parts as $addr)
				{
					$addr = trim($addr);

					if(empty($addr))
					{
						continue;
					}

					$interface->call('addUserAlias', array( $u->domain, $k, $addr ));	
					$interface->checkFault( );
				}
			}
		}

		// delete alias
		foreach($self->matchKeys( $keys, '/^del_(.*)$/' ) as $k)
		{
			if($k)
			{
				// 3rd param == false -- to delete the whole alias
				$interface->call('delUserAlias', array( $u->domain, $k, false ));
				$interface->checkFault( );
			}
		}

		// this tells it to reload the page (right side) with something
		$self->ajax->response['reloadContent'] = 'forwards';

		return "Completed...";
	}
	else
	{
		$ret = '';
		$res = $interface->call('listUserAliases', array( $u->domain ));	
		$interface->checkFault( );

		if(is_array($res))
		{
			//var_dump($res);
			//exit;
			ksort($res);
			$tbl = '';
			foreach($res as $user => $list)
			{
				//$ret .= sprintf("%s@%s<br>", $user, $u->domain);
				$tbl .= $self->html->tr(
					$self->html->td(
						$self->html->checkbox('del_' . $user, 1, false,
							array(
								'onclick'	=> 
									'if(this.checked) { return confirm(\'Are you sure you want to delete this alias?\') }'
							)
						),
						array('width' => 0)
					) .
					$self->html->td(
						sprintf("%s@%s", $user, $u->domain),
						array('width' => '50%')
					) .
					$self->html->td(
						$self->html->textarea('users_' . $user,
							array(
								'class'	=> 'input',
								'value'	=> implode("\n", $list) ,
								'cols'	=> 35,
								'rows'	=> 3,
								'onchange'	=> '_findObj(\'changed_' . $user .'\').value = 1;'
							)
						) .
						// hidden field for 'changes' to the aliases lists
						$self->html->hidden('changed_' . $user,
							array(
								'value'	=> 0
							)
						)
						,
						array('width' => '50%')
					)
				);
			}

			if($tbl)
			{
				// header
				$tbl = 
				$self->html->tr(
					$self->html->td(
						'DEL&nbsp;',	
						array('width' => 0, 'class' => 'topcells')
					) .
					$self->html->td(
						'Email Forward',
						array('width' => '50%', 'class' => 'topcells')
					) .
					$self->html->td(
						'Forward List',
						array('width' => '50%', 'class' => 'topcellsRight')
					)
				) . $tbl;

				$tbl = $self->html->table(
					$tbl,
					array('class' => 'maintable')
				);
			}
			
			$ret .= $self->tableHeader("Existing Email Forwards") . $tbl . '<hr size=1>';
		}

		// new accounts
		$ret .=
			$self->tableHeader("New Email Forward");

		$ret .=
			$self->html->table(
				$self->html->tr(
					$self->html->td(
						'Email Address',
						array('width' => '50%', 'class' => 'topcells')
					) .
					$self->html->td(
						'Forward To (list)',
						array('width' => '50%', 'class' => 'topcellsRight')
					) 
				) .
				$self->html->tr(
					$self->html->td(
						$self->html->textfield('new_forward', 
							array(
								'class' => 'input',
								'size'	=> '10'
							)
						) . '@' . $u->domain
					) .
					$self->html->td(
						$self->html->textarea('new_forward_list',
							array(
								'class'	=> 'input',
								'cols'	=> 35,
								'rows'	=> 3
							)
						)
					)  
				),
				array('class' => 'maintable')
			);

		// submit button
		$ret .= '<hr size=1>' .
			$self->tableHeader(
				$self->ajaxSubmitButton('Save Changes', 'frmMain')
			);

		// wrap it in a form
		$ret = $self->html->form_start(
					array(
						'name'	=> 'frmMain',
						'id'	=> 'frmMain',
						'action'	=> 'forwards',
						'onsubmit'	=> 'return findRealSubmit(this);'
					)
				) . $ret .
				$self->html->form_end( );

		return $ret;
	}
}

?>
