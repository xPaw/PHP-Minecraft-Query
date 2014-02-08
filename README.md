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

## Example (Without Composer)
```php
<?php
	require __DIR__ . '/src/xPaw/MinecraftQuery/MinecraftQuery.php';

	$Query = new xPaw\MinecraftQuery\MinecraftQuery( );

	try
	{
		$Query->Connect( 'localhost', 25565 );

		print_r( $Query->getInfo( ) );
		print_r( $Query->getPlayers( ) );
	}
	catch( xPaw\MinecraftQuery\MinecraftQueryException $e )
	{
		echo $e->getMessage( );
	}
?>
```
## Example (With Composer)
```json
{
    "name": "acme/blog",
    "require": {
        "xpaw/PHP-Minecraft-Query": "dev-master"
    }
}
```
```php
<?php
	require __DIR__ . '/vendor/autoload.php';

	$Query = new xPaw\MinecraftQuery\MinecraftQuery( );

	try
	{
		$Query->Connect( 'localhost', 25565 );

		print_r( $Query->getInfo( ) );
		print_r( $Query->getPlayers( ) );
	}
	catch( xPaw\MinecraftQuery\MinecraftQueryException $e )
	{
		echo $e->getMessage( );
	}
?>
```

## License
> *This work is licensed under a Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License.<br>
> To view a copy of this license, visit http://creativecommons.org/licenses/by-nc-sa/3.0/*
