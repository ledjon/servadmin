<?

/*
# from a perl thing I wront long ago
use constant DOM_REGEX => '^[a-z0-9\.\-]+\.[\.a-z0-9]+$';
use constant IP_REGEX => '\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}';
use constant EMAIL_REGEX => '^[\w.-]+@([\w.-]|\.)+\w+$';
*/

class Validator_Domain
{
	function __construct( )
	{
		// nothing
	}

	function doValidation( $user )
	{
		if(preg_match('/^[a-z0-9\.\-]+\.[\.a-z0-9]+$/i', $user))
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
