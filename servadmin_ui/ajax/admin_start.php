<?
	//var_dump($this->ajax->data);
	$mod = $this->ajax->data->Get('m');

	$res = 'error (uknown)';

	switch($mod)
	{
		case 'welcome':
			$res = welcome( $this );
			break;
		case 'newserver':
			$res = newserver( $this );
			break;
		case 'modserver':
			$res = modserver( $this );
			break;
		case 'pingserver':
			$res = pingserver( $this );
			break;
		case 'modservers':
			$res = modservers( $this );
			break;
		case 'delserver':
			$res = delserver( $this );
			break;
		default:
			$res ="Need valid mod (m) value ($mod)";
	}
	
	$this->ajax->response['content'] = $res;

function welcome( $this )
{
	return $this->getHelpNote('admin_start/welcome');
}

function newserver( $this )
{
	$exec = $this->ajax->data->Get('exec');

	if($exec)
	{
		$args = array( );
		$parts = array(
			'servname',
			'servkey',
			'servurl',
			'ftpuri',
			'nameserver1',
			'nameserver2',
			'mailservname',
			'smtpservname',
			'tmpurl'
		);

		foreach($parts as $key)
		{
			$args[$key] = $this->ajax->data->Get( $key );
		}

		$this->db->AutoExecute( 'server', $args, 'INSERT' )
			or $this->raiseError( $this->db->ErrorMsg( ) );

		$this->ajax->response['reloadContent'] = 'modservers';

		return "Completed...";
	}
	else
	{
		return _server_table( $this, array( ) );
	}
}

function modservers( $this )
{
	$ret = $this->tableHeader("Existing Servers");

	$res = $this->db->Execute("select serverid, servname from server order by servname")
		or $this->raiseerror( $this->db->ErrorMsg( ) );

	$tbl = '';
	while($row = $res->FetchNextObj( ))
	{
		$tbl .=
			$this->html->tr(
				$this->html->td(
					' [&nbsp;' .
					$this->html->ahref(
						'javascript:void(0)',
						'DEL',
						array(
							'onclick' =>
								"if(confirm('Are you *sure* you want to delete this server!?'))
									{ loadRight(action, 'delserver', " . $row->serverid . ") }"
						)
					) . '&nbsp;] ',
					array('width' => 2)
				) .
				$this->html->td(
					' [&nbsp;' .
					$this->html->ahref(
						'javascript:void(0)',
						'MOD',
						array(
							'onclick' => "loadRight(action, 'modserver', " . $row->serverid . ")"
						)
					) . '&nbsp;] ',
					array('width' => 2)
				) .
				$this->html->td(
					' [&nbsp;' .
					$this->html->ahref(
						'javascript:void(0)',
						'PING',
						array(
							'onclick' => "loadRight(action, 'pingserver', " . $row->serverid . ")"
						)
					) . '&nbsp;] ',
					array('width' => 2)
				) .
				$this->html->td(
					$row->servname
				) 
			);
	}

	if($tbl)
	{
		$tbl =
			$this->html->tr(
				$this->html->td(
					'Delete',
					array('class' => 'topcells')
				) .
				$this->html->td(
					'Modify',
					array('class' => 'topcells')
				) .
				$this->html->td(
					'Ping',
					array('class' => 'topcells')
				) .
				$this->html->td(
					'Server Mame',
					array('class' => 'topcells')
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

function modserver( $this )
{
	$exec = $this->ajax->data->Get('exec');

	if($exec)
	{
		$args = array( );
		$parts = array(
			'servname',
			'servkey',
			'servurl',
			'ftpuri',
			'nameserver1',
			'nameserver2',
			'mailservname',
			'smtpservname',
			'tmpurl'
		);

		foreach($parts as $key)
		{
			$args[$key] = $this->ajax->data->Get( $key );
		}

		$this->db->AutoExecute( 'server', $args, 'UPDATE', 'serverid = ' . intval($this->ajax->data->Get('serverid')) )
			or $this->raiseError( $this->db->ErrorMsg( ) );

		$this->ajax->response['reloadContent'] = 'modservers';

		return "Completed...";
	}
	else
	{
		// select and display
		$res = $this->db->Execute("select * from server where serverid = ?",
				array( $this->ajax->data->Get('extra') )
			) or $this->raiseError( $this->db->ErrorMsg( ) );

		$row = $res->FetchRow( );

		$row['action'] = 'modserver';

		return _server_table( $this, $row );
	}
}

function pingserver( $this )
{
	$servid = $this->ajax->data->Get('extra');

	$interface = $this->getInterface( 'ping', intval($servid) );

	if(! $interface )
	{
		die("Unable to get interface object for this serverid ($servid)");
	}

	$response = $interface->call('doPing');
	$interface->checkFault( );

	return "Response from server: " . $response .
		($response == 'PONG' ? ' (Good)' : ' (Bad)');
}

function _server_table( $this, $args )
{
	$action = $args['action'];
	if(! $action )
	{
		$action = 'newserver';
	}

	$is_new = $args['serverid'] ? false : true;

	// show html table
	$ret = 
		$this->html->form_start(
			array(
				'id'	=> 'frmMain',
				'name'	=> 'frmMain',
				'action'	=> $action,
				'onsubmit'	=> 'return findRealSubmit(this);'
			)
		) .
		$this->tableHeader($is_new ? "Add New Server" : "Modify Server: " . $args['servname']) .
		$this->html->hidden('serverid', array( 'value' => $args['serverid'] )) .
		$this->html->table(
			$this->html->tr(
				$this->html->td(
					'Server Name',
					array('width' => '50%')
				) .
				$this->html->td(
					$this->html->textfield('servname',
						array(
							'class'	=> 'input',
							'size'	=> 30,
							'value'	=> $args['servname'],
							'onblur'	=> 'defineServerDefaults(this)'
						)
					),
					array('width' => '50%')
				)
			) .
			$this->html->tr(
				$this->html->td(
					'Server Auth Key'
				) .
				$this->html->td(
					$this->html->textfield('servkey',
						array(
							'class'	=> 'input',
							'size'	=> 25,
							'value'	=> $args['servkey']
						)
					) . ' [ <a href="javascript:void(0)" onclick="generatePassword(\'servkey\')">Generate</a> ]'
				)
			) .
			$this->html->tr(
				$this->html->td(
					'Server Interface (API) URL'
				) .
				$this->html->td(
					$this->html->textfield('servurl',
						array(
							'class'	=> 'input',
							'size'	=> 30,
							'value'	=> $args['servurl']
						)
					)
				)
			) .
			$this->html->tr(
				$this->html->td(
					'Default FTP URI'
				) .
				$this->html->td(
					$this->html->textfield('ftpuri',
						array(
							'class'	=> 'input',
							'size'	=> 25,
							'value'	=> $args['ftpuri']
						)
					) 
				)
			) .
			$this->html->tr(
				$this->html->td(
					'Name Server #1'
				) .
				$this->html->td(
					$this->html->textfield('nameserver1',
						array(
							'class'	=> 'input',
							'size'	=> 30,
							'value'	=> $args['nameserver1']
						)
					) 
				)
			) .
			$this->html->tr(
				$this->html->td(
					'Name Server #2'
				) .
				$this->html->td(
					$this->html->textfield('nameserver2',
						array(
							'class'	=> 'input',
							'size'	=> 30,
							'value'	=> $args['nameserver2']
						)
					) 
				)
			) .
			$this->html->tr(
				$this->html->td(
					'Default Mail Server'
				) .
				$this->html->td(
					$this->html->textfield('mailservname',
						array(
							'class'	=> 'input',
							'size'	=> 20,
							'value'	=> $args['mailservname']
						)
					) 
				)
			) .
			$this->html->tr(
				$this->html->td(
					'Default SMTP Server'
				) .
				$this->html->td(
					$this->html->textfield('smtpservname',
						array(
							'class'	=> 'input',
							'size'	=> 20,
							'value'	=> $args['smtpservname']
						)
					) 
				)
			) .
			$this->html->tr(
				$this->html->td(
					'Temporary Site URL'
				) .
				$this->html->td(
					$this->html->textfield('tmpurl',
						array(
							'class'	=> 'input',
							'size'	=> 30,
							'value'	=> $args['tmpurl']
						)
					) 
				)
			) 
		,
			array('class' => 'maintable')
		) .
		'<hr size=1>' .
		$this->tableHeader(
			$this->ajaxSubmitButton('Submit Changes', 'frmMain')
		) .
		$this->html->form_end( );

	// execute this after the page is showing
	$this->ajax->response['onLoad'] = '_findObj(\'servname\').focus()';
	return $ret;
}

function delserver( $this )
{
	return "Not yet implemented (for security reasons) -- " .
		" please delete it manually from the database if you *really* mean to do this!";
}

?>
