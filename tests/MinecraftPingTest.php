<?php
declare(strict_types=1);

namespace xPaw\Tests;

use PHPUnit\Framework\TestCase;
use xPaw\MinecraftPing;
use xPaw\MinecraftPingException;

class MinecraftPingTest extends TestCase
{
	private FakeServerHelper $server;

	private function startServer( string $mode = 'modern' ) : int
	{
		$port = FakeServerHelper::findFreePort();
		$this->server = new FakeServerHelper( __DIR__ . '/FakeServer/FakePingServer.php' );
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

	public function testModernPingReturnsServerInfo() : void
	{
		$port = $this->startServer();

		$ping = new MinecraftPing( '127.0.0.1', $port, 5, false );

		try
		{
			$result = $ping->Query();
		}
		finally
		{
			$ping->Close();
		}

		$this->assertIsArray( $result );

		/** @var array<string, mixed> $result */
		$this->assertArrayHasKey( 'version', $result );
		$this->assertArrayHasKey( 'players', $result );
		$this->assertArrayHasKey( 'description', $result );

		/** @var array{version: array{name: string, protocol: int}, players: array{max: int, online: int, sample: list<array{name: string}>}, description: array{text: string}} $result */
		$this->assertSame( '1.21.4', $result['version']['name'] );
		$this->assertSame( 769, $result['version']['protocol'] );
		$this->assertSame( 100, $result['players']['max'] );
		$this->assertSame( 5, $result['players']['online'] );
		$this->assertSame( 'A Fake Minecraft Server', $result['description']['text'] );
		$this->assertCount( 1, $result['players']['sample'] );
		$this->assertSame( 'xPaw', $result['players']['sample'][0]['name'] );
	}

	public function testLegacyPingReturnsServerInfo() : void
	{
		$port = $this->startServer( 'legacy' );

		$ping = new MinecraftPing( '127.0.0.1', $port, 5, false );

		try
		{
			$result = $ping->QueryOldPre17();
		}
		finally
		{
			$ping->Close();
		}

		$this->assertIsArray( $result );

		/** @var array{HostName: string, Players: int, MaxPlayers: int, Protocol: int, Version: string} $result */
		$this->assertSame( 'A Legacy Server', $result['HostName'] );
		$this->assertSame( 3, $result['Players'] );
		$this->assertSame( 50, $result['MaxPlayers'] );
		$this->assertSame( 127, $result['Protocol'] );
		$this->assertSame( '1.6.4', $result['Version'] );
	}

	public function testLegacyPre13ReturnsServerInfo() : void
	{
		$port = $this->startServer( 'legacy-pre13' );

		$ping = new MinecraftPing( '127.0.0.1', $port, 5, false );

		try
		{
			$result = $ping->QueryOldPre17();
		}
		finally
		{
			$ping->Close();
		}

		$this->assertIsArray( $result );

		/** @var array{HostName: string, Players: int, MaxPlayers: int, Protocol: int, Version: string} $result */
		$this->assertSame( 'An Old Server', $result['HostName'] );
		$this->assertSame( 2, $result['Players'] );
		$this->assertSame( 10, $result['MaxPlayers'] );
		$this->assertSame( 0, $result['Protocol'] );
		$this->assertSame( '1.3', $result['Version'] );
	}

	public function testConnectionFailureThrowsException() : void
	{
		$this->expectException( MinecraftPingException::class );

		$port = FakeServerHelper::findFreePort();
		new MinecraftPing( '127.0.0.1', $port, 1, false );
	}

	public function testNegativeTimeoutThrowsException() : void
	{
		$this->expectException( \InvalidArgumentException::class );
		new MinecraftPing( '127.0.0.1', 25565, -1, false );
	}

	public function testCloseAndReconnect() : void
	{
		$port = $this->startServer();

		$ping = new MinecraftPing( '127.0.0.1', $port, 5, false );
		$result = $ping->Query();
		$ping->Close();

		$this->assertIsArray( $result );

		/** @var array{version: array{name: string}} $result */
		$this->assertSame( '1.21.4', $result['version']['name'] );
	}
}
