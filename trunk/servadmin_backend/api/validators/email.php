<?

class Validator_Email
{
	function __construct( )
	{
		// nothing
	}

	function doValidation( $input )
	{
		if(preg_match('/^[a-z][\w\.-]*[a-z0-9]@[a-z0-9][\w\.-]*[a-z0-9]\.[a-z][a-z\.]*[a-z]$/i', $input))
		{
			return null;
		}
		else
		{
			return "`$input' fails validation ruleset.";
		}
	}
}

?>
