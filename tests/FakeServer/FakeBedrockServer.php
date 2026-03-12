<?php
declare(strict_types=1);

/**
 * Fake Minecraft Bedrock server (RakNet protocol) for testing.
 *
 * Usage: php FakeBedrockServer.php <port> [mode]
 *
 * Modes:
 *   normal           - Full 13-field response with trailing semicolon (default)
 *   minimal          - Only 9 fields, no trailing semicolon (like Hive)
 *   no-trailing      - Full 13 fields, no trailing semicolon (like CubeCraft)
 *   ten-fields       - 10 fields, no trailing semicolon (like CubeCraft/Enchanted)
 *   extra-fields     - More than 13 known fields
 *   unicode          - Hostname with unicode/formatting codes
 *   long-hostname    - Very long hostname string
 *   empty-hostname   - Empty hostname field
 *
 * Prints "READY" to stdout when listening, serves one request, then exits.
 */

$port = (int)($argv[1] ?? 0);
$mode = $argv[2] ?? 'normal';

if( $port <= 0 )
{
	fwrite( STDERR, "Usage: php FakeBedrockServer.php <port> [mode]\n" );
	exit( 1 );
}

$socket = @stream_socket_server( "udp://127.0.0.1:{$port}", $errno, $errstr, STREAM_SERVER_BIND );

if( $socket === false )
{
	fwrite( STDERR, "Failed to create server: {$errno} {$errstr}\n" );
	exit( 1 );
}

echo "READY\n";
flush();

$OFFLINE_MESSAGE_DATA_ID = pack( 'c*', 0x00, 0xFF, 0xFF, 0x00, 0xFE, 0xFE, 0xFE, 0xFE, 0xFD, 0xFD, 0xFD, 0xFD, 0x12, 0x34, 0x56, 0x78 );

// Receive ping request
/** @var string $peer */
$peer = '';
$data = stream_socket_recvfrom( $socket, 4096, 0, $peer );

if( $data !== false && strlen( $data ) >= 33 && $data[0] === "\x01" )
{
	$clientTime = substr( $data, 1, 8 );

	$response = "\x1C"; // ID_UNCONNECTED_PONG
	$response .= $clientTime;
	$response .= pack( 'Q', 42 ); // Server GUID
	$response .= $OFFLINE_MESSAGE_DATA_ID;
	$response .= pack( 'n', 0 ); // 2 unknown bytes

	$response .= buildStatusString( $mode );

	\assert( \is_string( $peer ) );
	stream_socket_sendto( $socket, $response, 0, $peer );
}

fclose( $socket );

function buildStatusString( string $mode ) : string
{
	return match( $mode )
	{
		'minimal' => implode( ';', [
			// 9 fields, no trailing semicolon (like Hive)
			'MCPE',
			'A Minimal Server',
			'121',
			'1.0',
			'500',
			'10000',
			'-193553864714647384',
			'Lobby',
			'Survival',
		] ),

		'ten-fields' => implode( ';', [
			// 10 fields, no trailing semicolon (like CubeCraft/Enchanted)
			'MCPE',
			'A Ten Field Server',
			'748',
			'1.21.50',
			'200',
			'5000',
			'7401571425458450856',
			'Lobby',
			'Survival',
			'1',
		] ),

		'no-trailing' => buildFullFields( trailing: false ),

		'extra-fields' => buildFullFields() . 'unknown1;unknown2;unknown3',

		'unicode' => buildFullFields(
			hostname: "\xC2\xA7c\xC2\xA7lOPBlocks \xE2\x9A\xA1 \xE2\x98\x85",
		),

		'long-hostname' => buildFullFields(
			hostname: str_repeat( 'A Very Long Server Name! ', 100 ),
		),

		'empty-hostname' => buildFullFields(
			hostname: '',
		),

		default => buildFullFields(),
	};
}

/**
 * Build a full 13-field response.
 * GameName;HostName;Protocol;Version;Players;MaxPlayers;ServerId;Map;GameMode;NintendoLimited;IPv4Port;IPv6Port;Extra
 */
function buildFullFields(
	string $hostname = 'A Bedrock Server',
	bool $trailing = true,
) : string
{
	$fields = [
		'MCPE',
		$hostname,
		'748',
		'1.21.50',
		'7',
		'30',
		'13253860892328930865',
		'Bedrock level',
		'Survival',
		'1',
		'19132',
		'19133',
		'0',
	];

	$result = implode( ';', $fields );

	if( $trailing )
	{
		$result .= ';';
	}

	return $result;
}
