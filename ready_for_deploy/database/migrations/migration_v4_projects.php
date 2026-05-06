<?php
/**
 * MIGRACIÓN KODAN-HUB v4.0: Sistema de Proyectos
 * Objetivo: Crear la tabla de proyectos y vincular las aplicaciones.
 */

require __DIR__ . '/../vendor/autoload.php';

// Configuración manual para la migración (antes de tener Eloquent global)
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'sqlite',
    'database'  => __DIR__ . '/../database/database.sqlite',
    'prefix'    => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    echo "--- Iniciando Migración v4.0 ---\n";

    // 1. Crear tabla de proyectos
    if (!Capsule::schema()->hasTable('projects')) {
        Capsule::schema()->create('projects', function ($table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        echo " - Tabla 'projects' creada.\n";
    }

    // 2. Modificar tabla de apps para añadir project_id
    if (Capsule::schema()->hasTable('apps')) {
        Capsule::schema()->table('apps', function ($table) {
            if (!Capsule::schema()->hasColumn('apps', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('id');
            }
        });
        echo " - Columna 'project_id' añadida a 'apps'.\n";
    }

    echo "--- Migración Finalizada con Éxito ---\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
