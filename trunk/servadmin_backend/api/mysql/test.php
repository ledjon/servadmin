<?
	require("core.php");

	$mysql = new ServerAdminMysql;

	$dbs = $mysql->listDatabases('test');
	var_dump($dbs);

	$access = $mysql->listAccess('test');
	var_dump($access);

	var_dump($mysql->listUsers('test'));

	var_dump($mysql->createDatabase("test_foobar"));

	var_dump($mysql->listDatabases('test'));

	var_dump($mysql->createUser('test_user1', 'password'));

	var_dump($mysql->listUsers('test'));

	var_dump($mysql->grantAccess('test_user1', 'test_foobar'));

	var_dump($mysql->listAccess('test'));

	var_dump($mysql->revokeAccess('test_user1', 'test_foobar'));

	var_dump($mysql->changePass('test_user1', 'asdf'));

	var_dump($mysql->dropUser('test_user1'));

	var_dump($mysql->dropDatabase("test_foobar"));
?>
