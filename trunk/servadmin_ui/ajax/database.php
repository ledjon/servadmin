<?
	//var_dump($this->ajax->data);
	$mod = $this->ajax->data->Get('m');

	$res = 'error (uknown)';

	switch($mod)
	{
		case 'welcome':
			$res = welcome( $this );
			break;
		case 'myusers':
			$res = myusers( $this );
			break;
		case 'mydatabases':
			$res = mydatabases( $this );
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
	return $self->getHelpNote('database/welcome');
}

function myusers( $self )
{
	$exec = $self->ajax->data->Get('exec');

	$u = $self->userDetails( );
	$interface = $self->getInterface('mysql');

	if($exec)
	{
		// executing
		//var_dump($self->ajax->data);
		//die("did submit");
		$keys = $self->ajax->data->Keys( );

		if($user = $self->ajax->data->Get('new_user')
			and $pass = $self->ajax->data->Get('new_password')
		)
		{
			//die("user: $user");
			$interface->call('createUser', array( $u->username . '_' . $user, $pass ));	
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

				$interface->call('changePass', array( $k, $pass ));
				$interface->checkFault( );
			}
		}

		// deleted users?
		foreach($self->matchKeys( $keys, '/^del_(.*)$/' ) as $k)
		{
			if($k)
			{
				$interface->call('dropUser', array( $k ));
				$interface->checkFault( );
			}
		}

		// this tells it to reload the page (right side) with something
		$self->ajax->response['reloadContent'] = 'myusers';

		return "Completed...";
	}
	else
	{
		$ret = '';
		$res = $interface->call('listUsers', array( $u->username ));
		$interface->checkFault( );

		//var_dump($res);
		//exit;

		if(is_array($res))
		{
			sort($res);
			$tbl = '';
			foreach($res as $user)
			{
				//$ret .= sprintf("%s@%s<br>", $user, $u->domain);
				$tbl .= $self->html->tr(
					$self->html->td(
						$self->html->checkbox('del_' . $user, 1, false,
							array(
								'onclick'	=> 
									'if(this.checked) { return confirm(\'Are you sure you want to delete this user?\') }'
							)
						),
						array('width' => 0)
					) .
					$self->html->td(
						$user,
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
						'Database User',
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
			
			$ret .= $self->tableHeader("Existing Database Users") . $tbl . '<hr size=1>';
		}

		// new accounts
		$ret .=
			$self->tableHeader("New Database User");

		$ret .=
			$self->html->table(
				$self->html->tr(
					$self->html->td(
						'Database Username',
						array('width' => '50%', 'class' => 'topcells')
					) .
					$self->html->td(
						'Password',
						array('width' => '50%', 'class' => 'topcellsRight')
					) 
				) .
				$self->html->tr(
					$self->html->td(
						$u->username . '_' .
						$self->html->textfield('new_user', 
							array(
								'class' => 'input',
								'size'	=> '10',
								'maxlength'	=> (13 - strlen($u->username))
							)
						)
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
						'action'	=> 'myusers',
						'onsubmit'	=> 'return findRealSubmit(this);'
					)
				) . $ret .
				$self->html->form_end( );

		return $ret;
	}
}

function mydatabases( $self )
{
	$exec = $self->ajax->data->Get('exec');

	$u = $self->userDetails( );
	$interface = $self->getInterface('mysql');

	if($exec)
	{
		// executing
		//var_dump($self->ajax->data);
		//die("did submit");
		$keys = $self->ajax->data->Keys( );

		if($newdb = $self->ajax->data->Get('new_database'))
		{
			//die("user: $user");
			$interface->call('createDatabase', array( $u->username . '_' . $newdb ));	
			$interface->checkFault( );
		}

		// revoke access
		foreach($self->matchKeys( $keys, '/^revoke_(.*)$/' ) as $k)
		{
			list($db, $user) = explode(':', $k);
			
			$interface->call('revokeAccess', array( $user, $db ));
			$interface->checkFault( );
		}

		// grant access
		foreach($self->matchKeys( $keys, '/^grant_(.*)$/' ) as $k)
		{
			$key = 'grant_' . $k;

			if($user = trim($self->ajax->data->Get($key)))
			{
				$interface->call('grantAccess', array( $user, $k ));
				$interface->checkFault( );
			}
		}
		
		// delete databases?
		foreach($self->matchKeys( $keys, '/^deldb_(.*)$/' ) as $k)
		{
			if($k)
			{
				$interface->call('dropDatabase', array( $k ));
				$interface->checkFault( );
			}
		}

		// this tells it to reload the page (right side) with something
		$self->ajax->response['reloadContent'] = 'mydatabases';

		return "Completed...";
	}
	else
	{
		$ret = '';

		// user list
		$cur_users = $interface->call('listUsers', array( $u->username ) );
		$interface->checkFault( );

		// access lists
		$access = $interface->call('listAccess', array( $u->username ) );
		$interface->checkFault( );
		//var_dump($access);
		//exit;

		// existing databases
		$res = $interface->call('listDatabases', array( $u->username ));	
		$interface->checkFault( );

		//var_dump($res);
		//exit;

		if(is_array($res))
		{
			sort($res);

			// get access lists
			$tbl = '';
			$mod_i = 0;
			foreach($res as $db)
			{
				// user access for this database
				$users = $access[$db];

				// users not in the datbase
				$users_not_in_db = $cur_users;

				$existing_users = 'None';
				if(is_array($users))
				{
					$users_not_in_db = array_diff($cur_users, $users);

					$existing_users = '';
					
					foreach($users as $user)
					{
						$existing_users .= 
							$self->html->checkbox('revoke_' . $db . ':' . $user, 1, false) .
							' ' . $user . '<br>';
					}
				}


				//var_dump($users_not_in_db);

				$users_need_access = array( );
				foreach($users_not_in_db as $us)
				{
					$users_need_access[$us] = $us;
				}
				// make a dropdown of users not in the database
				$need_access = $self->html->popup_menu('grant_' . $db,
								array_merge(
									array('' => '--Select User--'),
									$users_need_access
								)
								, 
								'', // default
								array(
									'class'	=> 'input'
								)
							);

				//var_dump($access);
				//var_dump($users);
				//exit;

				$tbl .= $self->html->tr(
					$self->html->td(
						$self->html->checkbox('deldb_' . $db, 1, false,
							array(
								'onclick'	=> 
									'if(this.checked) { return confirm(\'Are you sure you want to delete this database?\') }'
							)
						),
						array('width' => 0, 'valign' => 'center')
					) .
					$self->html->td(
						$db,
						array('width' => '33%', 'valign' => 'center')
					) .
					$self->html->td(
						// current users
						$existing_users
						,
						array('width' => '33%')
					) .
					$self->html->td(
						$need_access
						,
						array('width' => '33%')
					),
					array('style' => (($mod_i++ % 2) ? 'background: #B0B3B4' : ''))
				);
			}
			//exit;

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
						'Database Name',
						array('width' => '33%', 'class' => 'topcells')
					) .
					$self->html->td(
						'Current Users (Revoke)',
						array('width' => '33%', 'class' => 'topcells')
					) .
					$self->html->td(
						'New User (Grant)',
						array('width' => '33%', 'class' => 'topcellsRight')
					)
				) . $tbl;

				$tbl = $self->html->table(
					$tbl,
					array('class' => 'maintable')
				);
			}
			
			$ret .= $self->tableHeader("Existing Database Access Lists") . $tbl . '<hr size=1>';
		}

		// new accounts
		$ret .=
			$self->tableHeader("Create New Database");

		$ret .=
			$self->html->table(
				$self->html->tr(
					$self->html->td(
						'Database Name',
						array('width' => '100%', 'class' => 'topcellsRight', 'align' => 'center')
					) 
				) .
				$self->html->tr(
					$self->html->td(
						$u->username . '_' .
						$self->html->textfield('new_database', 
							array(
								'class' => 'input',
								'size'	=> '10'
							)
						) ,
						array('align' => 'center')
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
						'action'	=> 'mydatabases',
						'onsubmit'	=> 'return findRealSubmit(this);'
					)
				) . $ret .
				$self->html->form_end( );

		return $ret;
	}
}

?>
