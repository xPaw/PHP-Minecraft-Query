<?php

namespace xPaw;

class MinecraftQuery
{
	/*
	 * Class written by xPaw
	 *
	 * Website: http://xpaw.me
	 * GitHub: https://github.com/xPaw/PHP-Minecraft-Query
	 */

	const STATISTIC = 0x00;
	const HANDSHAKE = 0x09;

	private $Socket;
	private $Players;
	private $Info;

	public function Connect( $Ip, $Port = 25565, $Timeout = 3, $ResolveSRV = true )
	{
		if( !is_int( $Timeout ) || $Timeout < 0 )
		{
			throw new \InvalidArgumentException( 'Timeout must be an integer.' );
		}

		if( $ResolveSRV )
		{
			$this->ResolveSRV( $Ip, $Port );
		}

		$this->Socket = @FSockOpen( 'udp://' . $Ip, (int)$Port, $ErrNo, $ErrStr, $Timeout );

		if( $ErrNo || $this->Socket === false )
		{
			throw new MinecraftQueryException( 'Could not create socket: ' . $ErrStr );
		}

		Stream_Set_Timeout( $this->Socket, $Timeout );
		Stream_Set_Blocking( $this->Socket, true );

		try
		{
			$Challenge = $this->GetChallenge( );

			$this->GetStatus( $Challenge );
		}
		finally
		{
			FClose( $this->Socket );
		}
	}

	public function ConnectBedrock( $Ip, $Port = 19132, $Timeout = 3, $ResolveSRV = true )
	{
		if( !is_int( $Timeout ) || $Timeout < 0 )
		{
			throw new \InvalidArgumentException( 'Timeout must be an integer.' );
		}

		if( $ResolveSRV )
		{
			$this->ResolveSRV( $Ip, $Port );
		}

		$this->Socket = @\fsockopen( 'udp://' . $Ip, (int)$Port, $ErrNo, $ErrStr, $Timeout );

		if( $ErrNo || $this->Socket === false )
		{
			throw new MinecraftQueryException( 'Could not create socket: ' . $ErrStr );
		}

		\stream_set_timeout( $this->Socket, $Timeout );
		\stream_set_blocking( $this->Socket, true );

		try
		{
			$this->GetBedrockStatus();
		}
		finally
		{
			FClose( $this->Socket );
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
		$Data = $this->WriteData( self :: HANDSHAKE );

		if( $Data === false )
		{
			throw new MinecraftQueryException( 'Failed to receive challenge.' );
		}

		return Pack( 'N', $Data );
	}

	private function GetStatus( $Challenge )
	{
		$Data = $this->WriteData( self :: STATISTIC, $Challenge . Pack( 'c*', 0x00, 0x00, 0x00, 0x00 ) );

		if( !$Data )
		{
			throw new MinecraftQueryException( 'Failed to receive status.' );
		}

		$Last = '';
		$Info = Array( );

		$Data    = SubStr( $Data, 11 ); // splitnum + 2 int
		$Data    = Explode( "\x00\x00\x01player_\x00\x00", $Data );

		if( Count( $Data ) !== 2 )
		{
			throw new MinecraftQueryException( 'Failed to parse server\'s response.' );
		}

		$Players = SubStr( $Data[ 1 ], 0, -2 );
		$Data    = Explode( "\x00", $Data[ 0 ] );

		// Array with known keys in order to validate the result
		// It can happen that server sends custom strings containing bad things (who can know!)
		$Keys = Array(
			'hostname'   => 'HostName',
			'gametype'   => 'GameType',
			'version'    => 'Version',
			'plugins'    => 'Plugins',
			'map'        => 'Map',
			'numplayers' => 'Players',
			'maxplayers' => 'MaxPlayers',
			'hostport'   => 'HostPort',
			'hostip'     => 'HostIp',
			'game_id'    => 'GameName'
		);

		foreach( $Data as $Key => $Value )
		{
			if( ~$Key & 1 )
			{
				if( !Array_Key_Exists( $Value, $Keys ) )
				{
					$Last = false;
					continue;
				}

				$Last = $Keys[ $Value ];
				$Info[ $Last ] = '';
			}
			else if( $Last != false )
			{
				$Info[ $Last ] = mb_convert_encoding( $Value, 'UTF-8' );
			}
		}

		// Ints
		$Info[ 'Players' ]    = IntVal( $Info[ 'Players' ] );
		$Info[ 'MaxPlayers' ] = IntVal( $Info[ 'MaxPlayers' ] );
		$Info[ 'HostPort' ]   = IntVal( $Info[ 'HostPort' ] );

		// Parse "plugins", if any
		if( $Info[ 'Plugins' ] )
		{
			$Data = Explode( ": ", $Info[ 'Plugins' ], 2 );

			$Info[ 'RawPlugins' ] = $Info[ 'Plugins' ];
			$Info[ 'Software' ]   = $Data[ 0 ];

			if( Count( $Data ) == 2 )
			{
				$Info[ 'Plugins' ] = Explode( "; ", $Data[ 1 ] );
			}
		}
		else
		{
			$Info[ 'Software' ] = 'Vanilla';
		}

		$this->Info = $Info;

		if( empty( $Players ) )
		{
			$this->Players = null;
		}
		else
		{
			$this->Players = Explode( "\x00", $Players );
		}
	}

	private function GetBedrockStatus( )
	{
		// hardcoded magic https://github.com/facebookarchive/RakNet/blob/1a169895a900c9fc4841c556e16514182b75faf8/Source/RakPeer.cpp#L135
		$OFFLINE_MESSAGE_DATA_ID = \pack( 'c*', 0x00, 0xFF, 0xFF, 0x00, 0xFE, 0xFE, 0xFE, 0xFE, 0xFD, 0xFD, 0xFD, 0xFD, 0x12, 0x34, 0x56, 0x78 );

		$Command = \pack( 'cQ', 0x01, time() ); // DefaultMessageIDTypes::ID_UNCONNECTED_PING + 64bit current time
		$Command .= $OFFLINE_MESSAGE_DATA_ID;
		$Command .= \pack( 'Q', 2 ); // 64bit guid
		$Length  = \strlen( $Command );

		if( $Length !== \fwrite( $this->Socket, $Command, $Length ) )
		{
			throw new MinecraftQueryException( "Failed to write on socket." );
		}

		$Data = \fread( $this->Socket, 4096 );

		if( $Data === false )
		{
			throw new MinecraftQueryException( "Failed to read from socket." );
		}

		if( $Data[ 0 ] !== "\x1C" ) // DefaultMessageIDTypes::ID_UNCONNECTED_PONG
		{
			throw new MinecraftQueryException( "First byte is not ID_UNCONNECTED_PONG." );
		}

		if( \substr( $Data, 17, 16 ) !== $OFFLINE_MESSAGE_DATA_ID )
		{
			throw new MinecraftQueryException( "Magic bytes do not match." );
		}

		// TODO: What are the 2 bytes after the magic?
		$Data = \substr( $Data, 35 );

		// TODO: If server-name contains a ';' it is not escaped, and will break this parsing
		$Data = \explode( ';', $Data );

		$this->Info =
		[
			'GameName'   => $Data[ 0 ],
			'HostName'   => $Data[ 1 ],
			'Protocol'   => $Data[ 2 ],
			'Version'    => $Data[ 3 ],
			'Players'    => $Data[ 4 ],
			'MaxPlayers' => $Data[ 5 ],
			'Unknown2'   => $Data[ 6 ], // TODO: What is this?
			'Map'        => $Data[ 7 ],
			'GameMode'   => $Data[ 8 ],
			'Unknown3'   => $Data[ 9 ], // TODO: What is this?
		];
		$this->Players = null;
	}

	private function WriteData( $Command, $Append = "" )
	{
		$Command = Pack( 'c*', 0xFE, 0xFD, $Command, 0x01, 0x02, 0x03, 0x04 ) . $Append;
		$Length  = StrLen( $Command );

		if( $Length !== FWrite( $this->Socket, $Command, $Length ) )
		{
			throw new MinecraftQueryException( "Failed to write on socket." );
		}

		$Data = FRead( $this->Socket, 4096 );

		if( $Data === false )
		{
			throw new MinecraftQueryException( "Failed to read from socket." );
		}

		if( StrLen( $Data ) < 5 || $Data[ 0 ] != $Command[ 2 ] )
		{
			return false;
		}

		return SubStr( $Data, 5 );
	}

	private function ResolveSRV( &$Address, &$Port )
	{
		if( ip2long( $Address ) !== false )
		{
			return;
		}

		$Record = @dns_get_record( '_minecraft._tcp.' . $Address, DNS_SRV );

		if( empty( $Record ) )
		{
			return;
		}

		if( isset( $Record[ 0 ][ 'target' ] ) )
		{
			$Address = $Record[ 0 ][ 'target' ];
		}
	}
}
