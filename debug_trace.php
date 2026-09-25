<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Override the error handler to get a stack trace
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    echo "ERROR: $errstr at $errfile:$errline\n";
    echo "Stack trace:\n";
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    foreach ($trace as $i => $frame) {
        echo "  #$i: " . ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '') . " in " . ($frame['file'] ?? 'unknown') . ":" . ($frame['line'] ?? '0') . "\n";
    }
    return true;
});

// Get the migrator
$migrator = $app->make('Illuminate\Database\Migrations\Migrator');

// Get all migration files
$files = $migrator->getMigrationFiles(database_path('/migrations'));
echo "Total migration files: " . count($files) . "\n";

// Find my file
$myFile = null;
foreach ($files as $name => $path) {
    if (str_contains($name, 'faculty')) {
        $myFile = $path;
        echo "My file: $name => $path\n";
    }
}

if ($myFile) {
    // Check class before anything
    $class = 'Database\\Migrations\\AddFacultyIdToSpecialties';
    echo "\nClass exists before require: " . var_export(class_exists($class), true) . "\n";
    
    // Simulate requireFiles (require_once)
    echo "Calling require_once directly...\n";
    require_once $myFile;
    echo "Class exists after require_once: " . var_export(class_exists($class), true) . "\n";
    
    // Now simulate resolvePath
    echo "\nCalling resolvePath logic manually...\n";
    $migrationName = str_replace('.php', '', basename($myFile));
    echo "Migration name: $migrationName\n";
    
    $expectedClass = Illuminate\Support\Str::studly(implode('_', array_slice(explode('_', $migrationName), 4)));
    echo "Expected class: Database\\Migrations\\$expectedClass\n";
    
    echo "class_exists result: " . var_export(class_exists($expectedClass), true) . "\n";
    
    if (class_exists($expectedClass)) {
        $realpath = realpath($myFile);
        $reflectionFile = (new ReflectionClass($expectedClass))->getFileName();
        echo "realpath: $realpath\n";
        echo "reflection: $reflectionFile\n";
        echo "Match: " . var_export($realpath === $reflectionFile, true) . "\n";
    }
}
