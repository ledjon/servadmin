<?

/*
	$Id: ui.php,v 1.9 2006/01/24 19:22:02 ledjon Exp $
	
	User interface class
	by Jon Coulter
	10/28/2005
*/

if(!defined('SA_MAIN'))
{
	die("Cannot call this file directly");
}

// ADODB library
require_once("adodb.inc.php");
require_once(SA_DIR . '/lib/html/class.LedHTML.php');
require_once(SA_DIR . '/lib/template/class.LedTemplate.php');

$config = array(
	'db'	=> array(
		'user'	=> 'servadmin',
		'pass'	=> 'servadmin',
		'db'	=> 'servadmin',
		'host'	=> 'localhost'
	),
	'admin'	=> array( 'ledjon', 'root', 'brandon' )
);

class ServAdminUI
{
	var $config = array( );
	var $db = null;

	var $menu_opts = array(
		'normal_top' =>
			array(
				'start'	=> 'ServAdmin',
				'account'	=> 'Account',
				'email'		=> 'Email',
				'database'	=> 'SQL Databases',
				'stats'		=> 'Statistics & Logs',
				'support'	=> 'Support'
			),
		'normal_sets'	=>
			array(
				'start'	=> array(
					'welcome'	=> 'Welcome Page'
				),
				'account'	=> array(
					'welcome'	=> 'Welcome',
					'passwd'	=> 'Change Password',
					'subdomains'	=> 'Mange Subdomains',
					'crontab'	=> 'Manage Cron Jobs',
					'contact'	=> 'Change Contact Details',
					'backup'	=> 'Backup Site'
				),
				'email'	=> array(
					'welcome'	=> 'Welcome',
					'modusers'	=> 'Add / Delete Email Accounts',
					'defaultaddress'	=> 'Default Address',
					'forwards'	=> 'Email Forwards',
					'mx'		=> 'Modify MX Settings'
				),
				'database'	=> array(
					'welcome'	=> 'Welcome',
					'myusers'	=> 'MySQL Users',
					'mydatabases'	=> 'MySQL Databases'
				),
				'stats'	=> array(
	
				),
				'support'	=> array(
					'welcome'	=> 'Welcome',
					'newticket'	=> 'New Support Ticket',
					'oldtickets'	=> 'Existing Support Tickets'
				)
			),
		'admin_top'	=>
			array(
				'admin_start'	=> 'ServAdmin',
				'admin_sites'	=> 'Site Administration',
				'admin_support'	=> 'Support System'
			),
		'admin_sets'	=>
			array(
				'admin_start'	=> array(
					'welcome'	=> 'Welcome Page',
					'newserver'	=> 'Add New Server to Cluster',
					'modservers'	=> 'Modify Servers in Cluster'
				),
				'admin_sites'	=> array(
					'welcome'	=> 'Welcome',
					'newsite'	=> 'Add New Site',
					'modsites'	=> 'Modfy Existing Sites'
				),
				// shell, there are no options here
				'autologin'	=> array( )
			)
	);

	function ServAdminUI( )
	{
		global $config;

		$this->config = $config;

		// database
		$dsn = sprintf("mysql://%s:%s@%s/%s",
				$config['db']['user'],
				$config['db']['pass'],
				$config['db']['host'],
				$config['db']['db']
			);

		$this->db = NewADOConnection($dsn);

		if(! $this->db )
		{
			$this->raiseError("Unable to connect to database.");
		}

		// html
		$this->html = new LedHTML(
					array(
						'border' => 0
					)
				);
		$this->param = (object) $this->html->_param;

		// template
		$this->template = new LedTemplate;
		$this->template->Set('first_action1', '');
		$this->template->Set('first_action2', '');


		// sessions
		$this->session = &$_SESSION;

		$this->makeMenus( );
	}

	// log a message to the database
	// (or whatever device)
	function log( $msg )
	{
		// TODO	
	}

	function makeMenus( )
	{
		$action = $this->getAction( );

		if($action == 'login'
			|| $action == 'logout'
			|| !$this->isLoggedIn( )
		)
		{
			$this->template->Set('top_menu', '');
			$this->template->Set('left_menu', '');

			return;
		}

		/*
		$top_items = array(
			'start'	=> 'ServAdmin',
			'account'	=> 'Account',
			'email'		=> 'Email',
			'database'	=> 'SQL Databases',
			'stats'		=> 'Statistics & Logs',
			'support'	=> 'Support'
		);
		*/
		$top_items = ($this->isAdmin( ) ? $this->menu_opts['admin_top'] : $this->menu_opts['normal_top']);
		$top_menu = '';

		foreach($top_items as $k => $v)
		{
			if($top_menu)
			{
				$top_menu .= ' | ';
			}

			$top_menu .= $this->html->ahref(
							$this->link(array('a' => $k), true),
							$v
						);
		}

		$left_items = $this->getMenuItems( $action );
		$left_menu = '<b>' . $top_items[$action] . '</b>';
		
		$first_action = false;

		foreach($left_items as $k => $v)
		{
			if(! $first_action )
			{
				$this->template->Set('first_action1', $action);
				$this->template->Set('first_action2', $k);
				$first_action = true;
			}

			if($left_menu)
			{
				$left_menu .= '<br>';
			}

			$left_menu .= ' - ' .
							$this->html->ahref(
								"javascript:void(0)",
								$v,
								array(
									'onclick' => "loadRight('" . $action . "','" . $k ."')"
								)
							);
		}

		$this->template->Set('top_menu', $top_menu);
		$this->template->Set('left_menu', $left_menu);
	}

	function getMenuItems( $action )
	{
		/*
		$items = array(
			'start'	=> array(
				'welcome'	=> 'Welcome Page'
			),
			'account'	=> array(
				'welcome'	=> 'Welcome',
				'passwd'	=> 'Change Password',
				'subdomains'	=> 'Mange Subdomains',
				'contact'	=> 'Change Contact Email',
				'backup'	=> 'Backup Site'
			),
			'email'	=> array(
				'welcome'	=> 'Welcome',
				'modusers'	=> 'Add / Delete Email Accounts',
				'defaultaddress'	=> 'Default Address',
				'forwards'	=> 'Email Forwards',
				'mx'		=> 'Modify MX Settings'
			),
			'database'	=> array(
				'welcome'	=> 'Welcome',
				'myusers'	=> 'MySQL Users',
				'mydatabases'	=> 'MySQL Databases'
			),
			'stats'	=> array(

			),
			'support'	=> array(

			)
		);
		*/
		$items = ($this->isAdmin( ) ? $this->menu_opts['admin_sets'] : $this->menu_opts['normal_sets']);
		
		if(!isset($items[$action]))
		{
			$this->raiseError("Unable to get left-menu items for action ($action)");
		}

		return $items[$action];
	}

	function handlePageRequest( )
	{
		if(! $this->isLoggedIn( ) )
		{
			$this->module("page/login");
		}
		else
		{
			$this->module("page/" . $this->getAction( ));
		}
	}

	function handleAJAXRequest( )
	{
		if(! $this->isLoggedIn( ) )
		{
			return $this->raiseError("Must be logged in.");
		}
		else
		{
			require_once(SA_DIR . '/lib/ajax/class.ajax.php');

			$this->ajax = new AJAX_Handler( );
			$this->ajax->response = array( );
			$this->ajax->data = $this->ajax->GetData( );

			$action = $this->getAction( $this->ajax->data->Get('a') );
			
			$this->module("ajax/" . $action);

			$this->ajax->SendResponse( new AJAX_Data( $this->ajax->response ) );
		}
	}

	function getAction( $k = null )
	{
		if(!isset($k))
		{
			$k = $this->param->a;
		}

		$action = preg_replace('/[^a-z_]*/i', strtolower($k), '');
			
		if(! $action )
		{
			$action = ($this->isAdmin( ) ? 'admin_start' : 'start');
		}

		return $action;
	}

	function redirect( $url )
	{
		header("Location: " . $url);

		exit;
	}

	function isLoggedIn( )
	{
		// this is called every request
		if($this->session['is_admin'])
		{
			define('SA_IS_ADMIN', 1);
		}
		else
		{
			define('SA_IS_ADMIN', 0);
		}

		return ($this->session['uid'] ? true : false);
	}

	function isAdmin( )
	{
		return ($this->session['is_admin'] ? true : false);
	}

	function isUserAdmin( $user )
	{
		if(in_array($user, $this->config['admin']))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function loginUser( $u, $p )
	{
		$sql = "select accountid, username from account where username = ? and password = ?";

		$ret = $this->db->Execute($sql, array( $u, md5($p) ))
				or $this->raiseError( $this->db->ErrorMsg( ) );

		if($row = $ret->FetchNextObj( ))
		{
			$this->setSession('uid', $row->accountid);

			if(in_array($row->username, $this->config['admin']))
			{
				$this->setSession('is_admin', 1);
			}

			return true;
		}
		
		return false;
	}

	function logoutUser( )
	{
		// this removes their uid session variable
		//$this->getSessionOnce('uid');
		session_destroy( );
	}

	function userDetails( $uid = null )
	{
		if(!isset($uid))
		{
			$uid = $this->session['uid'];
		}

		$ret = $this->db->Execute("
				select a.*, s.servname, s.serverid
				from account a join account_server sa using (accountid)
					join server s using (serverid)
				where a.accountid = ?",
					array( $uid )
				) or $this->raiseError( $this->db->ErrorMsg( ) );

		return $ret->FetchNextObj( );
	}

	function setUserDetail( $key, $val, $uid = null )
	{
		if(!isset($uid))
		{
			$uid = $this->session['uid'];
		}

		$this->db->Execute("update account set $key = ? where accountid = ?",
					array( $val, $uid )
			) or $this->raiseError( $this->db->ErrorMsg( ) );

		return true;
	}

	function addAccount( $args )
	{
		$sql = "insert into account
					(username, password, ownername, email,
						domain, acctstatus, createdatetime)
				values
					(?, ?, ?, ?, ?, '', now())";

		$this->db->Execute( $sql,
				array( $args['username'],
						md5($args['password']),
						$args['ownername'],
						$args['email'],
						$args['domain']
					)
			) or $this->raiseError( $this->db->ErrorMsg( ) );

		$uid = $this->db->Insert_ID( ) or $this->raiseError("Unable to get insert_id for this account");

		$ins = "insert into account_server values (?, ?)";

		$this->db->Execute( $ins, array( $uid, $args['servid'] ) )
			or $this->raiseError( $this->db->ErrorMsg( ) );

		return $uid;
	}

	function delAccount( $uid )
	{
		$this->db->Execute("delete from account where accountid = ?", array( $uid ))
			or $this->raiseError( $this->db->ErrorMsg( ) );

		return true;
	}

	function module( $m )
	{
		$file = $m . '.php';

		if(file_exists($file))
		{
			include($file);
		}
		else
		{
			return $this->raiseError("Unable to load module: ($m)");
		}

		return true;
	}

	function link( $args = null, $reset = false )
	{
		$orig = $_GET;

		if(!is_array($args))
		{
			$args = array( );
		}

		if($reset)
		{
			$orig = array( );
		}

		$final = array_merge($orig, $args);

		$page = $_SERVER['PHP_SELF'] . '?';

		foreach($final as $k => $v)
		{
			$page .= $k . '=' . urlencode($v) . '&';	
		}

		return $page;
	}

	// set the session error for the next refresh
	function setError( $msg )
	{
		$this->session['error'] = $msg;
	}

	function setSession( $k, $v )
	{
		$this->session[$k] = $v;
	}

	function getSessionOnce( $k )
	{
		$ret = $this->session[$k];
		unset($this->session[$k]);

		return $ret;
	}

	function raiseError( $msg )
	{
		die($msg);
	}

	function getInterfaceLocation( $servid )
	{
		if($servid)
		{
			$sql = "select servurl, servkey from server where serverid = ?";
			$key = $servid;
		}
		else
		{
			$sql ="select servurl, servkey
					from server s join account_server a
						on (s.serverid = a.serverid)
				where a.accountid = ?";
			$key = $this->session['uid'];
		}
			
		$res = $this->db->Execute( $sql, array( $key ) )
				or $this->raiseError($this->db->ErrorMsg( ));

		if($row = $res->FetchNextObj( ))
		{
			return $row->servurl . '?_k=' . $row->servkey;
		}
		else
		{
			return $this->raiseError("Unable to get servurl");
		}
	}

	function getInterface( $type, $servid = null )
	{
		require_once(SA_DIR . '/lib/soap/interface.php');

		return new ServAdminInterface( $this->getInterfaceLocation( $servid ), $type );
	}

	function getHelpNote( $note )
	{
		$file = SA_DIR . '/notes/' . $note . '.txt';

		if(file_exists($file)
			&& is_readable($file))
		{
			$data = fread(fopen($file, 'r'), filesize($file));

			$ret = $this->html->table(
				$this->html->tr(
					$this->html->td(
						'<b>Notes:</b><br>' .
						$data
					)
				)
				,
				array('class' => 'helptable', 'width' => '70%')
			);

			return $ret;
		}
		else
		{
			return "Unable to read help note: $note";
		}
	}

	function ajaxSubmitButton( $value, $frm, $validate = null )
	{
		$onclick = "submitForm('" . $frm . "')";

		if($validate)
		{
			$onclick = "if($validate) { $onclick }";
		}
		
		return $this->html->button($value,
				array(
					'name'	=> 'cmdSubmit',
					'id'	=> 'cmdSubmit',
					'class' => 'input',
					'onclick' => $onclick 
				)
			);
	}

	function tableHeader( $msg, $args = null )
	{
		if(!is_array($args))
			$args = array( );

		$args = array_merge(array('class' => 'maintable'), $args);

		return $this->html->table(
			$this->html->tr(
				$this->html->td(
					'<b>' . $msg . '</b>',
					array('align' => 'center')
				)
			),
			$args
		);
	}

	// run a preg_match on a set of keys and return
	// the ones that match
	function matchKeys( $keys, $match )
	{
		$ret = array( );
		foreach($keys as $k)
		{
			if(preg_match($match, $k, $m))
			{
				$ret[] = $m[1];	
			}
		}

		return $ret;
	}

	function usernameExists( $username )
	{
		$res = $this->db->Execute("select count(*) as total from account where username = ?",
				array( $username )
			) or $this->raiseError( $this->db->ErrorMsg( ) );
		
		$row = $res->FetchNextObj( );

		return ($row->total > 0 ? true : false);
	}

	// generate a unique (to the system) username based
	// on an input domain name
	function genUsername( $domain )
	{
		// size to aim for (max size)
		$max = 8;

		$salt = create_function('$chars, $start, $stop',
			'return substr($chars, $start, $stop);'
		);

		// no numbers to start with
		while(strlen($domain) and preg_match('/^\d/', $domain))
		{
			$domain = substr($domain, 1);
		}

		$domain = str_pad(str_replace(array('.', '-'), '', $domain), 3, 'x');

		$i = $t = 0;
		while(true)
		{
			$z = '';
			if($i == $max)
			{
				$t = 100;
				$i = 0;
			}

			if(++$t >= 100)
			{
				$i = 0;
				$z1 = $z;
				while(true)
				{
					if($i >= 100)
					{
						return $this->raiseError("Unable to generate unique username");
					}

					$z = $z1 . ++$i;

					if($this->usernameExists( $z ))
					{
						continue;
					}

					return $z;
				}
			}

			$z = $salt($domain, $i++, $max);
			//$z = str_pad($z, $max, substr($z, 0, 1));

			if($this->usernameExists( $z ))
			{
				continue;
			}

			return $z;
		}
	}

	function getServerList( )
	{
		$ret = array( );

		$res = $this->db->Execute("select serverid, servname from server order by servname")
			or $this->raiseError( $this->db->ErrorMsg( ) );

		while($row = $res->FetchNextObj( ))
		{
			$ret[$row->serverid] = $row->servname;
		}

		return $ret;
	}

	function getWelcomeMessage( $uid, $args = null )
	{
		$sql = "select a.*, s.*
				from account a join account_server sa on (a.accountid = sa.accountid)
					join server s on (s.serverid = sa.serverid)
				where a.accountid = ?";

		$res = $this->db->Execute( $sql, array( $uid ) )
			or $this->raiseError( $this->db->ErrorMsg( ) );

		$row = $res->FetchNextObj( );
		$row->tmpurl = sprintf($row->tmpurl, $row->username);

		$subject = 'templates/newsite-subject.txt';
		$body = 'templates/newsite.txt';

		$t = new LedTemplate;
		$t->loadTemplate( 'newsite_subject', $subject );
		$t->loadTemplate( 'newsite_body', $body );

		foreach($row as $k => $v)
		{
			if($k == 'password')
				continue;
			
			$t->Set( $k, $v );
		}

		if(is_array( $args ))
		{
			foreach($args as $k => $v)
			{
				$t->Set( $k, $v );
			}
		}

		$s = $t->Parse('newsite_subject');
		$b = $t->Parse('newsite_body');

		return array( $s, $b );
	}

	function domainExists( $domain )
	{
		$ret = $this->db->GetOne("select count(*) from account where domain = ?", array($domain));		

		return ($ret > 0 ? true : false);
	}

	function wizardLink( $name )
	{
		return $this->html->ahref(
			'javascript:void(0)',
			'Launch Wizard',
			array('onclick' => "launchWizard('" . $name ."')")
		);
	}
}

// AUX Functions
function _check_is_admin( )
{
	if(!defined('SA_IS_ADMIN'))
	{
		die("Invalid admin");
	}
	else
	{
		if(SA_IS_ADMIN)
		{
			return true;
		}
		else
		{
			die("Invalid admin");
		}
	}
}

?>
