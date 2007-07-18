<?
	//var_dump($this->ajax->data);
	$mod = $this->ajax->data->Get('m');

	$res = 'error (uknown)';

	switch($mod)
	{
		case 'welcome':
			$res = welcome( $this );
			break;
		case 'passwd':
			$res = passwd( $this );
			break;
		case 'subdomains':
			$res = 'This function is not yet implemented.';
			break;
		case 'crontab':
			$res = crontab( $this );
			break;
		case 'contact':
			$res = contact( $this );
			break;
		case 'backup':
			$res = 'N/A';
			break;
		default:
			$res ="Need valid mod (m) value ($mod)";
	}
	
	$this->ajax->response['content'] = $res;

function welcome( $this )
{
	$u = $this->userDetails( );
	$interface = $this->getInterface('user');

	/*
	$interface->call('addUser', array( 'test' ) );
	$interface->checkFault( );

	$interface->call('passwd', array( 'testuser', 'test' ) );
	$interface->checkFault( );
	
	$interface->call('delUser', array( 'testuser', array('rmdir' => 1) ) );
	$interface->checkFault( );
	*/

	$res = $interface->call('getUser', array( $u->username ) );
	$interface->checkFault( );
	//return $interface->responseData;

	if(! $res )
	{
		die("Unable to find details for user (" . $u->username . ')');
	}

	$ret = sprintf("Home Directory: %s<br>Shell: %s",
			$res['home'],
			$res['shell']
		);
	
	$has_shell = ($res['shell'] == '/sbin/nologin' ? false : true);
	
	$ret = $this->html->table(
		$this->html->tr(
			$this->html->td(
				'Home Directory:'
			) .
			$this->html->td(
				$res['home']
			)
		) .
		$this->html->tr(
			$this->html->td(
				'SSH Shell:'
			) .
			$this->html->td(
				$res['shell'] . 
					($has_shell ? '' :
						' (disabled)'
					)
			)
		)
		,
		array('class' => 'maintable')
	);
	
	//$res = $interface->call('userExists', array($u->username));

	/*
	$ret = $interface->call('addUser',
			array(
				'testuser',
				array('opt' => 'value')
			)
		);
	$interface->checkFault( );
	*/
	/*
	ob_start();
	var_dump($interface);
	$r = ob_get_contents();
	ob_end_clean();
	fputs(fopen('/tmp/a', 'w'), $r);
	//print_r(array_keys((array)$interface));
	var_dump($interface->responseData);
	var_dump($ret);
	die("val: $ret");
	*/

	return $ret;
}

function passwd( $this )
{
	$exec = $this->ajax->data->Get('exec');

	if($exec)
	{
		$u = $this->userDetails( );
		$interface = $this->getInterface('user');

		$p1 = trim($this->ajax->data->Get('newpass1'));
		$p2 = trim($this->ajax->data->Get('newpass2'));

		// add a check to make sure the password is secure enough
		if(strlen($p1) < 6)
		{
			die("Password must be at least 6 characters long.");
		}

		if($p1 != $p2)
		{
			die("Passwords do not match.");
		}

		// everything checks out

		// change it on the server first
		$interface->call('passwd', array( $u->username, $p1 ));
		$interface->checkFault( );

		// if we get this far, it went okay,
		// so we need to update this in our database
		$this->setUserDetail('password', md5($p1));

		return "Password successfully updated.";
	}
	else
	{
		$ret = 
			$this->html->form_start(
				array( 
					'name'	=> 'frmPasswd',
					'id'	=> 'frmPasswd',
					'action'	=> 'passwd'
				)
			) .
			$this->html->table(
			$this->html->tr(
				$this->html->td(
					'<center><b>Change Password</b>',
					array('colspan' => '2')
				)
			) .
			$this->html->tr(
				$this->html->td(
					'New password:',
					array('align' => 'right')
				) .
				$this->html->td(
					$this->html->password_field(
						'newpass1', 
						array('class' => 'input')
					)
				)
			) .
			$this->html->tr(
				$this->html->td(
					'New password again:',
					array('align' => 'right')
				) .
				$this->html->td(
					$this->html->password_field(
						'newpass2', 
						array('class' => 'input')
					)
				)
			) .
			$this->html->tr(
				$this->html->td(
					$this->ajaxSubmitButton('Change Password', 'frmPasswd'),
					array('colspan' => 2, 'align' => 'center')
				)
			)
			,
			array('class' => 'maintable', 'width' => '400')
		) .
		$this->html->form_end( ) .
		$this->getHelpNote('account/passwd');

		return $ret;
	}
}

function contact( $this )
{
	$exec = $this->ajax->data->Get('exec');

	if($exec)
	{
		// update values
		if($name = $this->ajax->data->Get('ownername'))
		{
			$this->setUserDetail( 'ownername', $name );
		}

		if($email = $this->ajax->data->Get('email'))
		{
			$this->setUserDetail( 'email', $email );
		}
	
		$this->ajax->response['reloadContent'] = 'contact';
		
		return "Contact Details Updated...";
	}
	else
	{
		// list existing values
		$u = $this->userDetails( );

		$ret = $this->html->form_start(
			array(
				'id'	=> 'frmMain',
				'name'	=> 'frmMain',
				'action'	=> 'contact'
			)
		) .
		$this->tableHeader("Update Contact Details") .
		$this->html->table(
			$this->html->tr(
				$this->html->td(
					'Contact Name:'
				) .
				$this->html->td(
					$this->html->textfield('ownername',
						array(	
							'value' => $u->ownername,
							'size'	=> 30,
							'class'	=> 'input'
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
							'value' => $u->email,
							'size'	=> 20,
							'class'	=> 'input'
						)
					)
				)
			),
			array('class' => 'maintable')
		) .
		'<hr size=1>' .
		$this->tableHeader(
			$this->ajaxSubmitButton("Save Changes", "frmMain")
		) .
		$this->html->form_end( );

		return $ret;
	}
}

function crontab( $this )
{
	$interface = $this->getInterface( 'crontab' );
	$u = $this->userDetails( );

	if($this->ajax->data->Get('exec'))
	{
		// save changes
		$keys = $this->ajax->data->Keys( );

		$final = array( );
		foreach($this->matchKeys( $keys, '/^command_(.*)$/' ) as $k)
		{
			$k = intval($k);

			// delete (skip) this entry?
			if($this->ajax->data->Get('del_' . $k))
			{
				continue;
			}

			$min = $this->ajax->data->Get('minute_' . $k);
			$hour = $this->ajax->data->Get('hour_' . $k);
			$day = $this->ajax->data->Get('day_' . $k);
			$month = $this->ajax->data->Get('month_' . $k);
			$weekday = $this->ajax->data->Get('weekday_' . $k);
			$command = $this->ajax->data->Get('command_' . $k);

			// echo implode(':', array( $min, $hour, $day, $month, $weekday, $command ));
			// exit;

			if($command
				&& strlen($min)
				&& strlen($hour)
				&& strlen($day)
				&& strlen($month)
				&& strlen($weekday)
			)
			{
				$final[] = array(
	                'minute'    => $min,
	                'hour'      => $hour,
	                'day'       => $day,
	                'month'     => $month,
	                'weekday'   => $weekday,
	                'command'   => $command
				);
			}
		}

		/*
		var_dump($final);
		exit;
		*/

		// set the finals now
		$interface->call('setEntries', array( $u->username, $final ));
		$interface->checkFault( );

		// set emailto
		$interface->call('setEmailTo', array( $u->username, $this->ajax->data->Get('emailto') ));
		$interface->checkFault( );

		// this tells it to reload the page (right side) with something
		$this->ajax->response['reloadContent'] = 'crontab';

		return "Completed...";
	}
	else
	{
		// does the user have a crontab?
		$exists = $interface->call('userExists', array( $u->username ));
		$interface->checkFault( );

		if(! $exists )
		{
			$emailTo = '';
			$items = array( );
		}
		else
		{
			// email to
			$emailTo = $interface->call('getEmailTo', array( $u->username ));
			$interface->checkFault( );

			// get listings
			$items = $interface->call('getEntries', array( $u->username ));
			$interface->checkFault( );
		}

		/*
		var_dump($emailTo);
		var_dump($items);
		exit;
		*/

		// entry for a new item
		$items[] = array( );
	

		// existing entries
		$tbl = '';
		foreach($items as $i => $item)
		{
			$tbl .= $this->html->tr(
				$this->html->td(
					( $item['command'] ?
						$this->html->checkbox('del_' . $i,
							array(
								'value'	=> '1'
							)
						)
						: 'NEW'
					)
					,
					array('width' => '0')
				) .
				$this->html->td(
					$this->html->textfield('minute_' . $i,
						array(
							'value' => $item['minute'],
							'size' => 2,
							'class' => 'input'
						)
					),
					array('width' => '0')
				) .
				$this->html->td(
					$this->html->textfield('hour_' . $i,
						array(
							'value' => $item['hour'],
							'size' => 2,
							'class' => 'input'
						)
					),
					array('width' => '0')
				) .
				$this->html->td(
					$this->html->textfield('day_' . $i,
						array(
							'value' => $item['day'],
							'size' => 2,
							'class' => 'input'
						)
					),
					array('width' => '0')
				) .
				$this->html->td(
					$this->html->textfield('month_' . $i,
						array(
							'value' => $item['month'],
							'size' => 2,
							'class' => 'input'
						)
					),
					array('width' => '0')
				) .
				$this->html->td(
					$this->html->textfield('weekday_' . $i,
						array(
							'value' => $item['weekday'],
							'size' => 2,
							'class' => 'input'
						)
					),
					array('width' => '0')
				) .
				$this->html->td(
					$this->html->textfield('command_' . $i,
						array(
							'value' => $item['command'],
							'size' => 55,
							'class' => 'input'
						)
					),
					array('width' => '100%')
				) 
			);
		}

		$tbl = $this->html->tr(
			$this->html->td(
				'DEL'
				,
				array('width' => '0', 'class' => 'topcells')
			) .
			$this->html->td(
				'MN',
				array('width' => '0', 'class' => 'topcells')
			) .
			$this->html->td(
				'HR',
				array('width' => '0', 'class' => 'topcells')
			) .
			$this->html->td(
				'DY',
				array('width' => '0', 'class' => 'topcells')
			) .
			$this->html->td(
				'MN',
				array('width' => '0', 'class' => 'topcells')
			) .
			$this->html->td(
				'WD',
				array('width' => '0', 'class' => 'topcells')
			) .
			$this->html->td(
				'Command to Execute',
				array('width' => '100%', 'class' => 'topcellsRight')
			) 
		) .
		$tbl;

		$ret =
			$this->html->form_start(
				array(
					'id'	=> 'frmMain',
					'name'	=> 'frmMain',
					'action'	=> 'crontab'
				)
			) .
			$this->tableHeader("Manage Crontab Entries") .
			$this->html->table(
				$this->html->tr(
					$this->html->td(
						'Ouput emailed to:'
					) .
					$this->html->td(
						$this->html->textfield('emailto', 
							array(
								'value'	=> $emailTo,
								'class'	=> 'input',
								'size'	=> '40'
							)
						)
					)
				),
				array('class' => 'maintable')
			) .
			$this->html->table(
				$tbl,
				array('class' => 'maintable')
			) .
			'<hr size=1>' .
			$this->tableHeader(
				$this->ajaxSubmitButton("Save Crontab Changes", 'frmMain')
			);
			$this->html->form_end( );


		return $ret;
	}
}

?>
