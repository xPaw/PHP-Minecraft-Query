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

	/** @var ?resource $Socket */
	private $Socket;
	private ?array $Players = null;
	private ?array $Info = null;

	public function Connect( string $Ip, int $Port = 25565, float $Timeout = 3, bool $ResolveSRV = true ) : void
	{
		if( $Timeout < 0 )
		{
			throw new \InvalidArgumentException( 'Timeout must be a positive integer.' );
		}

		if( $ResolveSRV )
		{
			$this->ResolveSRV( $Ip, $Port );
		}

		$Socket = @\fsockopen( 'udp://' . $Ip, $Port, $ErrNo, $ErrStr, $Timeout );

		if( $ErrNo || $Socket === false )
		{
			throw new MinecraftQueryException( 'Could not create socket: ' . $ErrStr );
		}

		$this->Socket = $Socket;

		\stream_set_timeout( $this->Socket, (int)$Timeout );
		\stream_set_blocking( $this->Socket, true );

		try
		{
			$Challenge = $this->GetChallenge( );

			$this->GetStatus( $Challenge );
		}
		finally
		{
			\fclose( $Socket );
		}
	}

	public function ConnectBedrock( string $Ip, int $Port = 19132, float $Timeout = 3, bool $ResolveSRV = true ) : void
	{
		if( $Timeout < 0 )
		{
			throw new \InvalidArgumentException( 'Timeout must be an integer.' );
		}

		if( $ResolveSRV )
		{
			$this->ResolveSRV( $Ip, $Port );
		}

		$Socket = @\fsockopen( 'udp://' . $Ip, $Port, $ErrNo, $ErrStr, $Timeout );

		if( $ErrNo || $Socket === false )
		{
			throw new MinecraftQueryException( 'Could not create socket: ' . $ErrStr );
		}

		$this->Socket = $Socket;

		\stream_set_timeout( $this->Socket, (int)$Timeout );
		\stream_set_blocking( $this->Socket, true );

		try
		{
			$this->GetBedrockStatus();
		}
		finally
		{
			\fclose( $Socket );
		}
	}

	/** @return array|false */
	public function GetInfo( ) : array|bool
	{
		return isset( $this->Info ) ? $this->Info : false;
	}

	/** @return array|false */
	public function GetPlayers( ) : array|bool
	{
		return isset( $this->Players ) ? $this->Players : false;
	}

	private function GetChallenge( ) : string
	{
		$Data = $this->WriteData( self::HANDSHAKE );

		if( $Data === false )
		{
			throw new MinecraftQueryException( 'Failed to receive challenge.' );
		}

		return \pack( 'N', $Data );
	}

	private function GetStatus( string $Challenge ) : void
	{
		$Data = $this->WriteData( self::STATISTIC, $Challenge . \pack( 'c*', 0x00, 0x00, 0x00, 0x00 ) );

		if( !$Data )
		{
			throw new MinecraftQueryException( 'Failed to receive status.' );
		}

		$Last = '';
		$Info = Array( );

		$Data    = \substr( $Data, 11 ); // splitnum + 2 int
		$Data    = \explode( "\x00\x00\x01player_\x00\x00", $Data );

		if( \count( $Data ) !== 2 )
		{
			throw new MinecraftQueryException( 'Failed to parse server\'s response.' );
		}

		$Players = \substr( $Data[ 1 ], 0, -2 );
		$Data    = \explode( "\x00", $Data[ 0 ] );

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
				if( !isset( $Keys[ $Value ] ) )
				{
					$Last = false;
					continue;
				}

				$Last = $Keys[ $Value ];
				$Info[ $Last ] = '';
			}
			else if( $Last != false )
			{
				$Info[ $Last ] = \mb_convert_encoding( $Value, 'UTF-8', 'ISO-8859-1' );
			}
		}

		// Ints
		$Info[ 'Players' ]    = (int)( $Info[ 'Players' ] ?? 0 );
		$Info[ 'MaxPlayers' ] = (int)( $Info[ 'MaxPlayers' ] ?? 0 );
		$Info[ 'HostPort' ]   = (int)( $Info[ 'HostPort' ] ?? 0 );

		// Parse "plugins", if any
		if( isset( $Info[ 'Plugins' ] ) )
		{
			$Data = \explode( ": ", $Info[ 'Plugins' ], 2 );

			$Info[ 'RawPlugins' ] = $Info[ 'Plugins' ];
			$Info[ 'Software' ]   = $Data[ 0 ];

			if( \count( $Data ) == 2 )
			{
				$Info[ 'Plugins' ] = \explode( "; ", $Data[ 1 ] );
			}
			else
			{
				$Info[ 'Plugins' ] = '';
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
			$this->Players = \explode( "\x00", $Players );
		}
	}

	private function GetBedrockStatus( ) : void
	{
		if( $this->Socket === null )
		{
			throw new MinecraftQueryException( 'Socket is not open.' );
		}

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

		if( empty( $Data ) )
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
			'GameName'   => $Data[ 0 ] ?? null,
			'HostName'   => $Data[ 1 ] ?? null,
			'Protocol'   => $Data[ 2 ] ?? null,
			'Version'    => $Data[ 3 ] ?? null,
			'Players'    => isset( $Data[ 4 ] ) ? (int)$Data[ 4 ] : 0,
			'MaxPlayers' => isset( $Data[ 5 ] ) ? (int)$Data[ 5 ] : 0,
			'ServerId'   => $Data[ 6 ] ?? null,
			'Map'        => $Data[ 7 ] ?? null,
			'GameMode'   => $Data[ 8 ] ?? null,
			'NintendoLimited' => $Data[ 9 ] ?? null,
			'IPv4Port'   => isset( $Data[ 10 ] ) ? (int)$Data[ 10 ] : 0,
			'IPv6Port'   => isset( $Data[ 11 ] ) ? (int)$Data[ 11 ] : 0,
			'Extra'      => $Data[ 12 ] ?? null, // What is this?
		];
		$this->Players = null;
	}

	/** @return string|false */
	private function WriteData( int $Command, string $Append = "" ) : string|bool
	{
		if( $this->Socket === null )
		{
			throw new MinecraftQueryException( 'Socket is not open.' );
		}

		$Command = \pack( 'c*', 0xFE, 0xFD, $Command, 0x01, 0x02, 0x03, 0x04 ) . $Append;
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

		if( \strlen( $Data ) < 5 || $Data[ 0 ] != $Command[ 2 ] )
		{
			return false;
		}

		return \substr( $Data, 5 );
	}

	private function ResolveSRV( string &$Address, int &$Port ) : void
	{
		if( \ip2long( $Address ) !== false )
		{
			return;
		}

		$Record = @\dns_get_record( '_minecraft._tcp.' . $Address, DNS_SRV );

		if( empty( $Record ) )
		{
			return;
		}

		if( isset( $Record[ 0 ][ 'target' ] ) )
		{
			$Address = $Record[ 0 ][ 'target' ];
		}

		if( isset( $Record[ 0 ][ 'port' ] ) )
		{
			$Port = (int)$Record[ 0 ][ 'port' ];
		}
	}
}
