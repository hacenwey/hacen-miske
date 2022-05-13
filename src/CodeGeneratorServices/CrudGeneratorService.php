<?php

namespace HacenMiske\CodeGenerator\CodeGeneratorServices;


use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudGeneratorService
{

    protected static function GetStubs($type)
    {
        return file_get_contents(resource_path("hacen-miske/stubs/$type.stub"));
    }


    /**
     * @param $name
     * This will create controller from stub file
     */
    public static function MakeController($name)
    {
        $template = str_replace(
            [
                '{{modelName}}',
            ],
            [
                $name,
            ],

            CrudGeneratorService::GetStubs('Controller')
        );

        file_put_contents(app_path("/Http/Controllers/{$name}Controller.php"), $template);
    }

    /**
     * @param $name
     * This will create controller from stub file
     */
    public static function MakeService($name)
    {
        $template = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}',
            ],
            [
                $name,
                strtolower(Str::plural($name)),
                strtolower($name)
            ],

            CrudGeneratorService::GetStubs('Service')
        );
        if (!file_exists($path = app_path('/Http/Services/')))
            mkdir($path, 0777, true);
        file_put_contents(app_path("/Http/Services/{$name}Service.php"), $template);
    }

    /**
     * @param $name
     * This will create model from stub file
     */
    public static function MakeModel($name, $attributes)
    {
        $template = str_replace(
            ['{{modelName}}', '{{attributes}}'],
            [$name, $attributes],
            CrudGeneratorService::GetStubs('Model')
        );

        file_put_contents(app_path("/Models/{$name}.php"), $template);
    }

    /**
     * @param $name
     * This will create Request from stub file
     */
    public static function MakeRequest($name, $attributes)
    {
        $template = str_replace(
            ['{{modelName}}', '{{attributes}}'],
            [$name, $attributes],
            CrudGeneratorService::GetStubs('Request')
        );

        if (!file_exists($path = app_path('/Http/Requests/')))
            mkdir($path, 0777, true);

        file_put_contents(app_path("/Http/Requests/{$name}Request.php"), $template);
    }
    /**
     * @param $name
     * This will create Request from stub file
     */
    public static function MakeResource($name, $attr)
    {
        $template = str_replace(
            ['{{modelName}}', '{{attributes}}'],
            [$name, $attr],
            CrudGeneratorService::GetStubs('Resource')
        );

        if (!file_exists($path = app_path('/Http/Resource/')))
            mkdir($path, 0777, true);

        file_put_contents(app_path("/Http/Resource/{$name}Resource.php"), $template);
    }

    /**
     * @param $name
     * This will create migration using artisan command
     */
    public static function MakeMigration($name, $tableattr, $x)
    {
        // Artisan::call('make:migration create_' . strtolower(Str::plural($name)) . '_table --create=' . strtolower(Str::plural($name)));
        $template = str_replace(
            ['{{modelName}}', '{{tableattr}}'],
            [Str::plural($name), $tableattr],
            CrudGeneratorService::GetStubs('Migration')
        );



        file_put_contents(base_path('database/migrations/' . date('Y_m_d_u', (time() + $x)) . '_create_' . strtolower(Str::plural($name)) . '_table.php'), $template);
    }

    /**
     * @param $name
     * This will create route in api.php file
     */
    public static function MakeRoute($name)
    {
        $path_to_file  = base_path('routes/api.php');
        $append_route = 'Route::apiResource(\'' . Str::plural(strtolower($name)) . "', {$name}Controller::class); \n";
        $append_import = 'use App\Http\Controllers\\' . $name . "Controller;";
        File::append($path_to_file, $append_route);
        $file = str_replace(['<?php'], ["<?php \n " . $append_import], file_get_contents($path_to_file));
        file_put_contents($path_to_file, $file);
    }
}
