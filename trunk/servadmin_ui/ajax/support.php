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

function welcome( $this )
{
	return $this->getHelpNote('support/welcome');
}

function newticket( $this )
{
	$exec = $this->ajax->data->Get('exec');

	if($exec)
	{
		// save ticket
		if($topic = $this->ajax->data->Get('topic')
			and $content = $this->ajax->data->Get('content'))
		{
			$u = $this->userDetails( );
			$support = new SupportSystem( $this );
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
		$ret = $this->html->form_start(
			array(
				'id'	=> 'frmMain',
				'name'	=> 'frmMain',
				'action'	=> 'newticket'
			)
		) .
		$this->tableHeader("Create New Support Ticket") .
		$this->html->table(
			$this->html->tr(
				$this->html->td(
					'Ticket Subject:'
				) . 
				$this->html->td(
					$this->html->textfield('topic', array('class' => 'input', 'size' => 40))
				)
			) .
			$this->html->tr(
				$this->html->td(
					'Ticket Body:<br>' .
					$this->html->textarea(
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
		$this->tableHeader(
			$this->ajaxSubmitButton('Save Ticket', 'frmMain')
		);

		return $ret;
	}
}

function oldtickets( $this )
{
	/*
	$res = $this->db->Execute("select * from support_ticket where ticketstatus = 'open'")
			or $this->raiseError( $this->db->ErrorMsg( ) );
	*/
	$u = $this->userDetails( );
	$support = new SupportSystem( $this );
	$tickets = $support->getTickets( $u->accountid );

	//var_dump($tickets);

	$tbl = '';
	foreach($tickets as $t)
	{
		$tbl .= $this->html->tr(
			$this->html->td(
				$t->ticketid
			) .
			$this->html->td(
				$t->createdate
			) .
			$this->html->td(
				$this->html->ahref(
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
		$this->html->tr(
			$this->html->td(
				'#',
				array('width' => 2, 'class' => 'topcells')
			) .
			$this->html->td(
				'Date',
				array('width' => '5', 'class' => 'topcells')
			) .
			$this->html->td(
				'Subject',
				array('width' => '370', 'class' => 'topcellsRight')
			) 
		)
		. $tbl;
	}

	// wrap it in table
	$ret = $this->html->table(
		$tbl,
		array('class' => 'maintable')
	);

	return $ret;
}

function viewticket( $this )
{
	$exec = $this->ajax->data->Get('exec');

	$tid = $this->ajax->data->Get('extra');
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

	$u = $this->userDetails( );
	$support = new SupportSystem( $this );

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
