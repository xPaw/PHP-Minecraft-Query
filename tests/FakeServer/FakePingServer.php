<?php
declare(strict_types=1);

/**
 * Fake Minecraft TCP Ping server for testing.
 *
 * Usage: php FakePingServer.php <port> [mode]
 *
 * Modes:
 *   modern           - Modern 1.7+ varint-framed JSON response (default)
 *   legacy           - Legacy 1.4-1.6 response (§1 format)
 *   legacy-pre13     - Pre-1.3 legacy response (§ delimited, no version)
 *
 * Prints "READY" to stdout when listening, serves one request, then exits.
 */

$port = (int)($argv[1] ?? 0);
$mode = $argv[2] ?? 'modern';

if( $port <= 0 )
{
	fwrite( STDERR, "Usage: php FakePingServer.php <port> [mode]\n" );
	exit( 1 );
}

$server = @stream_socket_server( "tcp://127.0.0.1:{$port}", $errno, $errstr );

if( $server === false )
{
	fwrite( STDERR, "Failed to create server: {$errno} {$errstr}\n" );
	exit( 1 );
}

echo "READY\n";
flush();

$client = @stream_socket_accept( $server, 5 );

if( $client === false )
{
	fclose( $server );
	exit( 1 );
}

match( $mode )
{
	'legacy' => serveLegacy( $client ),
	'legacy-pre13' => serveLegacyPre13( $client ),
	default => serveModern( $client ),
};

fclose( $client );
fclose( $server );

/** @param resource $client */
function serveModern( $client ) : void
{
	$data = fread( $client, 4096 );

	if( empty( $data ) )
	{
		return;
	}

	$json = (string)json_encode( [
		'version' => [
			'name' => '1.21.4',
			'protocol' => 769,
		],
		'players' => [
			'max' => 100,
			'online' => 5,
			'sample' => [
				[ 'name' => 'xPaw', 'id' => '12345678-1234-1234-1234-123456789012' ],
			],
		],
		'description' => [
			'text' => 'A Fake Minecraft Server',
		],
	] );

	$jsonLength = writeVarInt( strlen( $json ) );
	$packetId = "\x00";
	$payload = $packetId . $jsonLength . $json;
	$packetLength = writeVarInt( strlen( $payload ) );

	fwrite( $client, $packetLength . $payload );
}

/** @param resource $client */
function serveLegacy( $client ) : void
{
	$data = fread( $client, 2 );

	if( empty( $data ) || $data[0] !== "\xFE" )
	{
		return;
	}

	// Format for 1.4+: §1\x00protocol\x00version\x00motd\x00players\x00maxplayers
	$fields = "\xC2\xA7\x31\x00" . implode( "\x00", [ '127', '1.6.4', 'A Legacy Server', '3', '50' ] );
	$utf16 = iconv( 'UTF-8', 'UTF-16BE', $fields );

	if( $utf16 === false )
	{
		return;
	}

	$charCount = (int)( strlen( $utf16 ) / 2 );
	$response = "\xFF" . pack( 'n', $charCount ) . $utf16;
	fwrite( $client, $response );
}

/** @param resource $client */
function serveLegacyPre13( $client ) : void
{
	$data = fread( $client, 2 );

	if( empty( $data ) || $data[0] !== "\xFE" )
	{
		return;
	}

	// Pre-1.3 format: motd§players§maxplayers
	$fields = "An Old Server\xC2\xA7" . "2\xC2\xA7" . "10";
	$utf16 = iconv( 'UTF-8', 'UTF-16BE', $fields );

	if( $utf16 === false )
	{
		return;
	}

	$charCount = (int)( strlen( $utf16 ) / 2 );
	$response = "\xFF" . pack( 'n', $charCount ) . $utf16;
	fwrite( $client, $response );
}

function writeVarInt( int $value ) : string
{
	$result = '';

	do
	{
		$byte = $value & 0x7F;
		$value >>= 7;

		if( $value !== 0 )
		{
			$byte |= 0x80;
		}

		$result .= chr( $byte );
	}
	while( $value !== 0 );

	return $result;
}
