<?php
class SQueryException extends Exception
{
	// Exception thrown by SourceQuery class
}

class SourceQuery
{
	/*
	 * Class written by xPaw
	 *
	 * Website: http://xpaw.ru
	 * GitHub: https://github.com/xPaw
	 */
	
	// Engines
	const GoldSource = 0;
	const Source     = 1;
	
	// Rcon Sending
	const SERVERDATA_EXECCOMMAND    = 2;
	const SERVERDATA_AUTH           = 3;

	// Rcon Receiving
	const SERVERDATA_RESPONSE_VALUE = 0;
	const SERVERDATA_AUTH_RESPONSE  = 2;
	
	private $Socket;
	private $Connected;
	private $RconSocket;
	private $RconPassword;
	private $RconChallenge;
	private $Challenge;
	private $Timeout;
	private $Engine;
	
	public function __destruct( )
	{
		$this->Disconnect( );
	}
	
	public function Connect( $Ip, $Port, $Timeout = 3, $Engine = -1 )
	{
		$this->Disconnect( );
		$this->RconChallenge = 0;
		$this->RconPassword  = 0;
		$this->Challenge     = 0;
		
		if( $Engine == -1 )
		{
			$Engine = self :: GoldSource;
			
			Trigger_Error( 'You should pass $Engine variable to Connect(), defaulting to Goldsource.' );
		}
		
		$this->Engine  = (int)$Engine;
		$this->Timeout = (int)$Timeout;
		$this->Port    = (int)$Port;
		$this->Ip      = $Ip;
		
		if( !( $this->Socket = FSockOpen( 'udp://' . $this->Ip, $this->Port ) ) )
		{
			throw new SQueryException( 'Can\'t connect to the server.' );
		}
		
		Socket_Set_TimeOut( $this->Socket, $this->Timeout );
		
		$this->Connected = true;
		
		// TODO: Source servers dont respond to ping
		// TODO: TF2 currently does not split replies, expect A2S_PLAYER and A2S_RULES to be simply cut off after 1260 bytes
		
		/*if( !$this->Ping( ) )
		{
			$this->Disconnect( );
			
			throw new SQueryException( 'This server is not responding to Source Query Protocol.' );
		}*/
	}
	
	public function Disconnect( )
	{
		$this->Connected = false;
		
		if( $this->Socket )
		{
			FClose( $this->Socket );
			
			$this->Socket = null;
		}
		
		if( $this->RconSocket )
		{
			FClose( $this->RconSocket );
			
			$this->RconSocket = null;
		}
	}
	
	public function Ping( )
	{
		$this->WriteData( 'i' );
		$Type = $this->ReadData( );
		
		return $Type[ 0 ] == 'j';
	}
	
	public function GetInfo( )
	{
		if( !$this->Connected )
		{
			return false;
		}
		
		$this->WriteData( 'TSource Engine Query' );
		$Data = $this->ReadData( );
		
		$Type = self :: _CutByte( $Data, 1 );
		
		// TODO: Check this again and see if we can remove this
		if( $Type == 'm' && $this->Engine == self :: GoldSource )
		{
			$Temp = $this->ReadData( );
			
			if( $Temp[ 0 ] == 'I' )
			{
				$Data = $Temp;
				unset( $Temp );
				
				$Type = self :: _CutByte( $Data, 1 );
			}
			
			// Seriously, don't look at me like this, blame Valve!!
		}
		
		if( $Type == 'm' && $this->Engine == self :: GoldSource ) // Old GoldSrc protocol, HLTV still uses it
		{
			$Server[ 'Address' ]    = self :: _CutString( $Data );
			$Server[ 'HostName' ]   = self :: _CutString( $Data );
			$Server[ 'Map' ]        = self :: _CutString( $Data );
			$Server[ 'ModDir' ]     = self :: _CutString( $Data );
			$Server[ 'ModDesc' ]    = self :: _CutString( $Data );
			$Server[ 'Players' ]    = self :: _CutNumber( $Data );
			$Server[ 'MaxPlayers' ] = self :: _CutNumber( $Data );
			$Server[ 'Protocol' ]   = self :: _CutNumber( $Data );
			$Server[ 'Dedicated' ]  = self :: _CutByte( $Data );
			$Server[ 'Os' ]         = self :: _CutByte( $Data );
			$Server[ 'Password' ]   = self :: _CutNumber( $Data );
			$Server[ 'IsMod' ]      = self :: _CutNumber( $Data );
			
			if( $Server[ 'IsMod' ] ) // TODO: Needs testing
			{
				$Mod[ 'Url' ]        = self :: _CutString( $Data );
				$Mod[ 'Download' ]   = self :: _CutString( $Data );
				self :: _CutByte( $Data ); // NULL byte
				$Mod[ 'Version' ]    = self :: _CutNumber( $Data );
				$Mod[ 'Size' ]       = self :: _CutNumber( $Data );
				$Mod[ 'ServerSide' ] = self :: _CutNumber( $Data );
				$Mod[ 'CustomDLL' ]  = self :: _CutNumber( $Data );
			}
			
			$Server[ 'Secure' ]   = self :: _CutNumber( $Data );
			$Server[ 'Bots' ]     = self :: _CutNumber( $Data );
			
			if( isset( $Mod ) )
			{
				$Server[ 'Mod' ] = $Mod;
			}
			
			return $Server;
		}
		else if( $Type != 'I' )
		{
			return false;
		}
		
		$Server[ 'Protocol' ]   = self :: _CutNumber( $Data );
		$Server[ 'HostName' ]   = self :: _CutString( $Data );
		$Server[ 'Map' ]        = self :: _CutString( $Data );
		$Server[ 'ModDir' ]     = self :: _CutString( $Data );
		$Server[ 'ModDesc' ]    = self :: _CutString( $Data );
		$Server[ 'AppID' ]      = self :: _UnPack( 'S', self :: _CutByte( $Data, 2 ) );
		$Server[ 'Players' ]    = self :: _CutNumber( $Data );
		$Server[ 'MaxPlayers' ] = self :: _CutNumber( $Data );
		$Server[ 'Bots' ]       = self :: _CutNumber( $Data );
		$Server[ 'Dedicated' ]  = self :: _CutByte( $Data );
		$Server[ 'Os' ]         = self :: _CutByte( $Data );
		$Server[ 'Password' ]   = self :: _CutNumber( $Data );
		$Server[ 'Secure' ]     = self :: _CutNumber( $Data );
		
		if( $Server[ 'AppID' ] == 2400 ) // The Ship
		{
			$Server[ 'GameMode' ]     = self :: _CutNumber( $Data );
			$Server[ 'WitnessCount' ] = self :: _CutNumber( $Data );
			$Server[ 'WitnessTime' ]  = self :: _CutNumber( $Data );
		}
		
		$Server[ 'Version' ] = self :: _CutString( $Data );
		
		// EXTRA DATA FLAGS
		$Flags = self :: _CutNumber( $Data );
		
		// The server's game port
		if( $Flags & 0x80 )
		{
			// Some games, such as MW3, have different server ports
			
			$Server[ 'GamePort' ] = self :: _UnPack( 'S', self :: _CutByte( $Data, 2 ) );
		}
		
		// The server's SteamID
		if( $Flags & 0x10 )
		{
			// TODO: long long
			self :: _CutByte( $Data, 8 );
		}
		
		// The spectator port and then the spectator server name
		if( $Flags & 0x40 )
		{
			$Server[ 'SpecPort' ] = self :: _UnPack( 'S', self :: _CutByte( $Data, 2 ) );
			$Server[ 'SpecName' ] = self :: _CutString( $Data );
		}
		
		// The game tag data string for the server
		if( $Flags & 0x20 )
		{
			$Server[ 'GameTags' ] = self :: _CutString( $Data );
			
			if( $Server[ 'AppID' ] == 42690 ) // MW3
			{
				$Data = Explode( '\\', $Server[ 'GameTags' ] );
				$Last = "";
				$Info = Array( );
				
				foreach( $Data as $Key => $Value )
				{
					if( ~$Key & 1 )
					{
						if( Empty( $Value ) )
						{
							$Last = null;
							continue;
						}
						
						$Last = $Value;
						$Info[ $Last ] = "";
					}
					else if( $Last != null )
					{
						$Info[ $Last ] = $Value;
					}
				}
				
				$Server[ 'GameTags' ] = $Info;
			}
		}
		
		// The server's 64-bit GameID
		/*if( $Flags & 0x01 )
		{
			// TODO: long long
			self :: _CutByte( $Data, 8 );
		}*/
		
		return $Server;
	}
	
	public function GetPlayers( )
	{
		if( !$this->Connected || !$this->GetChallenge( ) )
		{
			return false;
		}
		
		$this->WriteData( 'U' . $this->Challenge );
		$Data = $this->ReadData( );
		
		if( self :: _CutByte( $Data, 1 ) != "D" )
		{
			return false;
		}
		
		$Count = self :: _CutNumber( $Data );
		
		if( $Count <= 0 ) // No players
		{
			return false;
		}
		
		for( $i = 0; $i < $Count; $i++ )
		{
			self :: _CutByte( $Data ); // PlayerID, is it just always 0?
			
			$Players[ $i ][ 'Name' ]    = self :: _CutString( $Data );
			$Players[ $i ][ 'Frags' ]   = self :: _UnPack( 'L', self :: _CutByte( $Data, 4 ) );
			$Time                       = (int)self :: _UnPack( 'f', self :: _CutByte( $Data, 4 ) );
			$Players[ $i ][ 'IntTime' ] = $Time;
			$Players[ $i ][ 'Time' ]    = GMDate( ( $Time > 3600 ? "H:i:s" : "i:s" ), $Time );
		}
		
		return $Players;
	}
	
	public function GetRules( )
	{
		if( !$this->Connected || !$this->GetChallenge( ) )
		{
			return false;
		}
		
		$this->WriteData( 'V' . $this->Challenge );
		$Data = $this->ReadData( );
		
		if( self :: _CutByte( $Data, 1 ) != "E" )
		{
			return false;
		}
		
		$Count = self :: _UnPack( 'S', self :: _CutByte( $Data, 2 ) );
		
		if( $Count <= 0 ) // Can this even happen?
		{
			return false;
		}
		
		$Rules = Array( );
		
		for( $i = 0; $i < $Count; $i++ )
		{
			$Rules[ self :: _CutString( $Data ) ] = self :: _CutString( $Data );
		}
		
		return $Rules;
	}
	
	private function GetChallenge( )
	{
		if( $this->Challenge )
		{
			return $this->Challenge;
		}
		
		$this->WriteData( "U\xFF\xFF\xFF" );
		$Data = $this->ReadData( );
		
		if( self :: _CutByte( $Data, 1 ) != "A" )
		{
			return false;
		}
		
		return ( $this->Challenge = $Data );
	}
	
	// ==========================================================
	// RCON
	public function SetRconPassword( $Password )
	{
		$this->RconPassword = $Password;
		
		if( $this->RconChallenge || !$Password )
		{
			return false;
		}
		
		if( $this->Engine == self :: GoldSource )
		{
			$this->WriteData( 'challenge rcon' );
			$Data = $this->ReadData( );
			
			if( $this->_CutByte( $Data, 14 ) != "\xFF\xFF\xFF\xFFchallenge rcon" )
			{
				return false;
			}
			
			$this->RconChallenge = Trim( $Data );
		}
		else if( $this->Engine == self :: Source )
		{
			throw new SQueryException( 'TODO: SetRconPassword' );
			
			if( !$this->RconSocket )
			{
				if( !( $this->RconSocket = FSockOpen( 'tcp://' . $this->Ip, $this->Port ) ) )
				{
					throw new SQueryException( 'Can\'t connect to rcon server.' );
				}
				
				Socket_Set_TimeOut( $this->RconSocket, $this->Timeout );
			}
			
			// TODO: @see https://github.com/xPaw/PHP-Minecraft-Query/blob/master/MinecraftRcon.class.php
		}
		
		return true;
	}
	
	public function Rcon( $Command, $DoWeCareAboutResult = true )
	{
		if( !$this->Connected )
		{
			return false;
		}
		else if( !$this->RconPassword || !$this->RconChallenge )
		{
			throw new SQueryException( "No rcon password is specified." );
		}
		
		$Buffer = "";
		
		if( $this->Engine == self :: GoldSource )
		{
			// TODO: Rewrite this
			
			$this->WriteData( 'rcon ' . $this->RconChallenge . ' "' . $this->RconPassword . '" ' . $Command );
			
			if( !$DoWeCareAboutResult )
			{
				return true;
			}
			
			Socket_Set_TimeOut( $this->Socket, 1 );
			
			while( $Type = FRead( $this->Socket, 5 ) )
			{
				if( Ord( $Type[ 0 ] ) == 254 ) // More than one datagram
				{
					$Data = SubStr( $this->_ReadSplitPackets( 3 ), 4 );
				}
				else
				{
					$Status = Socket_Get_Status( $this->Socket );
					$Data   = FRead( $this->Socket, $Status[ 'unread_bytes' ] );
				}
				
				$Buffer .= RTrim( $Data, "\0" );
			}
			
			Socket_Set_TimeOut( $this->Socket, $this->Timeout );
		}
		else if( $this->Engine == self :: GoldSource )
		{
			throw new SQueryException( 'TODO: Rcon' );
		}
		
		return $Buffer;
	}
	
	// ==========================================================
	// DATA WORKERS
	private function WriteData( $Command )
	{
		$Command = "\xFF\xFF\xFF\xFF" . $Command . "\x00";
		$Length  = StrLen( $Command );
		
		return $Length === FWrite( $this->Socket, $Command, $Length );
	}
	
	private function ReadData( )
	{
		$Data = FRead( $this->Socket, 1 );
		
		switch( Ord( $Data ) )
		{
			case 255: // Just one datagram
				$Status = Socket_Get_Status( $this->Socket );
				$Data  .= FRead( $this->Socket, $Status[ 'unread_bytes' ] );
				
				$Data = SubStr( $Data, 4 );
				
				break;
			
			case 254: // More than one datagram
				$Data = $this->_ReadSplitPackets( 7 );
				
				break;
			
			case 0:
				return false;
		}
		
		/*if( StrLen( $Data ) < 5 )
		{
			return false;
		}*/
		
		if( $Data[ 0 ] == 'l' && SubStr( $Data, 5, 38 ) == "You have been banned from this server." )
		{
			$this->Disconnect( );
			
			throw new SQueryException( "Banned." );
			
			return false;
		}
		
		return $Data;
	}
	
	// Massive credits go to koraktor
	private function _ReadSplitPackets( $BytesToRead )
	{
		FRead( $this->Socket, $BytesToRead );
		
		// TODO: Rework whole system to use proper buffers
		// ReadData( 1400 ) -> Reads 1400 bytes into buffer
		// GetByte -> Get( 1 ) == cuts 1 byte from buffer
		
		$Data = "";
		$Packets = Array( );
		$IsCompressed = false;
		
		if( $this->Engine == self :: GoldSource )
		{
			do
			{
				$RequestID            = self :: _UnPack( 'l', FRead( $this->Socket, 4 ) );
				$PacketCountAndNumber = Ord( FRead( $this->Socket, 1 ) );
				$PacketCount          = $PacketCountAndNumber & 0xF;
				$PacketNumber         = $PacketCountAndNumber >> 4;
				
				$Status = Socket_Get_Status( $this->Socket );
				$Packets[ $PacketNumber ] = FRead( $this->Socket, $Status[ 'unread_bytes' ] );
			}
			while( StrLen( $Data = FRead( $this->Socket, 4 ) ) == 4 && self :: _UnPack( 'l', $Data ) == -2 );
		}
		else if( $this->Engine == self :: Source )
		{
			do
			{
				$RequestID    = self :: _UnPack( 'l', FRead( $this->Socket, 4 ) );
				$IsCompressed = ( $RequestID & 0x80000000 ) != 0;
				$PacketCount  = Ord( FRead( $this->Socket, 1 ) );
				$PacketNumber = Ord( FRead( $this->Socket, 1 ) ) + 1;
				
				if( $IsCompressed )
				{
					$SplitSize      = self :: _UnPack( 'l', FRead( $this->Socket, 4 ) );
					$PacketChecksum = self :: _UnPack( 'V', FRead( $this->Socket, 4 ) );
				}
				else
				{
					$SplitSize = self :: _UnPack( 'v', FRead( $this->Socket, 2 ) );
				}
				
				$Status = Socket_Get_Status( $this->Socket );
				$Packets[ $PacketNumber ] = FRead( $this->Socket, $Status[ 'unread_bytes' ] );
			}
			while( StrLen( $Data = FRead( $this->Socket, 4 ) ) == 4 && self :: _UnPack( 'l', $Data ) == -2 );
		}
		
		foreach( $Packets as $Packet )
		{
			$Data .= $Packet;
		}
		
		if( $IsCompressed )
		{
			$Data = bzdecompress( $Data );
			
			if( crc32( $Data ) != $PacketChecksum )
			{
				throw new SQueryException( 'CRC32 checksum mismatch of uncompressed packet data.' );
			}
		}
		
		return $Data;
	}
	
	protected static function _CutNumber( &$Buffer )
	{
		return Ord( self :: _CutByte( $Buffer ) );
	}
	
	protected static function _CutByte( &$Buffer, $Length = 1 )
	{
		$String = SubStr( $Buffer, 0, $Length );
		$Buffer = SubStr( $Buffer, $Length );
		
		return $String;
	}
	
	protected static function _CutString( &$Buffer )
	{
		$Length = StrPos( $Buffer, "\x00" );
		
		if( $Length === FALSE )
		{
			$String = $Buffer;
			$Buffer = "";
		}
		else
		{
			$String = self :: _CutByte( $Buffer, ++$Length );
			$String = SubStr( $String, 0, -1 );
		}
		
		return $String;
	}
	
	protected static function _UnPack( $Format, $Buffer )
	{
		List( , $Buffer ) = UnPack( $Format, $Buffer );
		
		return $Buffer;
	}
}