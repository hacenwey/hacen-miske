<?php

namespace HacenMiske\CodeGenerator\Commands;

use Illuminate\Console\Command;

use HacenMiske\CodeGenerator\CodeGeneratorServices\CrudGeneratorService;
use Illuminate\Support\Str;

class CrudGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create all Crud operations with a single command';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $config = json_decode(file_get_contents(base_path('config/crudSettings.json')), true);

        $relations_model_functions = collect();
        $relations_migration_attr = collect();
        foreach ($config['models'] as $model) {
            echo $model['name'] . "\n";
            $attributes = '';
            $tableattr = '';
            $validator = '';
            $resouceAttr = '';
            $secondName = '';
            $model_function_relation = '';
            $migration_attr = '';
            foreach ($model['attributes']  as $attr) {
                $attributes .= '"' . $attr["key"] . '",';
                $tableattr .= '$table->' . $attr["db_type"] . '("' . $attr["key"] . '");' . "\n\t\t\t";
                $validator .= "'" . $attr["key"] . "' =>" . "'" . $attr["str_validator"] . "', \n\t\t\t";
                $resouceAttr .= "'" . $attr["key"] . "' =>$" . "this->" . $attr["key"] . ", \n\t\t\t";
            }
            $attributes = substr($attributes, 0, -1);
            if (!empty($model['related_to'])) {
                foreach ($model['related_to'] as $relation) {
                    $migration_attr .= '$table->unsignedBigInteger(' . "'" . $config["relations"][$relation]["fkey"] . "'" . ");\n\t\t\t";
                    $migration_attr .= ' $table->foreign(' . "'" . $config["relations"][$relation]["fkey"] . "'" . ")->references('id')->on('" . strtolower(Str::plural($config["relations"][$relation]["second"])) . "');\n\t\t\t";
                    if ($relations_migration_attr->has($config["relations"][$relation]["second"])) {
                        $relations_migration_attr[$config["relations"][$relation]["second"]]->push(
                            [
                                "migration_attr" => $migration_attr
                            ]
                        );
                    } else {
                        $relations_migration_attr->put($config["relations"][$relation]["second"], collect([
                            [
                                "migration_attr" => $migration_attr
                            ]
                        ]));
                    }

                    $model_function_relation .= "public function " . strtolower(Str::plural($config["relations"][$relation]["first"])) . "() \n\t\t{\n\t\t\treturn " . "$" . "this->" . $config["relations"][$relation]["type"] . "(" . $model['name'] . "::class);\n\t\t}\n";
                    if ($relations_model_functions->has($config["relations"][$relation]["second"])) {
                        $relations_model_functions[$config["relations"][$relation]["second"]]->push(
                            [
                                'func' => $model_function_relation,
                                'model' => $model['name']
                            ]
                        );
                    } else {

                        $relations_model_functions->put($config["relations"][$relation]["second"], collect([
                            [
                                'func' => $model_function_relation,
                                'model' => $model['name']
                            ]
                        ]));
                    }
                }
            }

            $this->info("relations_model_functions  :  " . $relations_model_functions);

            CrudGeneratorService::MakeService($model['name']);
            $this->info('Controller for ' . $model['name'] . ' created successfully');
            CrudGeneratorService::MakeController($model['name']);
            $this->info('Controller for ' . $model['name'] . ' created successfully');
            CrudGeneratorService::MakeModel($model['name'], $attributes);
            $this->info('Model for ' . $model['name'] . ' created successfully');
            CrudGeneratorService::MakeRequest($model['name'], $validator);
            $this->info('Request for ' . $model['name'] . 'name created successfully');
            CrudGeneratorService::MakeResource($model['name'], $resouceAttr);
            $this->info('Resource for ' . $model['name'] . ' created successfully');
            CrudGeneratorService::MakeMigration($model['name'], $tableattr);
            $this->info('Migration for ' . $model['name'] . ' created successfully');
            CrudGeneratorService::MakeRoute($model['name']);
            $this->info('Route for ' . $model['name'] . ' created successfully');

            $this->info('Api Crud for ' . $model['name'] . ' created successfully');
        }




        if (!empty($relations_model_functions)) {
            foreach ($relations_model_functions as $model => $model_func) {
                $file = app_path("Models\\" . $model . ".php");
                $content = file($file); //Read the file into an array. Line number => line content
                foreach ($content as $lineNumber => &$lineContent) {
                    foreach ($model_func as $func_model) {
                        if ($lineNumber == 2) { //Remember we start at line 0.
                            $lineContent .= 'use App\Models\\' . $func_model["model"] . ";\n";
                            //Modify the line. (We're adding another line by using PHP_EOL)
                        }
                        if ($lineNumber == 11) {
                            $lineContent .= "\t" . $func_model["func"];
                        }
                    } //Loop through the array (the "lines")

                }

                $allContent = implode("", $content); //Put the array back into one string
                file_put_contents($file, $allContent);
            }
        }

        if (!empty($relations_migration_attr)) {
            foreach ($relations_migration_attr as $model => $value) {
                $glob = glob('database/migrations/*_' . strtolower(Str::plural($model)) . '_table.php');
                $file = $glob[0];
                $content = file($file); //Read the file into an array. Line number => line content
                foreach ($content as $lineNumber => &$lineContent) {
                    foreach ($value as $migration) {
                        if ($lineNumber == 17) {
                            $lineContent .= $migration["migration_attr"];
                        }
                    } //Loop through the array (the "lines")

                }

                $allContent = implode("", $content); //Put the array back into one string
                file_put_contents($file, $allContent);
            }
        }
    }
}
