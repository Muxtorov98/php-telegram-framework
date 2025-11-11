<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\Polling;

$polling = new Polling();
Database::connect();
$polling->run();
