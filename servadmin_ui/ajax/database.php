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

function welcome( $this )
{
	return $this->getHelpNote('database/welcome');
}

function myusers( $this )
{
	$exec = $this->ajax->data->Get('exec');

	$u = $this->userDetails( );
	$interface = $this->getInterface('mysql');

	if($exec)
	{
		// executing
		//var_dump($this->ajax->data);
		//die("did submit");
		$keys = $this->ajax->data->Keys( );

		if($user = $this->ajax->data->Get('new_user')
			and $pass = $this->ajax->data->Get('new_password')
		)
		{
			//die("user: $user");
			$interface->call('createUser', array( $u->username . '_' . $user, $pass ));	
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

				$interface->call('changePass', array( $k, $pass ));
				$interface->checkFault( );
			}
		}

		// deleted users?
		foreach($this->matchKeys( $keys, '/^del_(.*)$/' ) as $k)
		{
			if($k)
			{
				$interface->call('dropUser', array( $k ));
				$interface->checkFault( );
			}
		}

		// this tells it to reload the page (right side) with something
		$this->ajax->response['reloadContent'] = 'myusers';

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
				$tbl .= $this->html->tr(
					$this->html->td(
						$this->html->checkbox('del_' . $user, 1, false,
							array(
								'onclick'	=> 
									'if(this.checked) { return confirm(\'Are you sure you want to delete this user?\') }'
							)
						),
						array('width' => 0)
					) .
					$this->html->td(
						$user,
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
						'Database User',
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
			
			$ret .= $this->tableHeader("Existing Database Users") . $tbl . '<hr size=1>';
		}

		// new accounts
		$ret .=
			$this->tableHeader("New Database User");

		$ret .=
			$this->html->table(
				$this->html->tr(
					$this->html->td(
						'Database Username',
						array('width' => '50%', 'class' => 'topcells')
					) .
					$this->html->td(
						'Password',
						array('width' => '50%', 'class' => 'topcellsRight')
					) 
				) .
				$this->html->tr(
					$this->html->td(
						$u->username . '_' .
						$this->html->textfield('new_user', 
							array(
								'class' => 'input',
								'size'	=> '10',
								'maxlength'	=> (13 - strlen($u->username))
							)
						)
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
						'action'	=> 'myusers',
						'onsubmit'	=> 'return findRealSubmit(this);'
					)
				) . $ret .
				$this->html->form_end( );

		return $ret;
	}
}

function mydatabases( $this )
{
	$exec = $this->ajax->data->Get('exec');

	$u = $this->userDetails( );
	$interface = $this->getInterface('mysql');

	if($exec)
	{
		// executing
		//var_dump($this->ajax->data);
		//die("did submit");
		$keys = $this->ajax->data->Keys( );

		if($newdb = $this->ajax->data->Get('new_database'))
		{
			//die("user: $user");
			$interface->call('createDatabase', array( $u->username . '_' . $newdb ));	
			$interface->checkFault( );
		}

		// revoke access
		foreach($this->matchKeys( $keys, '/^revoke_(.*)$/' ) as $k)
		{
			list($db, $user) = explode(':', $k);
			
			$interface->call('revokeAccess', array( $user, $db ));
			$interface->checkFault( );
		}

		// grant access
		foreach($this->matchKeys( $keys, '/^grant_(.*)$/' ) as $k)
		{
			$key = 'grant_' . $k;

			if($user = trim($this->ajax->data->Get($key)))
			{
				$interface->call('grantAccess', array( $user, $k ));
				$interface->checkFault( );
			}
		}
		
		// delete databases?
		foreach($this->matchKeys( $keys, '/^deldb_(.*)$/' ) as $k)
		{
			if($k)
			{
				$interface->call('dropDatabase', array( $k ));
				$interface->checkFault( );
			}
		}

		// this tells it to reload the page (right side) with something
		$this->ajax->response['reloadContent'] = 'mydatabases';

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
							$this->html->checkbox('revoke_' . $db . ':' . $user, 1, false) .
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
				$need_access = $this->html->popup_menu('grant_' . $db,
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

				$tbl .= $this->html->tr(
					$this->html->td(
						$this->html->checkbox('deldb_' . $db, 1, false,
							array(
								'onclick'	=> 
									'if(this.checked) { return confirm(\'Are you sure you want to delete this database?\') }'
							)
						),
						array('width' => 0, 'valign' => 'center')
					) .
					$this->html->td(
						$db,
						array('width' => '33%', 'valign' => 'center')
					) .
					$this->html->td(
						// current users
						$existing_users
						,
						array('width' => '33%')
					) .
					$this->html->td(
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
				$this->html->tr(
					$this->html->td(
						'DEL&nbsp;',	
						array('width' => 0, 'class' => 'topcells')
					) .
					$this->html->td(
						'Database Name',
						array('width' => '33%', 'class' => 'topcells')
					) .
					$this->html->td(
						'Current Users (Revoke)',
						array('width' => '33%', 'class' => 'topcells')
					) .
					$this->html->td(
						'New User (Grant)',
						array('width' => '33%', 'class' => 'topcellsRight')
					)
				) . $tbl;

				$tbl = $this->html->table(
					$tbl,
					array('class' => 'maintable')
				);
			}
			
			$ret .= $this->tableHeader("Existing Database Access Lists") . $tbl . '<hr size=1>';
		}

		// new accounts
		$ret .=
			$this->tableHeader("Create New Database");

		$ret .=
			$this->html->table(
				$this->html->tr(
					$this->html->td(
						'Database Name',
						array('width' => '100%', 'class' => 'topcellsRight', 'align' => 'center')
					) 
				) .
				$this->html->tr(
					$this->html->td(
						$u->username . '_' .
						$this->html->textfield('new_database', 
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
			$this->tableHeader(
				$this->ajaxSubmitButton('Save Changes', 'frmMain')
			);

		// wrap it in a form
		$ret = $this->html->form_start(
					array(
						'name'	=> 'frmMain',
						'id'	=> 'frmMain',
						'action'	=> 'mydatabases',
						'onsubmit'	=> 'return findRealSubmit(this);'
					)
				) . $ret .
				$this->html->form_end( );

		return $ret;
	}
}
