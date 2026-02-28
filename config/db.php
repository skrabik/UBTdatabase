<?php

$host = getenv('MYSQL_HOST') ?: 'localhost';
$port = getenv('MYSQL_PORT') ?: '3306';
$database = getenv('MYSQL_DATABASE') ?: 'UBTdatabase';
$username = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';

return [
    'class' => 'yii\db\Connection',
    'dsn' => "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
    'username' => $username,
    'password' => $password,
    'charset' => 'utf8mb4',
];
