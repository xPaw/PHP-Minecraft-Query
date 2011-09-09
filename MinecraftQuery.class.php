<?php
	// TODO: Cleanup
	
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
		
		$Status = Socket_Get_Status( $Resource );
		
		if( $Status[ 'unread_bytes' ] )
		{
			$Data .= FRead( $Resource, $Status[ 'unread_bytes' ] );
		}
		
		FClose( $Resource );
		
		if( !$Data )
		{
			throw new Exception( "Server did not answer anything." );
		}
		
		$Data = Explode( "\xA7", $Data );
		
		return Array(
			'HostName'   => SubStr( $Data[ 0 ], 1 ),
			'Players'    => (int)Str_Replace( "\x00", "", $Data[ 1 ] ), // Stupid notch-code fixes
			'MaxPlayers' => (int)Str_Replace( "\x00", "", $Data[ 2 ] )
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