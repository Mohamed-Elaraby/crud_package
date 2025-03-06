<?php

namespace mohamedelaraby\QuickCrud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateCrud extends Command
{
    protected $signature = 'generate:crud {model}';
    protected $description = 'Generate CRUD files for a given model';

    public function handle()
    {
        $modelName = ucfirst($this->argument('model'));
        $modelVariable = lcfirst($modelName);
        $pluralModel = Str::plural($modelVariable);
        $translationFormattedString = strtolower(preg_replace('/([a-z])([A-Z])/', '$1 $2', $modelName));
        $titleUpperCase = strtoupper(preg_replace('/([a-z])([A-Z])/', '$1 $2', $modelName));


        // Define Paths
        $controllerPath = base_path("app/Http/Controllers/Admin/{$modelName}Controller.php");
        $viewPath = base_path("resources/views/admin/{$pluralModel}/");
        $modelPath = base_path("app/Models/{$modelName}.php");
        $dataTablePath = base_path("app/DataTables/{$modelName}DataTable.php");
        $migrationPath = base_path('database/migrations/' . date('Y_m_d_His') . "_create_{$pluralModel}_table.php");
        // Ensure directories exist
        $this->createDirectory(dirname($controllerPath));
        $this->createDirectory($viewPath);
        $this->createDirectory(dirname($dataTablePath));

        // Generate Controller
        $controllerStub = file_get_contents(__DIR__.'/../../../resources/stubs/controller.stub');        $controllerStub = str_replace(
            ['{{ModelName}}', '{{modelVariable}}', '{{pluralModel}}'],
            [$modelName, $modelVariable, $pluralModel],
            $controllerStub
        );
        File::put($controllerPath, $controllerStub);
        $this->info("Controller Created: {$controllerPath}");

        // Generate Views
        $views = ['index', 'create', 'edit'];
        foreach ($views as $view) {
            $viewStub = file_get_contents(resource_path("stubs/views/{$view}.stub"));
            $viewStub = str_replace(
                [
                    '{{ModelName}}',
                    '{{modelVariable}}',
                    '{{pluralModel}}',
                    '{{translationFormattedString}}',
                    '{{titleUpperCase}}'
                ],
                [
                    $modelName,
                    $modelVariable,
                    $pluralModel,
                    $translationFormattedString,
                    $titleUpperCase
                ],
                $viewStub
            );
            File::put("{$viewPath}/{$view}.blade.php", $viewStub);
        }
        $this->info("Views Created: {$viewPath}");

        // Generate DataTable
        File::ensureDirectoryExists(base_path('app/DataTables'));
        $datatableStub = file_get_contents(resource_path('stubs/datatables/datatable.stub'));
        $datatableStub = str_replace(
            ['{{ModelName}}', '{{modelVariable}}', '{{pluralModel}}'],
            [$modelName, $modelVariable, $pluralModel],
            $datatableStub
        );
        File::put($dataTablePath, $datatableStub);
        $this->info("DataTable Created: {$dataTablePath}");

        // Generate Model
        $modelStub = file_get_contents(resource_path('stubs/models/model.stub'));
        $modelStub = str_replace('{{ModelName}}', $modelName, $modelStub);
        File::put($modelPath, $modelStub);
        $this->info("Model Created: {$modelPath}");

        // Generate Migration
        $migrationStub = file_get_contents(resource_path('stubs/migrations/migration.stub'));
        $migrationStub = str_replace('{{pluralModel}}', $pluralModel, $migrationStub);
        File::put($migrationPath, $migrationStub);
        $this->info("Migration Created: {$migrationPath}");

        $path = base_path('routes/web.php');
        $fileContent = file_get_contents($path);

// 1. البحث عن مجموعة الـ admin باستخدام تعبير عادي دقيق
        $pattern = '/Route::prefix\(\'admin\'\)\s*->name\(\'admin\.\'\)\s*->middleware\(\'auth\'\)\s*->group\(function\s*\(\)\s*{([\s\S]*?)}\);/m';

        if (preg_match($pattern, $fileContent, $matches)) {
            // 2. استخراج المحتوى الداخلي للمجموعة
            $groupContent = $matches[1];

            // 3. التحقق من وجود الراوت داخل المجموعة فقط
            $routePattern = "/Route::resource\('{$pluralModel}'/";

            if (preg_match($routePattern, $groupContent)) {
                $this->error("Route already exists inside admin group!");
            } else {
                // 4. إضافة الراوت الجديد داخل المجموعة
                $updatedGroup = str_replace(
                    $groupContent,
                    $groupContent . "\t\tRoute::resource('{$pluralModel}', \\App\\Http\\Controllers\\Admin\\{$modelName}Controller::class);\n",
                    $matches[0]
                );

                $newContent = str_replace($matches[0], $updatedGroup, $fileContent);
                file_put_contents($path, $newContent);
                $this->info("Route added successfully inside admin group!");
            }
        } else {
            // 5. حالة عدم وجود مجموعة الـ admin: إنشاؤها وإضافة الراوت
            $adminGroup = <<<EOT
                Route::prefix('admin')
                    ->name('admin.')
                    ->middleware('auth')
                    ->group(function () {
                        Route::resource('{$pluralModel}', \\App\\Http\\Controllers\\Admin\\{$modelName}Controller::class);
                    });
                EOT;

            $newContent = $fileContent . "\n" . $adminGroup;
            file_put_contents($path, $newContent);
            $this->info("Admin group created with new route!");
        }


        $this->info("CRUD for {$modelName} generated successfully!");
    }

    private function createDirectory($path)
    {
        if (!File::exists($path)) {
            File::makeDirectory($path, 0777, true, true);
        }
    }
}