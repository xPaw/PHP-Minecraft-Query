<?php
	/*
	 * Queries Minecraft server
	 * Returns array on success, false on failure.
	 *
	 * WARNING: This method was added in snapshot 13w41a (Minecraft 1.7)
	 *
	 * Written by xPaw
	 *
	 * Website: http://xpaw.ru
	 * GitHub: https://github.com/xPaw/PHP-Minecraft-Query
	 */
	
	/*
	 * This method can be used to get server-icon.png too.
	 * Something like this:
	 *
	 * $Info = QueryMinecraft( 'localhost' );
	 * echo '<img src="' . $Info[ 'favicon' ] . '">';
	 *
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
		
		$Data = "\x10\x00\x00\x0A" . Pack( 'a*', $IP ) . Pack( 'n', $Port ) . "\x01";
		
		Socket_Send( $Socket, $Data, StrLen( $Data ), 0 ); // handshake
		Socket_Send( $Socket, "\x01\x00", 2, 0 ); // status ping
		
		$Length = _QueryMinecraft_Read_VarInt( $Socket ); // full packet length
		
		if( $Length < 10 )
		{
			Socket_Close( $Socket );
			
			return FALSE;
		}
		
		Socket_Read( $Socket, 1 ); // packet type, in server ping it's 0
		
		$Length = _QueryMinecraft_Read_VarInt( $Socket ); // string length
		
		$Data = Socket_Read( $Socket, $Length, PHP_NORMAL_READ ); // and finally the json string
		
		Socket_Close( $Socket );
		
		$Data = JSON_Decode( $Data, true );
		
		return JSON_Last_Error( ) === JSON_ERROR_NONE ? $Data : FALSE;
	}
	
	function _QueryMinecraft_Read_VarInt( $Socket )
	{
		$i = 0;
		$j = 0;
		
		while( true )
		{
			$k = Ord( Socket_Read( $Socket, 1 ) );
			
			$i |= ( $k & 0x7F ) << $j++ * 7;
			
			if( $j > 5 )
			{
				throw new RuntimeException( 'VarInt too big' );
			}
			
			if( ( $k & 0x80 ) != 128 )
			{
				break;
			}
		}
		
		return $i;
	}
