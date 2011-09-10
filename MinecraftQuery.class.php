<?php
	function QueryMinecraft( $IP, $Port = 25565 )
	{
		if( !( $Resource = @FSockOpen( 'tcp://' . GetHostByName( $IP ), (int)$Port ) ) )
		{
			throw new Exception( "Can't open connection." );
		}
		
		Socket_Set_TimeOut( $Resource, 2 );
		
		FWrite( $Resource, "\xFE" );
		
		$Data = FRead( $Resource, 1 );
		
		if( $Data[ 0 ] != "\xFF" )
		{
			FClose( $Resource );
			throw new Exception( "Server answered something else." );
		}
		
		$Data = FRead( $Resource, 2 );
		
		if( StrLen( $Data ) != 2 )
		{
			FClose( $Resource );
			throw new Exception( "WTF?" );
		}
		
		$Data = UnPack( "n", $Data );
		
		$Data = FRead( $Resource, $Data[ 1 ] * 2 );
		FClose( $Resource );
		
		if( !$Data )
		{
			throw new Exception( "Server did not answer anything." );
		}
		
		$Data = IconV( 'UTF-16BE', 'UTF-8', $Data );
		$Data = Explode( "\xA7", $Data );
		
		return Array(
			'HostName'   => Trim( SubStr( $Data[ 0 ], 0, -1 ) ),
			'Players'    => IntVal( isset( $Data[ 1 ] ) ? $Data[ 1 ] : 0 ),
			'MaxPlayers' => IntVal( isset( $Data[ 2 ] ) ? $Data[ 2 ] : 0 )
		);
	}
	
	//////////////////////////////////////////////////////////////////////
	
	echo "<pre>";
	
	try
	{
		print_r( QueryMinecraft( 'localhost' ) );
	}
	catch( Exception $e )
	{
		echo "FAIL: " . $e->getMessage( );
	}
	
	echo "</pre>";
?>