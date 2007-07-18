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

function welcome( $this )
{
	//$ret = $this->wizardLink('email');
	$ret .= $this->getHelpNote('email/welcome');

	return $ret;
}

function modusers( $this )
{
	$exec = $this->ajax->data->Get('exec');

	$u = $this->userDetails( );
	$interface = $this->getInterface('mail');

	if($exec)
	{
		// executing
		//var_dump($this->ajax->data);
		//die("did submit");
		$keys = $this->ajax->data->Keys( );

		if($user = $this->ajax->data->Get('new_address')
			and $pass = $this->ajax->data->Get('new_password')
		)
		{
			//die("user: $user");
			$interface->call('addUser', array( $u->domain, $user, $pass ));	
			$interface->checkFault( );
		}

		// changed passwords?
		foreach($this->matchKeys( $keys, '/^newpass_(.*)$/' ) as $k)
		{
			// change password
			$key = 'newpass_' . $k;
			if($pass = $this->ajax->data->Get($key))
			{
				$pass = trim($pass);

				$interface->call('passwd', array( $u->domain, $k, $pass ));
				$interface->checkFault( );
			}
		}

		// deleted users?
		foreach($this->matchKeys( $keys, '/^del_(.*)$/' ) as $k)
		{
			if($k)
			{
				$interface->call('delUser', array( $u->domain, $k ));
				$interface->checkFault( );
			}
		}

		// this tells it to reload the page (right side) with something
		$this->ajax->response['reloadContent'] = 'modusers';

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
				$tbl .= $this->html->tr(
					$this->html->td(
						($user == 'postmaster' ?
							$this->html->checkbox('', 0, false, array('disabled' => 1)) :
							$this->html->checkbox('del_' . $user, 1, false,
								array(
									'onclick'	=> 
										'if(this.checked) { return confirm(\'Are you sure you want to delete this user?\') }'
								)
							)
						),
						array('width' => 0)
					) .
					$this->html->td(
						sprintf("%s@%s", $user, $u->domain),
						array('width' => '50%')
					) .
					$this->html->td(
						$this->html->password_field('newpass_' . $user, 
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
				$this->html->tr(
					$this->html->td(
						'DEL&nbsp;',	
						array('width' => 0, 'class' => 'topcells')
					) .
					$this->html->td(
						'Email Account',
						array('width' => '50%', 'class' => 'topcells')
					) .
					$this->html->td(
						'New Passowrd',
						array('width' => '50%', 'class' => 'topcellsRight')
					)
				) . $tbl;

				$tbl = $this->html->table(
					$tbl,
					array('class' => 'maintable')
				);
			}
			
			$ret .= $this->tableHeader("Existing Email Accounts") . $tbl . '<hr size=1>';
		}

		// new accounts
		$ret .=
			$this->tableHeader("New Email Account");

		$ret .=
			$this->html->table(
				$this->html->tr(
					$this->html->td(
						'Email Address',
						array('width' => '50%', 'class' => 'topcells')
					) .
					$this->html->td(
						'Password',
						array('width' => '50%', 'class' => 'topcellsRight')
					) 
				) .
				$this->html->tr(
					$this->html->td(
						$this->html->textfield('new_address', 
							array(
								'class' => 'input',
								'size'	=> '10'
							)
						) . '@' . $u->domain
					) .
					$this->html->td(
						$this->html->password_field('new_password',
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
			$this->tableHeader(
				$this->ajaxSubmitButton('Save Changes', 'frmMain')
			);

		// wrap it in a form
		$ret = $this->html->form_start(
					array(
						'name'	=> 'frmMain',
						'id'	=> 'frmMain',
						'action'	=> 'modusers',
						'onsubmit'	=> 'return findRealSubmit(this);'
					)
				) . $ret .
				$this->html->form_end( );

		return $ret;
	}
}

function defaultAddress( $this )
{
	$u = $this->userDetails( );
	$interface = $this->getInterface( "mail" );

	// is executing?
	$exec = $this->ajax->data->Get('exec');

	if($exec)
	{
		$addr = trim($this->ajax->data->Get('catchall'));

		if($this->ajax->data->Get('nocatchall')
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

		$this->ajax->response['reloadContent'] = 'defaultaddress';

		return "Completed...";
	}
	else
	{
		$res = $interface->call('catchAll', array( $u->domain ));
		$interface->checkFault( );

		$ret = $this->html->form_start(
			array(
				'id'	=> 'frmMain',
				'name'	=> 'frmMain',
				'action'	=> 'defaultaddress',
				'onsubmit'	=> 'return findRealSubmit(this);'
			)
		) .
		$this->tableHeader("Default Address Management") .
		// '<hr size=1>' .
		// $this->getHelpNote('email/catchall') .
		$this->html->table(
			$this->html->tr(
				$this->html->td(
					'Unrouted emails to go: ' .
					$this->html->textfield('catchall',
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
				$this->html->td(
					'No Default Address ' .
					$this->html->checkbox('nocatchall', 1,
						($res ? false : true),
						array(
							'onclick'	=> 'if(this.checked) { _findObj(\'catchall\').value = \'\'; }'
						)
					)
				)
			),
			array('class' => 'maintable')
		) . '<hr size=1>' .
		$this->tableHeader(
			$this->ajaxSubmitButton('Save Changes', 'frmMain')
		)
		. $this->html->form_end( );

		return $ret;
	}
}

function forwards( $this )
{
	$exec = $this->ajax->data->Get('exec');

	$u = $this->userDetails( );
	$interface = $this->getInterface('mail');

	if($exec)
	{
		// executing
		//var_dump($this->ajax->data);
		//die("did submit");
		$keys = $this->ajax->data->Keys( );

		if($user = $this->ajax->data->Get('new_forward')
			and $list = $this->ajax->data->Get('new_forward_list')
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
		foreach($this->matchKeys( $keys, '/^changed_(.*)$/' ) as $k)
		{
			// value not set t 1
			if($this->ajax->data->Get('changed_' . $k ) != '1')
			{
				continue;
			}

			// which user set?
			$key = 'users_' . $k;
			$list = $this->ajax->data->Get($key);

			// are we just going to delete it anyway?
			if(! $this->ajax->data->Get('del_' . $k ) )
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
		foreach($this->matchKeys( $keys, '/^del_(.*)$/' ) as $k)
		{
			if($k)
			{
				// 3rd param == false -- to delete the whole alias
				$interface->call('delUserAlias', array( $u->domain, $k, false ));
				$interface->checkFault( );
			}
		}

		// this tells it to reload the page (right side) with something
		$this->ajax->response['reloadContent'] = 'forwards';

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
				$tbl .= $this->html->tr(
					$this->html->td(
						$this->html->checkbox('del_' . $user, 1, false,
							array(
								'onclick'	=> 
									'if(this.checked) { return confirm(\'Are you sure you want to delete this alias?\') }'
							)
						),
						array('width' => 0)
					) .
					$this->html->td(
						sprintf("%s@%s", $user, $u->domain),
						array('width' => '50%')
					) .
					$this->html->td(
						$this->html->textarea('users_' . $user,
							array(
								'class'	=> 'input',
								'value'	=> implode("\n", $list) ,
								'cols'	=> 35,
								'rows'	=> 3,
								'onchange'	=> '_findObj(\'changed_' . $user .'\').value = 1;'
							)
						) .
						// hidden field for 'changes' to the aliases lists
						$this->html->hidden('changed_' . $user,
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
				$this->html->tr(
					$this->html->td(
						'DEL&nbsp;',	
						array('width' => 0, 'class' => 'topcells')
					) .
					$this->html->td(
						'Email Forward',
						array('width' => '50%', 'class' => 'topcells')
					) .
					$this->html->td(
						'Forward List',
						array('width' => '50%', 'class' => 'topcellsRight')
					)
				) . $tbl;

				$tbl = $this->html->table(
					$tbl,
					array('class' => 'maintable')
				);
			}
			
			$ret .= $this->tableHeader("Existing Email Forwards") . $tbl . '<hr size=1>';
		}

		// new accounts
		$ret .=
			$this->tableHeader("New Email Forward");

		$ret .=
			$this->html->table(
				$this->html->tr(
					$this->html->td(
						'Email Address',
						array('width' => '50%', 'class' => 'topcells')
					) .
					$this->html->td(
						'Forward To (list)',
						array('width' => '50%', 'class' => 'topcellsRight')
					) 
				) .
				$this->html->tr(
					$this->html->td(
						$this->html->textfield('new_forward', 
							array(
								'class' => 'input',
								'size'	=> '10'
							)
						) . '@' . $u->domain
					) .
					$this->html->td(
						$this->html->textarea('new_forward_list',
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
			$this->tableHeader(
				$this->ajaxSubmitButton('Save Changes', 'frmMain')
			);

		// wrap it in a form
		$ret = $this->html->form_start(
					array(
						'name'	=> 'frmMain',
						'id'	=> 'frmMain',
						'action'	=> 'forwards',
						'onsubmit'	=> 'return findRealSubmit(this);'
					)
				) . $ret .
				$this->html->form_end( );

		return $ret;
	}
}

?>
