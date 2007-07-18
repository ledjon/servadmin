<?

/*
	ServAdmin support system
*/

class SupportSystem
{
	var $p = null;

	function SupportSystem( &$p )
	{
		$this->p = & $p;
	}

	function newTicket( $accountid )
	{
		$this->p->db->Execute("insert into support_ticket (accountid, createdatetime) values (?, now())",
			array( $accountid ) )
			or $this->p->raiseError( $this->db->ErrorMsg( ) );

		return $this->p->db->Insert_ID( );
	}

	function getTickets( $accountid, $status = 'open', $order = ' createdatetime asc ' )
	{
		$tickets = array( ); 

		$res = $this->p->db->Execute(
			sprintf("select *, from_days(to_days(createdatetime)) as createdate
					from support_ticket
					where parentticketid is null 
						and accountid = ? and ticketstatus = ? order by %s", $order),
			array( $accountid, $status )
		) or $this->p->raiseError( $this->p->db->ErrorMsg( ) );

		while($row = $res->FetchNextObj( ))
		{
			$tickets[] = $row;
		}

		return $tickets;
	}

	function getTicket( $id )
	{
		$r = $this->p->db->GetRow("select * from support_ticket where ticketid = ?", array( $id ) );	

		if(is_array($r))
		{
			$r = (object) $r;
		}

		return $r;
	}

	function setTicketItem( $id, $key, $val )
	{
		$this->p->db->Execute("update support_ticket set $key = ? where ticketid = ?",
			array( $val, $id )
		) or $this->p->raiseError( $this->p->db->ErrorMsg( ) );

		return true;
	}

	function getChildren( $tid )
	{
		$res = $this->p->db->Execute("select * from support_ticket where parentticketid = ?", array( $tid ))
			or $this->p->raiseError( $this->p->db->ErrorMsg( ) );

		while($row = $res->FetchNextObj( ))
		{
			$tickets[] = $row;
		}

		return $tickets;
	}
}

?>
