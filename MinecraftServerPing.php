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
	 * echo '<img width="64" height="64" src="' . Str_Replace( "\n", "", $Info[ 'favicon' ] ) . '">';
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
		
		$Length = StrLen( $IP );
		$Data = Pack( 'cccca*', HexDec( $Length ), 0, 0x04, $Length, $IP ) . Pack( 'nc', $Port, 0x01 );
		
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
		
		$Data = substr(mb_convert_encoding($Data, "UTF-8"), 3);
		
		$Data = JSON_Decode( $Data, true );
		
		return verifyJson(JSON_Last_Error( ));
	}
	
	 function verifyJson($last_error)
	 {
	 	//Could make this an exception
	        switch($last_error)
	        {
	            case JSON_ERROR_DEPTH:
	                echo "The maximum stack depth has been exceeded";
	                break;
	            case JSON_ERROR_STATE_MISMATCH:
	                echo "Invalid or malformed JSON";
	                break;
	            case JSON_ERROR_CTRL_CHAR:
	                echo "Control character error, possibly incorrectly encoded";
	                break;
	            case JSON_ERROR_SYNTAX:
	                echo "Syntax error";
	                break;
	            case JSON_ERROR_UTF8:
	                echo "Malformed UTF-8 characters, possibly incorrectly encoded";
	                break;
	            case JSON_ERROR_RECURSION:
	                echo "One or more recursive references in the value to be encoded";
	                break;
	            case JSON_ERROR_INF_OR_NAN:
	                echo "One or more NAN or INF values in the value to be encoded";
	                break;
	            case JSON_ERROR_UNSUPPORTED_TYPE:
	                echo "A value of a type that cannot be encoded was given";
	                break;
	            case JSON_ERROR_NONE:
	                return true;
	        }
	        return false;
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
