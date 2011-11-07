<?php
class MinecraftQueryException extends Exception
{
	// Exception thrown by MinecraftQuery class
}

class MinecraftQuery
{
	/*
	 * Class written by xPaw
	 *
	 * Website: http://xpaw.ru
	 * GitHub: https://github.com/xPaw/PHP-Minecraft-Query
	 */
	
	private $Socket;
	private $Challenge;
	private $Players;
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
	
	public function GetInfo( )
	{
		return isset( $this->Info ) ? $this->Info : false;
	}
	
	public function GetPlayers( )
	{
		return isset( $this->Players ) ? $this->Players : false;
	}
	
	private function GetChallenge( )
	{
		$Data = $this->WriteData( "\x09" );
	
		if( !$Data )
		{
			return false;
		}
		
		$this->Challenge = Pack( 'N', $Data );
		
		return true;
	}
	
	private function GetStatus( )
	{
		$Data = $this->WriteData( "\x00", $this->Challenge . "\x01\x02\x03\x04" );
		
		if( !$Data )
		{
			return false;
		}
		
		$Last = "";
		$Info = Array( );
		
		$Data    = SubStr( $Data, 11 ); // splitnum + 2 int
		$Data    = Explode( "\x00\x00\x01player_\x00\x00", $Data );
		$Players = SubStr( $Data[ 1 ], 0, -2 );
		$Data    = Explode( "\x00", $Data[ 0 ] );
		
		if( $Data[ 0 ] == "hostname" ) { $Data[ 0 ] = "motd"; } // Temporary fix
		
		ForEach( $Data as $Key => $Value )
		{
			if( ~$Key & 1 )
			{
				$Last = $Value;
				$Info[ $Value ] = "";
			}
			else
			{
				$Info[ $Last ] = $Value;
			}
		}
		
		// Ints
		$Info[ 'numplayers' ] = IntVal( $Info[ 'numplayers' ] );
		$Info[ 'maxplayers' ] = IntVal( $Info[ 'maxplayers' ] );
		$Info[ 'hostport' ]   = IntVal( $Info[ 'hostport' ] );
		
		// Parse "plugins", if any
		if( $Info[ 'plugins' ] )
		{
			$Data = Explode( ": ", $Info[ 'plugins' ], 2 );
			
			$Info[ 'raw_plugins' ] = $Info[ 'plugins' ];
			$Info[ 'software' ]    = $Data[ 0 ];
			
			if( Count( $Data ) == 2 )
			{
				$Info[ 'plugins' ] = Explode( "; ", $Data[ 1 ] );
			}
		}
		else
		{
			$Info[ 'software' ] = 'Vanilla';
		}
		
		$this->Info = $Info;
		
		if( $Players )
		{
			$this->Players = Explode( "\x00", $Players );
		}
		
		return true;
	}
	
	// ==========================================================
	
	private function WriteData( $Command, $Append = "" )
	{
		$Signal  = $Command[ 0 ];
		$Command = "\xFE\xFD" . $Command . "\x01\x02\x03\x04" . $Append;
		$Length  = StrLen( $Command );
		
		if( $Length !== FWrite( $this->Socket, $Command, $Length ) )
		{
			return false;
		}
		
		$Data = FRead( $this->Socket, 1440 );
		
		if( StrLen( $Data ) < 5 || $Data[ 0 ] != $Signal )
		{
			return false;
		}
		
		return SubStr( $Data, 5 );
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