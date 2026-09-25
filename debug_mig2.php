<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get the migrator
$migrator = $app->make('Illuminate\Database\Migrations\Migrator');

// Check if the class is defined before anything
$class = 'Database\\Migrations\\AddFacultyIdToSpecialties';
echo "Before: class_exists = " . var_export(class_exists($class), true) . "\n";

// Now simulate what requireFiles does
$files = $migrator->getMigrationFiles(database_path('/migrations'));
echo "Total migration files: " . count($files) . "\n";

$myFile = null;
foreach ($files as $name => $path) {
    if (str_contains($name, 'faculty')) {
        $myFile = $path;
        echo "Found my file: $path\n";
        echo "Name: $name\n";
    }
}

if ($myFile) {
    // Simulate requireFiles
    echo "\nCalling requireOnce...\n";
    $migrator->requireFiles([$myFile]);
    echo "After requireOnce: class_exists = " . var_export(class_exists($class), true) . "\n";
    
    // Now try resolvePath
    echo "\nCalling resolvePath...\n";
    try {
        $migration = $migrator->resolvePath($myFile);
        echo "Resolve success: " . get_class($migration) . "\n";
    } catch (\Exception $e) {
        echo "Resolve failed: " . $e->getMessage() . "\n";
    }
}
