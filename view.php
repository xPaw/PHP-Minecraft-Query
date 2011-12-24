<?php
	define( 'MQ_SERVER_ADDR', 'mc.xpaw.ru' );
	define( 'MQ_SERVER_PORT', 25565 );
	define( 'MQ_TIMEOUT', 1 );
	
	require 'MinecraftQuery.class.php';
	
	$Query = new MinecraftQuery( );
	
	try
	{
		$Query->Connect( MQ_SERVER_ADDR, MQ_SERVER_PORT, MQ_TIMEOUT );
	}
	catch( MinecraftQueryException $e )
	{
		$Error = $e->getMessage( );
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>Minecraft Query PHP Class</title>
	<link rel="stylesheet" href="http://twitter.github.com/bootstrap/1.4.0/bootstrap.min.css">
	<style>
		body {
			padding-top: 45px;
			background-color: #049CD9;
			background-repeat: no-repeat;
			background-image: -webkit-gradient(linear, left top, left bottom, from(#004D9F), to(#049cd9));
			background-image: -webkit-linear-gradient(#004D9F, #049cd9);
			background-image: -moz-linear-gradient(#004D9F, #049cd9);
			background-image: -o-linear-gradient(top, #004D9F, #049cd9);
			background-image: -khtml-gradient(linear, left top, left bottom, from(#004D9F), to(#049cd9));
			filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#004D9F', endColorstr='#049cd9', GradientType=0);
		}
		
		.bordered-table { background: white; }
		thead { background: whiteSmoke; }
		h1 { text-align: center; color: white; text-shadow: 0px 0px 20px #DDD; }
		.alert-message { width: 360px; text-align: center; margin: 0 auto; }
	</style>
</head>

<body>
    <div class="container">
		<h1>Minecraft Query PHP Class</h1>

<?php if( isset( $Error ) ): ?>
		<div class="alert-message error">
			<p><b>Error:</b> <?php echo $Error; ?></p>
		</div>
<?php else: ?>
		<!--h2>xPaw is here. <span class="label important">OFFLINE</span></h2--> 
		
		<div class="row">
			<div class="span8">
				<table class="bordered-table zebra-striped">
					<thead>
						<tr>
							<th colspan="2">Server info</th>
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
<?php endif; ?>
					</tbody>
				</table>
			</div>
			<div class="span8">
				<table class="bordered-table zebra-striped">
					<thead>
						<tr>
							<th>Players</th>
						</tr>
					</thead>
					<tbody>
<?php if( ( $Players = $Query->GetPlayers( ) ) !== false ): ?>
<?php foreach( $Players as $Player ): ?>
						<tr>
							<td><?php echo htmlspecialchars( $Player ); ?></td>
						</tr>
<?php endforeach; ?>
<?php else: ?>
						<tr>
							<td>No players in da house!</td>
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
