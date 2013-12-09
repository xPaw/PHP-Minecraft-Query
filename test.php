<?php
Error_Reporting( E_ALL | E_STRICT );
Ini_Set( 'display_errors', true );

require __DIR__ . '/src/xPaw/MinecraftQuery/MinecraftQuery.php';
require __DIR__ . '/src/xPaw/MinecraftQuery/MinecraftPing.php';

$query = new xPaw\MinecraftQuery\MinecraftQuery();

$query->connect( '178.32.221.88' );

var_dump( $query->getInfo() );


$ping = new xPaw\MinecraftQuery\MinecraftPing( '178.32.221.88' );

var_dump( $ping->QueryOldPre17() );


?>