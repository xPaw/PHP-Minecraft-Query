<?php
	/*
	 * Queries Minecraft server
	 * Returns array on success, false on failure.
	 *
	 * Written by xPaw
	 *
	 * Website: http://xpaw.ru
	 * GitHub: https://github.com/xPaw/PHP-Minecraft-Query
	 */
	
	function QueryMinecraft( $IP, $Port = 25565, $Timeout = 2 )
	{
		$Socket = Socket_Create( AF_INET, SOCK_STREAM, SOL_TCP );
		
		Socket_Set_Option( $Socket, SOL_SOCKET, SO_SNDTIMEO, array( 'sec' => (int)$Timeout, 'usec' => 0 ) );
		Socket_Set_Option( $Socket, SOL_SOCKET, SO_RCVTIMEO, array( 'sec' => (int)$Timeout, 'usec' => 0 ) );
		
		if( $Socket === FALSE || @Socket_Connect( $Socket, $IP, (int)$Port ) === FALSE )
		{
			return FALSE;
		}
		
		Socket_Send( $Socket, "\xFE\x01", 2, 0 );
		$Len = Socket_Recv( $Socket, $Data, 512, 0 );
		Socket_Close( $Socket );
		
		if( $Len < 4 || $Data[ 0 ] !== "\xFF" )
		{
			return FALSE;
		}
		
		$Data = SubStr( $Data, 3 ); // Strip packet header (kick message packet and short length)
		$Data = iconv( 'UTF-16BE', 'UTF-8', $Data );
		
		// Are we dealing with Minecraft 1.4+ server?
		if( $Data[ 1 ] === "\xA7" && $Data[ 2 ] === "\x31" )
		{
			$Data = Explode( "\x00", $Data );
			
			return Array(
				'HostName'   => $Data[ 3 ],
				'Players'    => IntVal( $Data[ 4 ] ),
				'MaxPlayers' => IntVal( $Data[ 5 ] ),
				'Protocol'   => IntVal( $Data[ 1 ] ),
				'Version'    => $Data[ 2 ]
			);
		}
		
		$Data = Explode( "\xA7", $Data );
		
		return Array(
			'HostName'   => SubStr( $Data[ 0 ], 0, -1 ),
			'Players'    => isset( $Data[ 1 ] ) ? IntVal( $Data[ 1 ] ) : 0,
			'MaxPlayers' => isset( $Data[ 2 ] ) ? IntVal( $Data[ 2 ] ) : 0,
			'Protocol'   => 0,
			'Version'    => '1.3'
		);
	}
