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

        foreach ($config['models'] as $model) {
            echo $model['name'] . "\n";
            $attributes = '';
            $tableattr = '';
            $validator = '';
            $resouceAttr = '';
            $model_function_relation = '';
            foreach ($model['attributes']  as $attr) {
                $attributes .= '"' . $attr["key"] . '",';
                $tableattr .= '$table->' . $attr["db_type"] . '("' . $attr["key"] . '");' . "\n\t\t\t";
                $validator .= "'" . $attr["key"] . "' =>" . "'" . $attr["str_validator"] . "', \n\t\t\t";
                $resouceAttr .= "'" . $attr["key"] . "' =>$" . "this->" . $attr["key"] . ", \n\t\t\t";
            }
            $attributes = substr($attributes, 0, -1);
            if (!empty($model['related_to'])) {
                foreach ($model['related_to'] as $relation) {
                    $tableattr .= '$table->unsignedBigInteger(' . "'" . $config["relations"][$relation]["fkey"] . "'" . ");\n\t\t\t";
                    $tableattr .= ' $table->foreign(' . "'" . $config["relations"][$relation]["fkey"] . "'" . ")->references('id')->on('" . strtolower(Str::plural($config["relations"][$relation]["first"])) . "');n\t\t\t";
                    $model_function_relation .= "public function comments() \n{\nreturn " . "$" . "this->" . $config["relations"][$relation]["type"] . "(" . $model['name'] . "::class);\n}\n";



                    CrudGeneratorService::MakeService($model['name']);
                    $this->info('Controller for ' . $model['name'] . ' created successfully');
                    CrudGeneratorService::MakeController($model['name']);
                    $this->info('Controller for ' . $model['name'] . ' created successfully');
                    CrudGeneratorService::MakeModel($model['name'], $attributes, $model_function_relation);
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
            }
        }
    }
}
