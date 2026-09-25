<?php
require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Before require_once:\n";
echo "Class exists (no autoload): " . (class_exists('Database\\Migrations\\AddFacultyIdToSpecialties', false) ? 'yes' : 'no') . "\n";

$file = __DIR__ . '/database/migrations/2026_09_19_000001_add_faculty_id_to_specialties.php';

// Simulate requireFiles
echo "Calling require_once...\n";
$result = require_once $file;
echo "require_once returned: " . var_export($result, true) . "\n";
echo "Class exists (no autoload): " . (class_exists('Database\\Migrations\\AddFacultyIdToSpecialties', false) ? 'yes' : 'no') . "\n";
echo "Class exists (with autoload): " . (class_exists('Database\\Migrations\\AddFacultyIdToSpecialties', true) ? 'yes' : 'no') . "\n";

// Simulate resolvePath
$className = 'Database\\Migrations\\AddFacultyIdToSpecialties';
echo "class_exists result: " . var_export(class_exists($className), true) . "\n";

if (class_exists($className)) {
    $reflection = new ReflectionClass($className);
    echo "Reflection filename: " . $reflection->getFileName() . "\n";
    echo "Realpath: " . realpath($file) . "\n";
    echo "Match: " . (realpath($file) === $reflection->getFileName() ? 'yes' : 'no') . "\n";
}
