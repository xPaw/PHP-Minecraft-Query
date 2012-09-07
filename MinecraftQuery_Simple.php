<?php
	/*
	 * Queries Minecraft server (1.8+)
	 * Returns array on success, false on failure.
	 *
	 * Written by xPaw
	 *
	 * Website: http://xpaw.ru
	 * GitHub: https://github.com/xPaw/PHP-Minecraft-Query
	 */
	
	function QueryMinecraft( $IP, $Port = 25565, $Timeout = 2 )
	{
		$TimerStart = microtime(true);
		
		$Socket = Socket_Create( AF_INET, SOCK_STREAM, SOL_TCP );
		
		Socket_Set_Option( $Socket, SOL_SOCKET, SO_SNDTIMEO, array( 'sec' => (int)$Timeout, 'usec' => 0 ) );
		
		if( $Socket === FALSE || @Socket_Connect( $Socket, $IP, (int)$Port ) === FALSE )
		{
			return FALSE;
		}
		
		Socket_Send( $Socket, "\xFE", 1, 0 );
		$Len = Socket_Recv( $Socket, $Data, 256, 0 );
		Socket_Close( $Socket );
		
		$TimerEnd = microtime(true);
		
		if( $Len < 4 || $Data[ 0 ] != "\xFF" )
		{
			return FALSE;
		}
		
		$Data = SubStr( $Data, 3 );
		$Data = iconv( 'UTF-16BE', 'UTF-8', $Data );
		$Data = Explode( "\xA7", $Data );
		
		return Array(
			'HostName'   => SubStr( $Data[ 0 ], 0, -1 ),
			'Players'    => isset( $Data[ 1 ] ) ? IntVal( $Data[ 1 ] ) : 0,
			'MaxPlayers' => isset( $Data[ 2 ] ) ? IntVal( $Data[ 2 ] ) : 0,
			'Latency'    => ( ($TimerEnd - $TimerStart) * 1000 )
		);
	}
