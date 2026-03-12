<?php

namespace xPaw;

class SRVResolver
{
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

		\usort( $Records, static function( array $a, array $b ) : int
		{
			if( $a[ 'pri' ] !== $b[ 'pri' ] )
			{
				return $a[ 'pri' ] - $b[ 'pri' ];
			}

			return $b[ 'weight' ] - $a[ 'weight' ];
		} );

		$Record = $Records[ 0 ];

		if( isset( $Record[ 'target' ], $Record[ 'port' ] ) )
		{
			$Address = $Record[ 'target' ];
			$Port = (int)$Record[ 'port' ];
		}
	}
}
