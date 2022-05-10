<?php

namespace HacenMiske\CodeGenerator\Commands;

use Illuminate\Console\Command;

use HacenMiske\CodeGenerator\CodeGeneratorServices\CrudGeneratorService;

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
            $index = 0;
            foreach (array_combine($model['attributes'], $model['types'])  as $attr => $type) {
                $attributes .= '"' . $attr . '",';
                $tableattr .= '$table->' . $type . '("' . $attr . '");' . "\n\t\t\t";
                $validator .= "'" . $attr . "' =>" . "'" . $model['validator'][$index] . "', \n\t\t\t\t";
                $resouceAttr .= "'" . $attr . "' =>$" . "this->" . $attr . ", \n\t\t\t\t";
                $index += 1;
            }
            $attributes = substr($attributes, 0, -1);
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
    }
}
