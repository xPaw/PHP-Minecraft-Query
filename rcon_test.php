<?php
	define( 'MQ_SERVER_ADDR', 'localhost' );
	define( 'MQ_SERVER_PORT', 25575 );
	define( 'MQ_SERVER_PASS', 'lolrcontest' );
	define( 'MQ_TIMEOUT', 2 );
	
	require __DIR__ . '/MinecraftRcon.class.php';
	
	echo "<pre>";
	
	try
	{
		$Rcon = new MinecraftRcon;
		
		$Rcon->Connect( MQ_SERVER_ADDR, MQ_SERVER_PORT, MQ_SERVER_PASS, MQ_TIMEOUT );
		
		$Data = $Rcon->Command( "say Hello from xPaw's minecraft rcon implementation." );
		
		if( $Data === false )
		{
			throw new MinecraftRconException( "Failed to get command result." );
		}
		else if( StrLen( $Data ) == 0 )
		{
			throw new MinecraftRconException( "Got command result, but it's empty." );
		}
		
		echo HTMLSpecialChars( $Data );
	}
	catch( MinecraftRconException $e )
	{
		echo $e->getMessage( );
	}
	
	$Rcon->Disconnect( );
