<?php
declare(strict_types=1);

namespace xPaw\Tests;

use PHPUnit\Framework\TestCase;
use xPaw\MinecraftQuery;
use xPaw\MinecraftQueryException;

class MinecraftQueryTest extends TestCase
{
	private FakeServerHelper $server;

	private function startQueryServer( string $mode = 'normal' ) : int
	{
		$port = FakeServerHelper::findFreePort();
		$this->server = new FakeServerHelper( __DIR__ . '/FakeServer/FakeQueryServer.php' );
		$this->server->start( $port, [ $mode ] );
		return $port;
	}

	private function startBedrockServer( string $mode = 'normal' ) : int
	{
		$port = FakeServerHelper::findFreePort();
		$this->server = new FakeServerHelper( __DIR__ . '/FakeServer/FakeBedrockServer.php' );
		$this->server->start( $port, [ $mode ] );
		return $port;
	}

	protected function tearDown() : void
	{
		if( isset( $this->server ) )
		{
			$this->server->stop();
		}
	}

	// --- GameSpy4 Query tests ---

	public function testQueryReturnsServerInfo() : void
	{
		$port = $this->startQueryServer();

		$query = new MinecraftQuery();
		$query->Connect( '127.0.0.1', $port, 5, false );

		$info = $query->GetInfo();

		$this->assertIsArray( $info );
		$this->assertSame( 'A Fake Query Server', $info['HostName'] );
		$this->assertSame( 'SMP', $info['GameType'] );
		$this->assertSame( 'MINECRAFT', $info['GameName'] );
		$this->assertSame( '1.21.4', $info['Version'] );
		$this->assertSame( 'world', $info['Map'] );
		$this->assertSame( 3, $info['Players'] );
		$this->assertSame( 20, $info['MaxPlayers'] );
		$this->assertSame( $port, $info['HostPort'] );
		$this->assertSame( '127.0.0.1', $info['HostIp'] );
	}

	public function testQueryReturnsPlayerList() : void
	{
		$port = $this->startQueryServer();

		$query = new MinecraftQuery();
		$query->Connect( '127.0.0.1', $port, 5, false );

		$players = $query->GetPlayers();

		$this->assertIsArray( $players );
		$this->assertCount( 3, $players );
		$this->assertSame( [ 'Player1', 'Player2', 'Player3' ], $players );
	}

	public function testQueryParsesPlugins() : void
	{
		$port = $this->startQueryServer();

		$query = new MinecraftQuery();
		$query->Connect( '127.0.0.1', $port, 5, false );

		$info = $query->GetInfo();

		$this->assertIsArray( $info );
		$this->assertSame( 'Paper 1.21.4: EssentialsX; WorldEdit', $info['RawPlugins'] );
		$this->assertSame( 'Paper 1.21.4', $info['Software'] );
		$this->assertIsArray( $info['Plugins'] );
		$this->assertSame( [ 'EssentialsX', 'WorldEdit' ], $info['Plugins'] );
	}

	public function testQueryVanillaNoPluginsKey() : void
	{
		$port = $this->startQueryServer( 'vanilla' );

		$query = new MinecraftQuery();
		$query->Connect( '127.0.0.1', $port, 5, false );

		$info = $query->GetInfo();

		$this->assertIsArray( $info );
		$this->assertSame( 'Vanilla', $info['Software'] );
		$this->assertArrayNotHasKey( 'Plugins', $info );
		$this->assertArrayNotHasKey( 'RawPlugins', $info );
	}

	public function testQueryEmptyPlugins() : void
	{
		$port = $this->startQueryServer( 'empty-plugins' );

		$query = new MinecraftQuery();
		$query->Connect( '127.0.0.1', $port, 5, false );

		$info = $query->GetInfo();

		$this->assertIsArray( $info );
		$this->assertSame( '', $info['Software'] );
		$this->assertSame( '', $info['RawPlugins'] );
		$this->assertSame( [], $info['Plugins'] );
	}

	public function testQueryNoPlayers() : void
	{
		$port = $this->startQueryServer( 'no-players' );

		$query = new MinecraftQuery();
		$query->Connect( '127.0.0.1', $port, 5, false );

		$info = $query->GetInfo();
		$this->assertIsArray( $info );
		$this->assertSame( 0, $info['Players'] );

		$players = $query->GetPlayers();
		$this->assertFalse( $players );
	}

	public function testQueryConnectionFailureThrowsException() : void
	{
		$this->expectException( MinecraftQueryException::class );

		$query = new MinecraftQuery();
		$port = FakeServerHelper::findFreePort();
		$query->Connect( '127.0.0.1', $port, 1, false );
	}

	public function testNegativeTimeoutThrowsException() : void
	{
		$this->expectException( \InvalidArgumentException::class );

		$query = new MinecraftQuery();
		$query->Connect( '127.0.0.1', 25565, -1, false );
	}

	public function testGetInfoReturnsFalseBeforeConnect() : void
	{
		$query = new MinecraftQuery();
		$this->assertFalse( $query->GetInfo() );
	}

	public function testGetPlayersReturnsFalseBeforeConnect() : void
	{
		$query = new MinecraftQuery();
		$this->assertFalse( $query->GetPlayers() );
	}

	// --- Bedrock tests ---

	public function testBedrockNormal() : void
	{
		$port = $this->startBedrockServer( 'normal' );

		$query = new MinecraftQuery();
		$query->ConnectBedrock( '127.0.0.1', $port, 5, false );

		$info = $query->GetInfo();

		$this->assertIsArray( $info );
		$this->assertSame( 'MCPE', $info['GameName'] );
		$this->assertSame( 'A Bedrock Server', $info['HostName'] );
		$this->assertSame( '748', $info['Protocol'] );
		$this->assertSame( '1.21.50', $info['Version'] );
		$this->assertSame( 7, $info['Players'] );
		$this->assertSame( 30, $info['MaxPlayers'] );
		$this->assertSame( 'Bedrock level', $info['Map'] );
		$this->assertSame( 'Survival', $info['GameMode'] );
		$this->assertSame( 19132, $info['IPv4Port'] );
		$this->assertSame( 19133, $info['IPv6Port'] );
		$this->assertSame( '0', $info['Extra'] );
		$this->assertFalse( $query->GetPlayers() );
	}

	public function testBedrockMinimalFields() : void
	{
		$port = $this->startBedrockServer( 'minimal' );

		$query = new MinecraftQuery();
		$query->ConnectBedrock( '127.0.0.1', $port, 5, false );

		$info = $query->GetInfo();

		$this->assertIsArray( $info );
		$this->assertSame( 'MCPE', $info['GameName'] );
		$this->assertSame( 'A Minimal Server', $info['HostName'] );
		$this->assertSame( '121', $info['Protocol'] );
		$this->assertSame( '1.0', $info['Version'] );
		$this->assertSame( 500, $info['Players'] );
		$this->assertSame( 10000, $info['MaxPlayers'] );
		$this->assertSame( 'Lobby', $info['Map'] );
		$this->assertSame( 'Survival', $info['GameMode'] );
		// Missing fields should be null/0
		$this->assertNull( $info['NintendoLimited'] );
		$this->assertSame( 0, $info['IPv4Port'] );
		$this->assertSame( 0, $info['IPv6Port'] );
		$this->assertNull( $info['Extra'] );
	}

	public function testBedrockTenFields() : void
	{
		$port = $this->startBedrockServer( 'ten-fields' );

		$query = new MinecraftQuery();
		$query->ConnectBedrock( '127.0.0.1', $port, 5, false );

		$info = $query->GetInfo();

		$this->assertIsArray( $info );
		$this->assertSame( 'A Ten Field Server', $info['HostName'] );
		$this->assertSame( '748', $info['Protocol'] );
		$this->assertSame( 200, $info['Players'] );
		$this->assertSame( 5000, $info['MaxPlayers'] );
		$this->assertSame( '1', $info['NintendoLimited'] );
		// Ports and Extra not present
		$this->assertSame( 0, $info['IPv4Port'] );
		$this->assertSame( 0, $info['IPv6Port'] );
		$this->assertNull( $info['Extra'] );
	}

	public function testBedrockNoTrailingSemicolon() : void
	{
		$port = $this->startBedrockServer( 'no-trailing' );

		$query = new MinecraftQuery();
		$query->ConnectBedrock( '127.0.0.1', $port, 5, false );

		$info = $query->GetInfo();

		$this->assertIsArray( $info );
		$this->assertSame( 'A Bedrock Server', $info['HostName'] );
		$this->assertSame( '748', $info['Protocol'] );
		$this->assertSame( 19132, $info['IPv4Port'] );
		$this->assertSame( 19133, $info['IPv6Port'] );
		$this->assertSame( '0', $info['Extra'] );
	}

	public function testBedrockUnicodeHostname() : void
	{
		$port = $this->startBedrockServer( 'unicode' );

		$query = new MinecraftQuery();
		$query->ConnectBedrock( '127.0.0.1', $port, 5, false );

		$info = $query->GetInfo();

		$this->assertIsArray( $info );
		$this->assertSame( "\xC2\xA7c\xC2\xA7lOPBlocks \xE2\x9A\xA1 \xE2\x98\x85", $info['HostName'] );
		$this->assertSame( '748', $info['Protocol'] );
	}

	public function testBedrockLongHostname() : void
	{
		$port = $this->startBedrockServer( 'long-hostname' );

		$query = new MinecraftQuery();
		$query->ConnectBedrock( '127.0.0.1', $port, 5, false );

		$info = $query->GetInfo();

		$this->assertIsArray( $info );
		$expected = str_repeat( 'A Very Long Server Name! ', 100 );
		$this->assertSame( $expected, $info['HostName'] );
		$this->assertSame( '748', $info['Protocol'] );
		$this->assertSame( 7, $info['Players'] );
	}

	public function testBedrockEmptyHostname() : void
	{
		$port = $this->startBedrockServer( 'empty-hostname' );

		$query = new MinecraftQuery();
		$query->ConnectBedrock( '127.0.0.1', $port, 5, false );

		$info = $query->GetInfo();

		$this->assertIsArray( $info );
		$this->assertSame( '', $info['HostName'] );
		$this->assertSame( '748', $info['Protocol'] );
	}

	public function testBedrockExtraUnknownFields() : void
	{
		$port = $this->startBedrockServer( 'extra-fields' );

		$query = new MinecraftQuery();
		$query->ConnectBedrock( '127.0.0.1', $port, 5, false );

		$info = $query->GetInfo();

		$this->assertIsArray( $info );
		$this->assertSame( 'A Bedrock Server', $info['HostName'] );
		$this->assertSame( '748', $info['Protocol'] );
		$this->assertSame( '1.21.50', $info['Version'] );
		$this->assertSame( 7, $info['Players'] );
		$this->assertSame( 30, $info['MaxPlayers'] );
		$this->assertSame( 19132, $info['IPv4Port'] );
		$this->assertSame( 19133, $info['IPv6Port'] );
		$this->assertSame( '0', $info['Extra'] );
	}

	public function testBedrockNegativeTimeoutThrowsException() : void
	{
		$this->expectException( \InvalidArgumentException::class );

		$query = new MinecraftQuery();
		$query->ConnectBedrock( '127.0.0.1', 19132, -1, false );
	}
}
