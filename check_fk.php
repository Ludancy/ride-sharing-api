<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$res = DB::select("SELECT CONSTRAINT_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'traslados' AND COLUMN_NAME IN ('origen', 'destino') AND TABLE_SCHEMA = DATABASE() AND CONSTRAINT_NAME != 'PRIMARY'");
echo json_encode($res);
