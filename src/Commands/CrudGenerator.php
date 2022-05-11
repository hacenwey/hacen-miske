<?php

namespace HacenMiske\CodeGenerator\Commands;

use Illuminate\Console\Command;

use HacenMiske\CodeGenerator\CodeGeneratorServices\CrudGeneratorService;
use HacenMiske\CodeGenerator\Services\CrudService;
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

        $relations_model_functions = collect();
        $relations_migration_attr = collect();

        CrudService::generateModelCruds($relations_model_functions, $relations_migration_attr);
        CrudService::appendModelRelationFunctions($relations_model_functions);
        CrudService::appendModeMigrationRelationFKs($relations_migration_attr);
    }
}
