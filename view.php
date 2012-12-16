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
		$Error = $e->getMessage( );
	}
?>
<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en" xmlns="http://www.w3.org/1999/xhtml"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en" xmlns="http://www.w3.org/1999/xhtml"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en" xmlns="http://www.w3.org/1999/xhtml"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en" xmlns="http://www.w3.org/1999/xhtml"> <!--<![endif]-->
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta name="keywords" content="Minecraft, Stats, HTML5, Query" />
	<meta name="author" lang="en" content="xPaw (email)" />
	<meta name="description" content="Minecraft Stats Via Query"  />
	<meta name="copyright" content="xPaw Copyright (c) 2012" />
	
	<title>Minecraft Query PHP Class</title>
	
	<link rel="stylesheet" href="http://twitter.github.com/bootstrap/assets/css/bootstrap.css">
	<style type="text/css">
		footer {
			margin-top: 45px;
			padding: 35px 0 36px;
			border-top: 1px solid #e5e5e5;
		}
		footer p {
			margin-bottom: 0;
			color: #555;
		}
	</style>
	<!-- IE Fix for HTML5 Tags -->
	<!--[if lt IE 9]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
</head>

<body>
    <div class="container">
    	<div class="page-header">
			<h1>Minecraft Query PHP Class</h1>
		</div>

<?php if( isset( $Error ) ): ?>
		<div class="alert alert-info">
			<h4 class="alert-heading">Exception:</h4>
			<?php echo htmlspecialchars( $Error ); ?>
		</div>
<?php else: ?>
		<div class="row">
			<div class="span6">
				<table class="table table-bordered table-striped">
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
			<div class="span6">
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
		<footer>
			<p class="pull-right">Generated in <span class="badge badge-success"><?php echo Number_Format( ( MicroTime( true ) - $Timer ), 4, '.', '' ); ?>s</span></p>
			
			<p>Written by <a href="http://xpaw.ru" target="_blank">xPaw</a></p>
			<p>Code licensed under the <a href="http://creativecommons.org/licenses/by-nc-sa/3.0/" target="_blank">CC BY-NC-SA 3.0</a></p>
			<p>Sourcecode available on <a href="https://github.com/xPaw/PHP-Minecraft-Query" target="_blank">GitHub</a></p>
		</footer>
	</div>
</body>
</html>
