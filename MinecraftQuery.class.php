<?php
	function QueryMinecraft( $IP, $Port = 25565 )
	{
		$Socket = Socket_Create( AF_INET, SOCK_STREAM, SOL_TCP );
		
		if( $Socket === FALSE || @Socket_Connect( $Socket, $IP, (int)$Port ) === FALSE )
		{
			return FALSE;
		}
		
		Socket_Send( $Socket, "\xFE", 1, 0 );
		
		if( ( $Length = Socket_Recv( $Socket, $Data, 100, 0 ) ) < 4 )
		{
			Socket_Close( $Socket );
			return FALSE;
		}
		
		Socket_Close( $Socket );
		
		if( $Data[ 0 ] != "\xFF" )
		{
			return FALSE;
		}
		
		$Data = SubStr( $Data, 3 );
		$Data = IconV( 'UTF-16BE', 'UTF-8', $Data );
		$Data = Explode( "\xA7", $Data );
		
		return Array(
			'HostName'   => Trim( SubStr( $Data[ 0 ], 0, -1 ) ),
			'Players'    => IntVal( isset( $Data[ 1 ] ) ? $Data[ 1 ] : 0 ),
			'MaxPlayers' => IntVal( isset( $Data[ 2 ] ) ? $Data[ 2 ] : 0 )
		);
	}
	
	// Usage
	echo '<pre>';
	var_dump( QueryMinecraft( 'c.nerd.nu' ) );
	echo '</pre>';
?>