<?php
	use xPaw\MinecraftPing;
	use xPaw\MinecraftPingException;

	// Edit this ->
	define( 'MQ_SERVER_ADDR', 'localhost' );
	define( 'MQ_SERVER_PORT', 25565 );
	define( 'MQ_TIMEOUT', 1 );
	// Edit this <-

	// Display everything in browser, because some people can't look in logs for errors
	Error_Reporting( E_ALL | E_STRICT );
	Ini_Set( 'display_errors', true );

	require __DIR__ . '/src/MinecraftPing.php';
	require __DIR__ . '/src/MinecraftPingException.php';

	$Timer = MicroTime( true );

	$Info = false;
	$Query = null;

	try
	{
		$Query = new MinecraftPing( MQ_SERVER_ADDR, MQ_SERVER_PORT, MQ_TIMEOUT );

		$Info = $Query->Query( );

		if( $Info === false )
		{
			/*
			 * If this server is older than 1.7, we can try querying it again using older protocol
			 * This function returns data in a different format, you will have to manually map
			 * things yourself if you want to match 1.7's output
			 *
			 * If you know for sure that this server is using an older version,
			 * you then can directly call QueryOldPre17 and avoid Query() and then reconnection part
			 */

			$Query->Close( );
			$Query->Connect( );

			$Info = $Query->QueryOldPre17( );
		}
	}
	catch( MinecraftPingException $e )
	{
		$Exception = $e;
	}

	if( $Query !== null )
	{
		$Query->Close( );
	}

	$Timer = Number_Format( MicroTime( true ) - $Timer, 4, '.', '' );
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Minecraft Ping PHP Class</title>

	<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<style type="text/css">
		.jumbotron {
			margin-top: 30px;
			border-radius: 0;
		}

		.table thead th {
			background-color: #428BCA;
			border-color: #428BCA !important;
			color: #FFF;
		}
	</style>
</head>

<body>
    <div class="container">
    	<div class="jumbotron">
			<h1>Minecraft Ping PHP Class</h1>

			<p>This class was created to query Minecraft servers. It works starting from Minecraft 1.0.</p>

			<p>
				<a class="btn btn-large btn-primary" href="https://xpaw.me">Made by xPaw</a>
				<a class="btn btn-large btn-primary" href="https://github.com/xPaw/PHP-Minecraft-Query">View on GitHub</a>
				<a class="btn btn-large btn-danger" href="https://github.com/xPaw/PHP-Minecraft-Query/blob/master/LICENSE">MIT license</a>
			</p>
		</div>

<?php if( isset( $Exception ) ): ?>
		<div class="panel panel-primary">
			<div class="panel-heading"><?php echo htmlspecialchars( $Exception->getMessage( ) ); ?></div>
			<div class="panel-body"><?php echo nl2br( $e->getTraceAsString(), false ); ?></div>
		</div>
<?php else: ?>
		<div class="row">
				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th colspan="2">Server Info <em>(queried in <?php echo $Timer; ?>s)</em></th>
						</tr>
					</thead>
					<tbody>
<?php if( $Info !== false ): ?>
<?php foreach( $Info as $InfoKey => $InfoValue ): ?>
						<tr>
							<td><?php echo htmlspecialchars( $InfoKey ); ?></td>
							<td><?php
	if( $InfoKey === 'favicon' )
	{
		echo '<img width="64" height="64" src="' . Str_Replace( "\n", "", $InfoValue ) . '">';
	}else if( Is_Array( $InfoValue ) )
	{
		echo "<pre>";
		print_r( $InfoValue );
		echo "</pre>";
	}
	else
	{
		echo htmlspecialchars( $InfoValue );
	}
?></td>
						</tr>
<?php endforeach; ?>
<?php else: ?>
						<tr>
							<td colspan="2">No information received</td>
						</tr>
<?php endif; ?>
					</tbody>
				</table>
		</div>
<?php endif; ?>
	</div>
</body>
</html>
