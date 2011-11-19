<?php
require_once('config.php');
require_once('MinecraftQuery.class.php');

$query = new MinecraftQuery();
try {
	$query->Connect(MQ_SERVER_ADDR, MQ_SERVER_PORT, MQ_TIMEOUT);
} catch (MinecraftQueryException $e) {
	$error = $e->getMessage();
}
?>
<html>
	<head>
		<title>Minecraft Server Status</title>
	</head>
	<body>
<h1>Minecraft Server Status</h1>
<?php if (isset($error)): ?>
		<div class="error"><?php echo htmlspecialchars($error) ?></div>
<?php else: ?>
		<table class="info">
			<tr>
				<th colspan="2">Server Info</th>
			</tr>
<?php if (($info = $query->GetInfo()) !== false): ?>
<?php foreach ($info as $info_key => $info_value): ?>
			<tr>
				<th><?php echo htmlspecialchars($info_key) ?></th>
				<td><?php echo htmlspecialchars($info_value) ?></td>
			</tr>
<?php endforeach ?>
<?php endif ?>
		</table>
		<table class="players">
			<tr>
				<th>Players</th>
			</tr>
<?php if (($players = $query->GetPlayers()) !== false): ?>
<?php foreach ($players as $player): ?>
			<tr>
				<td><?php echo htmlspecialchars($player) ?></td>
			</tr>
<?php endforeach ?>
<?php endif ?>
		</table>
<?php endif ?>
	</body>
</html>

