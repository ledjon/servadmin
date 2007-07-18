<?

class Validator_User
{
	function __construct( )
	{
		// nothing
	}

	function doValidation( $user )
	{
		if(preg_match('/^[a-z][a-z0-9\_]+$/i', $user))
		{
			return null;
		}
		else
		{
			return "`$user' fails validation ruleset.";
		}
	}
}

?>
