<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n🔍 SCHEMA TABELLA PRESENZE\n";
echo "==========================\n\n";

$columns = DB::select('DESCRIBE presenze');
foreach ($columns as $col) {
    echo "- {$col->Field} ({$col->Type})\n";
}

echo "\n✅ Verifica completata\n";

