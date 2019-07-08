# PHP Minecraft Query [![Packagist](https://img.shields.io/packagist/dt/xpaw/php-minecraft-query.svg)](https://packagist.org/packages/xpaw/php-minecraft-query)

This library can be used to query Minecraft servers for some basic information.

**:warning: Please do not create issues when you are unable to retrieve information from a server, unless you can prove that there is a bug within the library.**

## Differences between Ping and Query

There are two methods of retrieving information about a Minecraft server.

### Ping
Ping protocol was added in Minecraft 1.7 and is used to query the server for minimal amount of information (hostname, motd, icon, and a sample of players). This is easier to use and doesn't require extra setup on server side. It uses TCP protocol on the same port as you would connect to your server.

`MinecraftPing` class contains a method `QueryOldPre17` which can be used to query servers on version 1.6 or older.

### Query
This method uses GameSpy4 protocol, and requires enabling `query` listener in your `server.properties` like this:

> *enable-query=true*<br>
> *query.port=25565*

Query allows to request a full list of servers' plugins and players, however this method is more prone to breaking, so if you don't need all this information, stick to the ping method as it's more reliable.

## RCON
It is possible to send console commands to a Minecraft server remotely using the [Source RCON protocol](https://developer.valvesoftware.com/wiki/Source_RCON_Protocol). Use [PHP Source Query](https://github.com/xPaw/PHP-Source-Query-Class) library for your RCON needs.

## SRV DNS record
This library automatically tries to resolve SRV records. If you do not wish to do so, pass `false` as the fourth param in the constructor (after timeout param).

## Example
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
		if( $Query )
		{
			$Query->Close();
		}
	}
?>
```

If you want to get `ping` info from a server that uses a version older than Minecraft 1.7,
then use function `QueryOldPre17` instead of `Query`.

----

If the server has query enabled (`enable-query`), then you can use `MinecraftQuery` to more retrieve information about a server.
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

For Bedrock servers (MCPE) use `ConnectBedrock` function instead of `Connect`, then `GetInfo` will work.

## License
[MIT](LICENSE)
