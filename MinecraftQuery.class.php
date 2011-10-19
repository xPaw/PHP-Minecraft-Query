<?php
class MinecraftQueryException extends Exception
{
	// Exception thrown by MinecraftQuery class
}

class MinecraftQuery
{
	private $Socket;
	private $Challenge;
	private $Info;
	
	public function Connect( $Ip, $Port = 25565, $Timeout = 3 )
	{
		if( $this->Socket = FSockOpen( 'udp://' . $Ip, (int)$Port ) )
		{
			Socket_Set_TimeOut( $this->Socket, $Timeout );
			
			if( !$this->GetChallenge( ) )
			{
				FClose( $this->Socket );
				throw new MinecraftQueryException( "Failed to receive challenge." );
			}
			
			if( !$this->GetStatus( ) )
			{
				FClose( $this->Socket );
				throw new MinecraftQueryException( "Failed to receive status." );
			}
			
			FClose( $this->Socket );
		}
		else
		{
			throw new MinecraftQueryException( "Can't open connection." );
		}
	}
	
	public function GetSimpleInfo( )
	{
		// Minecraft 1.8
		
		FWrite( $this->Socket, "\xFE" );
		$Data = FRead( $this->Socket, 256 );
		
		if( $Data[ 0 ] != "\xFF" )
		{
			return false;
		}
		
		$Data = SubStr( $Data, 3 );
		$Data = iconv( 'UTF-16BE', 'UTF-8', $Data );
		$Data = Explode( "\xA7", $Data );
		
		return Array(
			'HostName'   => SubStr( $Data[ 0 ], 0, -1 ),
			'Players'    => isset( $Data[ 1 ] ) ? IntVal( $Data[ 1 ] ) : 0,
			'MaxPlayers' => isset( $Data[ 2 ] ) ? IntVal( $Data[ 2 ] ) : 0
		);
	}
	
	public function GetInfo( )
	{
		return isset( $this->Info[ 's' ] ) ? $this->Info[ 's' ] : false;
	}
	
	public function GetPlayers( )
	{
		return isset( $this->Info[ 'p' ] ) ? $this->Info[ 'p' ] : false;
	}
	
	private function GetChallenge( )
	{
		$Data = $this->WriteData( "\x09" );
	
		if( !$Data )
		{
			return false;
		}
		
		$Data = Trim( $Data );
		$Data = Chr( $Data >> 24 ) . Chr( $Data >> 16 ) . Chr( $Data >> 8 ) . Chr( $Data >> 0 );
		
		$this->Challenge = $Data;
		
		return true;
	}
	
	private function GetStatus( )
	{
		$Data = $this->WriteData( "\x00", $this->Challenge . "\x01\x02\x03\x04" );
		
		if( !$Data )
		{
			return false;
		}
		
		$Info = Array( );
		
		$this->_CutString( $Data ); // splitnum
		$this->_CutByte( $Data ); // 128?
		$this->_CutByte( $Data ); // 0?
		$this->_CutString( $Data ); // hostname
		$Info[ 's' ][ 'HostName' ] = $this->_CutString( $Data );
		$this->_CutString( $Data ); // gametype
		$Info[ 's' ][ 'GameType' ] = $this->_CutString( $Data );
		$this->_CutString( $Data ); // game_id
		$this->_CutString( $Data ); // MINECRAFT
		$this->_CutString( $Data ); // version
		$Info[ 's' ][ 'Version' ] = $this->_CutString( $Data );
		$this->_CutString( $Data ); // plugins
		$this->_CutString( $Data ); // ""
		$this->_CutString( $Data ); // map
		$Info[ 's' ][ 'Map' ] = $this->_CutString( $Data );
		$this->_CutString( $Data ); // numplayers
		$Info[ 's' ][ 'Players' ] = (int)$this->_CutString( $Data );
		$this->_CutString( $Data ); // maxplayers
		$Info[ 's' ][ 'MaxPlayers' ] = (int)$this->_CutString( $Data );
		$this->_CutString( $Data ); // hostport
		$Info[ 's' ][ 'HostPort' ] = (int)$this->_CutString( $Data );
		$this->_CutString( $Data ); // hostip
		$Info[ 's' ][ 'HostIp' ] = $this->_CutString( $Data );
		
		if( $Info[ 's' ][ 'Players' ] > 0 )
		{
			$this->_CutByte( $Data ); // 0?
			$this->_CutByte( $Data ); // 1?
			$this->_CutString( $Data ); // player_
			$this->_CutByte( $Data ); // 0?
			
			for( $i = 0; $i < $Info[ 's' ][ 'Players' ]; $i++ )
			{
				$Info[ 'p' ][ ] = $this->_CutString( $Data );
			}
			
			$this->_CutByte( $Data ); // 0?
		}
		
		$this->Info = $Info;
		
		return true;
	}
	
	// ==========================================================
	// DATA WORKERS
	private function WriteData( $Command, $Append = "" )
	{
		$Command = "\xFE\xFD" . $Command . "\x01\x02\x03\x04" . $Append;
		$Length  = StrLen( $Command );
		
		if( $Length !== FWrite( $this->Socket, $Command, $Length ) )
		{
			return false;
		}
		
		$Data = FRead( $this->Socket, 1440 );
		
		if( StrLen( $Data ) < 5 || $Data[ 0 ] != $Command[ 0 ] )
		{
			return false;
		}
		
		return SubStr( $Data, 5 );
	}
	
	private function _CutNumber( &$Buffer )
	{
		return Ord( $this->_CutByte( $Buffer ) );
	}
	
	private function _CutByte( &$Buffer, $Length = 1 )
	{
		$String = SubStr( $Buffer, 0, $Length );
		$Buffer = SubStr( $Buffer, $Length );
		
		return $String;
	}
	
	private function _CutString( &$Buffer )
	{
		$Length = StrPos( $Buffer, "\x00" );
		
		if( $Length === FALSE )
		{
			$String = $Buffer;
			$Buffer = "";
		}
		else
		{
			$String = $this->_CutByte( $Buffer, ++$Length );
			$String = SubStr( $String, 0, -1 );
		}
		
		return $String;
	}
}

	echo '<pre>';
	try
	{
		$Query = new MinecraftQuery( );
		$Query->Connect( '127.0.0.1', 25565 );
		
		var_dump( $Query->GetInfo( ) );
		var_dump( $Query->GetPlayers( ) );
	}
	catch( MinecraftQueryException $e )
	{
		echo "FAIL: " . $e->getMessage( );
	}
	echo '</pre>';
?>