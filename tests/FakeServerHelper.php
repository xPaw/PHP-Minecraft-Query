<?php
declare(strict_types=1);

namespace xPaw\Tests;

/**
 * Helper to start/stop fake server subprocesses for testing.
 */
class FakeServerHelper
{
	/** @var resource|false */
	private $process = false;

	/** @var ?array<int, resource> */
	private ?array $pipes = null;

	private int $port;

	public function __construct( private string $script )
	{
	}

	/**
	 * Start the fake server on the given port.
	 * Waits for the server to print "READY" before returning.
	 *
	 * @param string[] $extraArgs
	 */
	public function start( int $port, array $extraArgs = [] ) : void
	{
		$this->port = $port;

		$cmd = PHP_BINARY . ' ' . escapeshellarg( $this->script ) . ' ' . $port;

		foreach( $extraArgs as $arg )
		{
			$cmd .= ' ' . escapeshellarg( $arg );
		}

		$descriptors = [
			0 => [ 'pipe', 'r' ],  // stdin
			1 => [ 'pipe', 'w' ],  // stdout
			2 => [ 'pipe', 'w' ],  // stderr
		];

		$pipes = [];
		$this->process = proc_open( $cmd, $descriptors, $pipes );

		if( $this->process === false )
		{
			throw new \RuntimeException( "Failed to start fake server: {$this->script}" );
		}

		$this->pipes = $pipes;

		// Wait for "READY" signal with a timeout
		stream_set_timeout( $pipes[1], 5 );
		$line = fgets( $pipes[1] );

		if( trim( (string)$line ) !== 'READY' )
		{
			$stderr = stream_get_contents( $pipes[2] );
			$this->stop();
			throw new \RuntimeException( "Fake server did not become ready. stderr: {$stderr}" );
		}
	}

	public function getPort() : int
	{
		return $this->port;
	}

	public function stop() : void
	{
		if( $this->pipes !== null )
		{
			foreach( $this->pipes as $pipe )
			{
				if( is_resource( $pipe ) )
				{
					fclose( $pipe );
				}
			}

			$this->pipes = null;
		}

		if( $this->process !== false && is_resource( $this->process ) )
		{
			proc_terminate( $this->process );

			$status = proc_get_status( $this->process );

			if( $status['running'] )
			{
				proc_terminate( $this->process, 9 );
			}

			proc_close( $this->process );
			$this->process = false;
		}
	}

	/**
	 * Find an available port for testing.
	 */
	public static function findFreePort() : int
	{
		$sock = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );

		if( $sock === false )
		{
			throw new \RuntimeException( 'Failed to create socket for port discovery' );
		}

		socket_bind( $sock, '127.0.0.1', 0 );
		socket_getsockname( $sock, $addr, $port );
		socket_close( $sock );

		/** @var int $port */
		return $port;
	}
}
