<?
	require_once( dirname(__FILE__) . '/../lib/class.SupportSystem.php' );
	//var_dump($this->ajax->data);
	$mod = $this->ajax->data->Get('m');

	$res = 'error (uknown)';

	switch($mod)
	{
		case 'welcome':
			$res = welcome( $this );
			break;
		case 'newticket':
			$res = newticket( $this );
			break;
		case 'oldtickets':
			$res = oldtickets( $this );
			break;
		case 'viewticket':
			$res = viewticket( $this );
			break;
		default:
			$res ="Need valid mod (m) value ($mod)";
	}
	
	$this->ajax->response['content'] = $res;

function welcome( $self )
{
	return $self->getHelpNote('support/welcome');
}

function newticket( $self )
{
	$exec = $self->ajax->data->Get('exec');

	if($exec)
	{
		// save ticket
		if($topic = $self->ajax->data->Get('topic')
			and $content = $self->ajax->data->Get('content'))
		{
			$u = $self->userDetails( );
			$support = new SupportSystem( $self );
			$tid = $support->newTicket( $u->accountid );

			if(! $tid )
			{
				die("Unable to create ticket (Unknown error)");
			}

			$support->setTicketItem( $tid, 'topic', $topic );
			$support->setTicketItem( $tid, 'content', $content );
	
			return "Your support ticket has been created.";
		}
		else
		{
			die("Need a valid subject and body");
		}
	}
	else
	{
		// show form for new ticket
		$ret = $self->html->form_start(
			array(
				'id'	=> 'frmMain',
				'name'	=> 'frmMain',
				'action'	=> 'newticket'
			)
		) .
		$self->tableHeader("Create New Support Ticket") .
		$self->html->table(
			$self->html->tr(
				$self->html->td(
					'Ticket Subject:'
				) . 
				$self->html->td(
					$self->html->textfield('topic', array('class' => 'input', 'size' => 40))
				)
			) .
			$self->html->tr(
				$self->html->td(
					'Ticket Body:<br>' .
					$self->html->textarea(
						'content',
						array(
							'cols'	=> 50,
							'rows'	=> 20,
							'style'	=> 'width: 100%;',
							'class'	=> 'input'
						)
					)
					,
					array('colspan' => 2)
				) 
			)
			,
			array('class' => 'maintable')
		) .
		'<hr size=1>' .
		$self->tableHeader(
			$self->ajaxSubmitButton('Save Ticket', 'frmMain')
		);

		return $ret;
	}
}

function oldtickets( $self )
{
	/*
	$res = $self->db->Execute("select * from support_ticket where ticketstatus = 'open'")
			or $self->raiseError( $self->db->ErrorMsg( ) );
	*/
	$u = $self->userDetails( );
	$support = new SupportSystem( $self );
	$tickets = $support->getTickets( $u->accountid );

	//var_dump($tickets);

	$tbl = '';
	foreach($tickets as $t)
	{
		$tbl .= $self->html->tr(
			$self->html->td(
				$t->ticketid
			) .
			$self->html->td(
				$t->createdate
			) .
			$self->html->td(
				$self->html->ahref(
					'javascript:void(0)',
					$t->topic,
					array(
						'onclick' => "loadRight(action, 'viewticket', " . $t->ticketid . ")"
					)
				)
			) 
		);
	}

	if($tbl)
	{
		// headers
		$tbl =
		$self->html->tr(
			$self->html->td(
				'#',
				array('width' => 2, 'class' => 'topcells')
			) .
			$self->html->td(
				'Date',
				array('width' => '5', 'class' => 'topcells')
			) .
			$self->html->td(
				'Subject',
				array('width' => '370', 'class' => 'topcellsRight')
			) 
		)
		. $tbl;
	}

	// wrap it in table
	$ret = $self->html->table(
		$tbl,
		array('class' => 'maintable')
	);

	return $ret;
}

function viewticket( $self )
{
	$exec = $self->ajax->data->Get('exec');

	$tid = $self->ajax->data->Get('extra');
	$ptid = $tid;

	if(stristr($tid, '-'))
	{
		list($ptid, $tid) = explode('-', $tid, 2);
	}

	$ptid = intval($ptid);
	$tid = intval($tid);

	if(!$ptid)
	{
		die("Invalid ticketid: " . $ptid);
	}

	$u = $self->userDetails( );
	$support = new SupportSystem( $self );

	if($exec)
	{
		return "changes?";
	}
	else
	{
		$ticket = $support->getTicket( $tid )
			or die("Invalid ticketid: $tid");
		
		// child tickets
		$children = $support->getChildren( $tid );
		var_dump($ticket);
		exit;

	}
}

?>
