<?
	//var_dump($self->ajax->data);
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

function welcome( $self )
{
	return $self->getHelpNote('admin_start/welcome');
}

function newserver( $self )
{
	$exec = $self->ajax->data->Get('exec');

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
			$args[$key] = $self->ajax->data->Get( $key );
		}

		$self->db->AutoExecute( 'server', $args, 'INSERT' )
			or $self->raiseError( $self->db->ErrorMsg( ) );

		$self->ajax->response['reloadContent'] = 'modservers';

		return "Completed...";
	}
	else
	{
		return _server_table( $self, array( ) );
	}
}

function modservers( $self )
{
	$ret = $self->tableHeader("Existing Servers");

	$res = $self->db->Execute("select serverid, servname from server order by servname")
		or $self->raiseerror( $self->db->ErrorMsg( ) );

	$tbl = '';
	while($row = $res->FetchNextObj( ))
	{
		$tbl .=
			$self->html->tr(
				$self->html->td(
					' [&nbsp;' .
					$self->html->ahref(
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
				$self->html->td(
					' [&nbsp;' .
					$self->html->ahref(
						'javascript:void(0)',
						'MOD',
						array(
							'onclick' => "loadRight(action, 'modserver', " . $row->serverid . ")"
						)
					) . '&nbsp;] ',
					array('width' => 2)
				) .
				$self->html->td(
					' [&nbsp;' .
					$self->html->ahref(
						'javascript:void(0)',
						'PING',
						array(
							'onclick' => "loadRight(action, 'pingserver', " . $row->serverid . ")"
						)
					) . '&nbsp;] ',
					array('width' => 2)
				) .
				$self->html->td(
					$row->servname
				) 
			);
	}

	if($tbl)
	{
		$tbl =
			$self->html->tr(
				$self->html->td(
					'Delete',
					array('class' => 'topcells')
				) .
				$self->html->td(
					'Modify',
					array('class' => 'topcells')
				) .
				$self->html->td(
					'Ping',
					array('class' => 'topcells')
				) .
				$self->html->td(
					'Server Mame',
					array('class' => 'topcells')
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

function modserver( $self )
{
	$exec = $self->ajax->data->Get('exec');

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
			$args[$key] = $self->ajax->data->Get( $key );
		}

		$self->db->AutoExecute( 'server', $args, 'UPDATE', 'serverid = ' . intval($self->ajax->data->Get('serverid')) )
			or $self->raiseError( $self->db->ErrorMsg( ) );

		$self->ajax->response['reloadContent'] = 'modservers';

		return "Completed...";
	}
	else
	{
		// select and display
		$res = $self->db->Execute("select * from server where serverid = ?",
				array( $self->ajax->data->Get('extra') )
			) or $self->raiseError( $self->db->ErrorMsg( ) );

		$row = $res->FetchRow( );

		$row['action'] = 'modserver';

		return _server_table( $self, $row );
	}
}

function pingserver( $self )
{
	$servid = $self->ajax->data->Get('extra');

	$interface = $self->getInterface( 'ping', intval($servid) );

	if(! $interface )
	{
		die("Unable to get interface object for this serverid ($servid)");
	}

	$response = $interface->call('doPing');
	$interface->checkFault( );

	return "Response from server: " . $response .
		($response == 'PONG' ? ' (Good)' : ' (Bad)');
}

function _server_table( $self, $args )
{
	$action = $args['action'];
	if(! $action )
	{
		$action = 'newserver';
	}

	$is_new = $args['serverid'] ? false : true;

	// show html table
	$ret = 
		$self->html->form_start(
			array(
				'id'	=> 'frmMain',
				'name'	=> 'frmMain',
				'action'	=> $action,
				'onsubmit'	=> 'return findRealSubmit(this);'
			)
		) .
		$self->tableHeader($is_new ? "Add New Server" : "Modify Server: " . $args['servname']) .
		$self->html->hidden('serverid', array( 'value' => $args['serverid'] )) .
		$self->html->table(
			$self->html->tr(
				$self->html->td(
					'Server Name',
					array('width' => '50%')
				) .
				$self->html->td(
					$self->html->textfield('servname',
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
			$self->html->tr(
				$self->html->td(
					'Server Auth Key'
				) .
				$self->html->td(
					$self->html->textfield('servkey',
						array(
							'class'	=> 'input',
							'size'	=> 25,
							'value'	=> $args['servkey']
						)
					) . ' [ <a href="javascript:void(0)" onclick="generatePassword(\'servkey\')">Generate</a> ]'
				)
			) .
			$self->html->tr(
				$self->html->td(
					'Server Interface (API) URL'
				) .
				$self->html->td(
					$self->html->textfield('servurl',
						array(
							'class'	=> 'input',
							'size'	=> 30,
							'value'	=> $args['servurl']
						)
					)
				)
			) .
			$self->html->tr(
				$self->html->td(
					'Default FTP URI'
				) .
				$self->html->td(
					$self->html->textfield('ftpuri',
						array(
							'class'	=> 'input',
							'size'	=> 25,
							'value'	=> $args['ftpuri']
						)
					) 
				)
			) .
			$self->html->tr(
				$self->html->td(
					'Name Server #1'
				) .
				$self->html->td(
					$self->html->textfield('nameserver1',
						array(
							'class'	=> 'input',
							'size'	=> 30,
							'value'	=> $args['nameserver1']
						)
					) 
				)
			) .
			$self->html->tr(
				$self->html->td(
					'Name Server #2'
				) .
				$self->html->td(
					$self->html->textfield('nameserver2',
						array(
							'class'	=> 'input',
							'size'	=> 30,
							'value'	=> $args['nameserver2']
						)
					) 
				)
			) .
			$self->html->tr(
				$self->html->td(
					'Default Mail Server'
				) .
				$self->html->td(
					$self->html->textfield('mailservname',
						array(
							'class'	=> 'input',
							'size'	=> 20,
							'value'	=> $args['mailservname']
						)
					) 
				)
			) .
			$self->html->tr(
				$self->html->td(
					'Default SMTP Server'
				) .
				$self->html->td(
					$self->html->textfield('smtpservname',
						array(
							'class'	=> 'input',
							'size'	=> 20,
							'value'	=> $args['smtpservname']
						)
					) 
				)
			) .
			$self->html->tr(
				$self->html->td(
					'Temporary Site URL'
				) .
				$self->html->td(
					$self->html->textfield('tmpurl',
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
		$self->tableHeader(
			$self->ajaxSubmitButton('Submit Changes', 'frmMain')
		) .
		$self->html->form_end( );

	// execute this after the page is showing
	$self->ajax->response['onLoad'] = '_findObj(\'servname\').focus()';
	return $ret;
}

function delserver( $self )
{
	return "Not yet implemented (for security reasons) -- " .
		" please delete it manually from the database if you *really* mean to do this!";
}

?>
