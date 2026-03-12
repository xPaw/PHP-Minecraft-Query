<?php
declare(strict_types=1);

namespace xPaw;

class SRVResolver
{
	/**
	 * @param-out string $Address
	 * @param-out int $Port
	 */
	public static function Resolve( string &$Address, int &$Port ) : void
	{
		if( @\inet_pton( $Address ) !== false )
		{
			return;
		}

		$Records = @\dns_get_record( '_minecraft._tcp.' . $Address, DNS_SRV );

		if( empty( $Records ) )
		{
			return;
		}

		/** @var array{pri: int, weight: int, target: string, port: int}[] $Records */
		\usort( $Records, static function( array $a, array $b ) : int
		{
			if( $a[ 'pri' ] !== $b[ 'pri' ] )
			{
				return $a[ 'pri' ] - $b[ 'pri' ];
			}

			return $b[ 'weight' ] - $a[ 'weight' ];
		} );

		$Record = $Records[ 0 ];

		$Address = $Record[ 'target' ];
		$Port = $Record[ 'port' ];
	}
}
