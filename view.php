<?php
	// Edit this ->
	define( 'MQ_SERVER_ADDR', 'localhost' );
	define( 'MQ_SERVER_PORT', 25565 );
	define( 'MQ_TIMEOUT', 1 );
	// Edit this <-
	
	// Display everything in browser, because some people can't look in logs for errors
	Error_Reporting( E_ALL | E_STRICT );
	Ini_Set( 'display_errors', true );
	
	require __DIR__ . '/MinecraftQuery.class.php';
	
	$Timer = MicroTime( true );
	
	$Query = new MinecraftQuery( );
	
	try
	{
		$Query->Connect( MQ_SERVER_ADDR, MQ_SERVER_PORT, MQ_TIMEOUT );
	}
	catch( MinecraftQueryException $e )
	{
		$Exception = $e;
	}
	
	$Timer = Number_Format( MicroTime( true ) - $Timer, 4, '.', '' );
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Minecraft Query PHP Class</title>
	
	<link rel="stylesheet" href="http://netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
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
			<h1>Minecraft Query PHP Class</h1>
			
			<p>This class was created to query Minecraft servers. It works starting from Minecraft 1.0.</p>
			
			<p>
				<a class="btn btn-large btn-primary" href="http://xpaw.me">Made by xPaw</a>
				<a class="btn btn-large btn-primary" href="https://github.com/xPaw/PHP-Minecraft-Query">View on GitHub</a>
				<a class="btn btn-large btn-danger" href="http://creativecommons.org/licenses/by-nc-sa/3.0/">CC BY-NC-SA 3.0</a>
			</p>
		</div>

<?php if( isset( $Exception ) ): ?>
		<div class="panel panel-primary">
			<div class="panel-heading"><?php echo htmlspecialchars( $Exception->getMessage( ) ); ?></div>
			<p><?php echo nl2br( $e->getTraceAsString(), false ); ?></p>
		</div>
<?php else: ?>
		<div class="row">
			<div class="col-sm-6">
				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th colspan="2">Server Info <em>(queried in <?php echo $Timer; ?>s)</em></th>
						</tr>
					</thead>
					<tbody>
<?php if( ( $Info = $Query->GetInfo( ) ) !== false ): ?>
<?php foreach( $Info as $InfoKey => $InfoValue ): ?>
						<tr>
							<td><?php echo htmlspecialchars( $InfoKey ); ?></td>
							<td><?php
	if( Is_Array( $InfoValue ) )
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
			<div class="col-sm-6">
				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th>Players</th>
						</tr>
					</thead>
					<tbody>
<?php if( ( $Players = $Query->GetPlayers( ) ) !== false ): ?>
<?php foreach( $Players as $Player ): ?>
						<tr>
							<td><?php echo '<img src="http://cravatar.eu/helmavatar/'. htmlspecialchars( $Player ).'/32.png">    '.htmlspecialchars( $Player ) ; ?></td>
						</tr>
<?php endforeach; ?>
<?php else: ?>
						<tr>
							<td>No players in da house</td>
						</tr>
<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
<?php endif; ?>
	</div>
</body>
</html>
