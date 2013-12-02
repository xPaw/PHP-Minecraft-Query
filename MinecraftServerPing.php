<?php
class MinecraftPingException extends Exception
{
	//
}

class MinecraftPing
{
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
	 *
	 * ---------
	 *
	 * This method can be used to get server-icon.png too.
	 * Something like this:
	 *
	 * $Server = new MinecraftPing( 'localhost' );
	 * $Info = $Server->Query();
	 * echo '<img width="64" height="64" src="' . Str_Replace( "\n", "", $Info[ 'favicon' ] ) . '">';
	 *
	 */
	
	private $Socket;
	private $ServerIP;
	private $ServerPort;
	private $Timeout;
	
	public function __construct( $IP, $Port = 25565, $Timeout = 2 )
	{
		$this->ServerIP = $IP;
		$this->ServerPort = (int)$Port;
		$this->Timeout = (int)$Timeout;
		
		$this->Connect( );
	}
	
	public function __destruct( )
	{
		$this->Close( );
	}
	
	public function Close( )
	{
		if( $this->Socket !== null )
		{
			Socket_Close( $this->Socket );
			
			$this->Socket = null;
		}
	}
	
	public function Connect( )
	{
		$this->Socket = Socket_Create( AF_INET, SOCK_STREAM, SOL_TCP );
		
		Socket_Set_Option( $this->Socket, SOL_SOCKET, SO_SNDTIMEO, array( 'sec' => $this->Timeout, 'usec' => 0 ) );
		Socket_Set_Option( $this->Socket, SOL_SOCKET, SO_RCVTIMEO, array( 'sec' => $this->Timeout, 'usec' => 0 ) );
		
		if( $this->Socket === FALSE || @Socket_Connect( $this->Socket, $this->ServerIP, $this->ServerPort ) === FALSE )
		{
			throw new MinecraftPingException( 'Failed to connect or create a socket' );
		}
	}
	
	public function Query( )
	{
		$Length = StrLen( $this->ServerIP );
		$Data = Pack( 'cccca*', HexDec( $Length ), 0, 0x04, $Length, $this->ServerIP ) . Pack( 'nc', $this->ServerPort, 0x01 );
		
		Socket_Send( $this->Socket, $Data, StrLen( $Data ), 0 ); // handshake
		Socket_Send( $this->Socket, "\x01\x00", 2, 0 ); // status ping
		
		$Length = $this->ReadVarInt( ); // full packet length
		
		if( $Length < 10 )
		{
			return FALSE;
		}
		
		Socket_Read( $this->Socket, 1 ); // packet type, in server ping it's 0
		
		$Length = $this->ReadVarInt( ); // string length
		
		$Data = Socket_Read( $this->Socket, $Length, PHP_NORMAL_READ ); // and finally the json string
		
		if( $Data === FALSE )
		{
			throw new MinecraftPingException( 'Server didn\'t return any data' );
		}
		
		$Data = JSON_Decode( $Data, true );
		
		if( JSON_Last_Error( ) !== JSON_ERROR_NONE )
		{
			if( Function_Exists( 'json_last_error_msg' ) )
			{
				throw new MinecraftPingException( JSON_Last_Error_Msg( ) );
			}
			else
			{
				throw new MinecraftPingException( 'JSON parsing failed' );
			}
			
			return FALSE;
		}
		
		return $Data;
	}
	
	public function QueryOldPre17( )
	{
		Socket_Send( $this->Socket, "\xFE\x01", 2, 0 );
		$Len = Socket_Recv( $this->Socket, $Data, 512, 0 );
		
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
	
	private function ReadVarInt( )
	{
		$i = 0;
		$j = 0;
		
		while( true )
		{
			$k = @Socket_Read( $this->Socket, 1 );
			
			if( $k === FALSE )
			{
				return 0;
			}
			
			$k = Ord( $k );
			
			$i |= ( $k & 0x7F ) << $j++ * 7;
			
			if( $j > 5 )
			{
				throw new MinecraftPingException( 'VarInt too big' );
			}
			
			if( ( $k & 0x80 ) != 128 )
			{
				break;
			}
		}
		
		return $i;
	}
}
