<?
	require_once('../api/interface/soap.php');

	$soap = new ServerAdmin_InterfaceSoap;

	/* we're an dns-interface, so pull in that core class */
	$soap->setType('dns');

	/* commands that can be called: */
	$commands = array(
			'getDomains',
			'addDomain',
			'deleteDomain',
			'domainExists',
			'setPrimaryIP',
			'setTTL',
			'setNS',
			'setMX',
			'setDefaultIP'
	);

	$soap->registerServices( $commands );
	$dns = new ServerAdminDNS;
	$dns->doDebug = false;

	/* service the request */
	$soap->main( $dns );

	/* likely need to do the build */
	$dns->build( );

//	print_r( listDomains( ) );
	/*
	*
	*  Interface code starts here
	*
	*/
	function getDomains( ) {
		global $dns;

		$domains = $dns->getDomains( );

//		return $domains;
		return array_keys( $domains );
	}

	function addDomain( $domain, $in, $args = null ) {
		global $soap, $dns;

		$parts = unserialize($in);

		/*
		if(($parts = $soap->parseParts( $in )) == false) {
			return $soap->raiseError( $soap->myError );
		}
		*/

		if(!($domain)) {
			return $soap->raiseError( "Need to pass domain param" );
		}

		try
		{
			$ret = $dns->addDomain( $domain, $parts );
		}
		catch(Exception $ex)
		{
			return $soap->raiseError( $ex->getMessage( ) );
		}

		return true;
	}

	function deleteDomain( $domain ) {
		global $dns, $soap;
		
		try
		{
			$ret = $dns->deleteDomain( $domain );
		}
		catch(Exception $ex)
		{
			return $soap->raiseError( $ex->getMessage( ) );
		}

		return true;
	}

	function domainExists( $domain ) {
		global $dns;

		return $dns->domainExists( $domain ); 
	}

	function setPrimaryIP( $domain, $ipaddr ) {
		global $dns, $soap;

		if(!$domain or !$ipaddr) {
			return $soap->raiseError("Need domain and ipaddr ($domain:$ipaddr)");
		}

		try
		{
			$ret = $dns->setPrimaryIP( $domain, $ipaddr );
		}
		catch(Exception $ex)
		{
			return $soap->raiseError( $ex->getMessage( ) );
		}

		return true;
	}

	function setTTL( $domain, $ttl ) {
		global $dns, $soap;

		$ttl = intval( $ttl );

		if(!$domain or !$ttl) {
			return $soap->raiseError("Need domain and TTL ($domain:$ttl)");
		}

		try
		{
			$ret = $dns->setTTL( $domain, $ttl );
		}
		catch(Exception $ex)
		{
			return $soap->raiseError( $ex->getMessage( ) );
		}

		return true;
	}

	function setNS( ) {
		global $dns, $soap;

		$in = func_get_args( );

		if(!is_array($in)) {
			return $soap->raiseError("Need input array of items");
		}

		if(count($in) < 2) {
			return $soap->raiseError("Need at least two params (domain, ns1[, nsX..])");
		}

		$domain = array_shift( $in );
		$servers = $in;


		try
		{
			$ret = $dns->setNS( $domain, $servers );
		}
		catch(Exception $ex)
		{
			return $soap->raiseError( $ex->getMessage( ) );
		}
		
		return true;
	}

	function setMX( $domain, $in ) {
		global $dns, $soap;

		$parts = unserialize( $in );

		/*
		if(($parts = $soap->parseParts( $in )) == false) {
			return $soap->raiseError( $soap->myError );
		}
		*/

		if(!($domain)) {
			return $soap->raiseError( "Need to pass domain param" );
		}

		try
		{
			$ret = $dns->setMX( $domain, $parts );
		}
		catch(Exception $ex)
		{
			return $soap->raiseError( $ex->getMessage( ) );
		}

		return true;
	}

	function setDefaultIP( $domain, $ipaddr ) {
		global $dns, $soap;

		if(!$domain or !$ipaddr) {
			return $soap->raiseError("Need domain and ipaddr ($domain:$ipaddr)");
		}

		try
		{
			$ret = $dns->setDefaultIP( $domain, ($ipaddr == -1 ? false : $ipaddr) );
		}
		catch(Exception $ex)
		{
			return $soap->raiseError( $ex->getMessage( ) );
		}

		return true;
	}

?>
