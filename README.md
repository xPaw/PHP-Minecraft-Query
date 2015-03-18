# PHP Minecraft Query

## Description
This class was created to query Minecraft servers.<br>
It works starting from **Minecraft 1.0**

## Instructions
Before using this class, you need to make sure that your server is running GS4 status listener.

Look for those settings in **server.properties**:

> *enable-query=true*<br>
> *query.port=25565*

## RCON
Minecraft implements [Source RCON protocol](https://developer.valvesoftware.com/wiki/Source_RCON_Protocol), so I suggest using [PHP Source Query](https://github.com/xPaw/PHP-Source-Query-Class) library for your RCON needs.

## Example
If the server has query enabled (`enable-query`, and it requires using a different port), then you can use `MinecraftQuery` to retrieve info about a server.
```php
<?php
	require __DIR__ . '/src/MinecraftQuery.php';
	require __DIR__ . '/src/MinecraftQueryException.php';
	
	use xPaw\MinecraftQuery;
	use xPaw\MinecraftQueryException;
	
	$Query = new MinecraftQuery( );
	
	try
	{
		$Query->Connect( 'localhost', 25565 );
		
		print_r( $Query->GetInfo( ) );
		print_r( $Query->GetPlayers( ) );
	}
	catch( MinecraftQueryException $e )
	{
		echo $e->getMessage( );
	}
?>
```

Otherwise you will want to use `MinecraftPing` to get info using the same address as you use to connect to the server.
```php
<?php
	require __DIR__ . '/src/MinecraftPing.php';
	require __DIR__ . '/src/MinecraftPingException.php';
	
	use xPaw\MinecraftPing;
	use xPaw\MinecraftPingException;
	
	try
	{
		$Query = new MinecraftPing( 'localhost', 25565 );
		
		print_r( $Query->Query() );
	}
	catch( MinecraftPingException $e )
	{
		echo $e->getMessage();
	}
	finally
	{
		$Query->Close();
	}
?>
```

If you want to get `ping` info from a server that uses a version older than Minecraft 1.7,
then use function `QueryOldPre17` instead of `Query`.

Please note that this library does not resolve SRV records, you will need to do that yourself.
Take a look at [this issue](https://github.com/xPaw/PHP-Minecraft-Query/issues/34) for an example script.

## License
> *This work is licensed under a Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License.<br>
> To view a copy of this license, visit http://creativecommons.org/licenses/by-nc-sa/3.0/*
