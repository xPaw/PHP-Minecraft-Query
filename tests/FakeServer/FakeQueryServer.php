<?php
declare(strict_types=1);

/**
 * Fake Minecraft UDP Query server (GameSpy4 protocol) for testing.
 *
 * Usage: php FakeQueryServer.php <port> [mode]
 *
 * Modes:
 *   normal           - Full response with plugins and players (default)
 *   vanilla          - No plugins key at all (vanilla server)
 *   empty-plugins    - Plugins key present but empty value
 *   no-players       - No players online
 *
 * Prints "READY" to stdout when listening, serves one full query cycle, then exits.
 */

$port = (int)($argv[1] ?? 0);
$mode = $argv[2] ?? 'normal';

if( $port <= 0 )
{
	fwrite( STDERR, "Usage: php FakeQueryServer.php <port> [mode]\n" );
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

$challengeToken = 12345678;

// Step 1: Receive handshake request
/** @var string $peer */
$peer = '';
$data = stream_socket_recvfrom( $socket, 4096, 0, $peer );

if( $data !== false && strlen( $data ) >= 7 && $data[0] === "\xFE" && $data[1] === "\xFD" && ord( $data[2] ) === 0x09 )
{
	$sessionId = substr( $data, 3, 4 );
	$response = chr( 0x09 ) . $sessionId . (string)$challengeToken . "\x00";
	\assert( \is_string( $peer ) );
	stream_socket_sendto( $socket, $response, 0, $peer );
}

// Step 2: Receive status request
$data = stream_socket_recvfrom( $socket, 4096, 0, $peer );

if( $data !== false && strlen( $data ) >= 11 && $data[0] === "\xFE" && $data[1] === "\xFD" && ord( $data[2] ) === 0x00 )
{
	$sessionId = substr( $data, 3, 4 );

	$response = chr( 0x00 ) . $sessionId;
	$response .= "splitnum\x00\x80\x00";

	[ $kvPairs, $players ] = getResponseData( $mode, $port );

	foreach( $kvPairs as $key => $value )
	{
		$response .= $key . "\x00" . $value . "\x00";
	}

	$response .= "\x00\x01player_\x00\x00";

	if( !empty( $players ) )
	{
		$response .= implode( "\x00", $players );
	}

	$response .= "\x00\x00";

	\assert( \is_string( $peer ) );
	stream_socket_sendto( $socket, $response, 0, $peer );
}

fclose( $socket );

/** @return array{array<string, string>, list<string>} */
function getResponseData( string $mode, int $port ) : array
{
	$baseKv = [
		'hostname' => 'A Fake Query Server',
		'gametype' => 'SMP',
		'game_id' => 'MINECRAFT',
		'version' => '1.21.4',
		'map' => 'world',
		'numplayers' => '3',
		'maxplayers' => '20',
		'hostport' => (string)$port,
		'hostip' => '127.0.0.1',
	];

	$basePlayers = [ 'Player1', 'Player2', 'Player3' ];

	return match( $mode )
	{
		'vanilla' => [
			$baseKv,
			$basePlayers,
		],

		'empty-plugins' => [
			array_merge(
				array_slice( $baseKv, 0, 4 ),
				[ 'plugins' => '' ],
				array_slice( $baseKv, 4 ),
			),
			$basePlayers,
		],

		'no-players' => [
			array_merge(
				array_slice( $baseKv, 0, 4 ),
				[ 'plugins' => 'Paper 1.21.4: EssentialsX; WorldEdit' ],
				[ 'map' => 'world', 'numplayers' => '0', 'maxplayers' => '20', 'hostport' => (string)$port, 'hostip' => '127.0.0.1' ],
			),
			[],
		],

		default => [
			array_merge(
				array_slice( $baseKv, 0, 4 ),
				[ 'plugins' => 'Paper 1.21.4: EssentialsX; WorldEdit' ],
				array_slice( $baseKv, 4 ),
			),
			$basePlayers,
		],
	};
}
