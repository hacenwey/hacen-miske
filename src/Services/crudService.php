<?php

namespace HacenMiske\CodeGenerator\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use HacenMiske\CodeGenerator\CodeGeneratorServices\CrudGeneratorService;

class CrudService
{
    static  function generateModelCruds(&$relations_model_functions, &$relations_migration_attr)
    {
        $config = json_decode(file_get_contents(base_path('config/crudSettings.json')), true);
        CrudService::orderMigrationTables($config);
        $x = 0;
        foreach ($config['models'] as $model) {

            $attributes = '';
            $tableattr = '';
            $validator = '';
            $resouceAttr = '';
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

                    if ($relations_migration_attr->has($config["relations"][$relation]["second"])) {
                        $relations_migration_attr[$config["relations"][$relation]["second"]]->push(
                            [
                                "migration_attr" => $migration_attr
                            ]
                        );

                        $migration_attr .= '$table->unsignedBigInteger(' . "'" . $config["relations"][$relation]["fkey"] . "'" . ");\n\t\t\t";
                        $migration_attr .= '$table->foreign(' . "'" . $config["relations"][$relation]["fkey"] . "'" . ")->references('id')->on('" . strtolower(Str::plural($config["relations"][$relation]["first"])) . "');\n\t\t";
                    } else {
                        $relations_migration_attr->put($config["relations"][$relation]["second"], collect([
                            [
                                "migration_attr" => $migration_attr
                            ]
                        ]));
                    }

                    if ($relations_model_functions->has($config["relations"][$relation]["second"]) && $relations_model_functions[$config["relations"][$relation]["second"]]->pluck("model")->search($model["name"]) == null) {

                        echo "second";
                        $relations_model_functions[$config["relations"][$relation]["second"]]->push(
                            [
                                'func' => $model_function_relation,
                                'model' => $model['name']
                            ]
                        );
                        $model_function_relation .= "public function " . strtolower(Str::plural($config["relations"][$relation]["first"])) . "() \n\t{\n\t\treturn " . "$" . "this->" . $config["relations"][$relation]["type"] . "(" . $model['name'] . "::class);\n\t}\n";
                    } else {
                        echo "first";
                        $model_function_relation .= "public function " . strtolower(Str::plural($config["relations"][$relation]["first"])) . "() \n\t{\n\t\treturn " . "$" . "this->" . $config["relations"][$relation]["type"] . "(" . $model['name'] . "::class);\n\t}\n";
                        $relations_model_functions->put($config["relations"][$relation]["second"], collect([
                            [
                                'func' => $model_function_relation,
                                'model' => $model['name']
                            ]
                        ]));
                    }
                }
            }

            CrudService::createModelCrud($model["name"], $validator, $attributes, $resouceAttr, $tableattr, $x);
        }
    }

    static function appendModelRelationFunctions($relations_model_functions)
    {
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
    }

    static function appendModeMigrationRelationFKs($relations_migration_attr)
    {
        if (!empty($relations_migration_attr)) {
            foreach ($relations_migration_attr as $model => $value) {
                $glob = glob('database/migrations/*_' . strtolower(Str::plural($model)) . '_table.php');
                $file = $glob[0];
                $content = file($file); //Read the file into an array. Line number => line content
                foreach ($content as $lineNumber => &$lineContent) {
                    foreach ($value as $migration) {
                        if ($lineNumber == 17) {
                            $lineContent .= substr("\t\t\t" . $migration["migration_attr"], 0, -2);
                        }
                    } //Loop through the array (the "lines")

                }

                $allContent = implode("", $content); //Put the array back into one string
                file_put_contents($file, $allContent);
            }
        }
    }

    static function createModelCrud($name, $validator, $attributes, $resouceAttr, $tableattr, &$x)
    {
        CrudGeneratorService::MakeService($name);
        CrudGeneratorService::MakeController($name);
        CrudGeneratorService::MakeModel($name, $attributes);
        CrudGeneratorService::MakeRequest($name, $validator);
        CrudGeneratorService::MakeResource($name, $resouceAttr);
        CrudGeneratorService::MakeMigration($name, $tableattr, $x);
        CrudGeneratorService::MakeRoute($name);
        echo "\033[01;32m Api Crud for\033[0m \033[01;33m" . $name . "\033[0m \033[01;32m created successfully\n \033[0m";
        $x += 1;
    }
    static function orderMigrationTables(&$json)
    {
        $json["models"] = collect($json["models"])->sortBy(function ($model, $key) {
            return count($model["related_to"]);
        }, descending: true);

        $models_dependency = collect();
        foreach ($json["models"] as $model) {
            $models_dependency->put($model["name"], collect([]));
            foreach ($json["relations"] as $relation) {
                if ($model["name"] == $relation["second"]) {
                    $models_dependency[$model["name"]]->push($relation["first"]);
                }
            }
        }
        $end_loop = false;

        while (!$end_loop) {
            $index = 0;
            foreach ($json["models"] as $model) {
                $modelsIndxes = array();
                foreach ($models_dependency[$model["name"]] as $model_dep) {
                    array_push($modelsIndxes, $json["models"]->pluck("name")->search($model_dep));
                }
                if (count($modelsIndxes) > 0) {
                    if ($index < max($modelsIndxes)) {
                        $input = $json["models"]->toArray();
                        CrudService::moveElement($input, $index, max($modelsIndxes));
                        $json["models"] = collect($input);
                        break;
                    } else {
                        $index += 1;
                    }
                } else {
                    $index += 1;
                }
                if (($index == (count($json["models"]) - 1))) {
                    $end_loop = true;
                    break;
                }
            }
        }
        // Log::info(var_export($json["models"], 1));
    }

    static function moveElement(&$array, $a, $b)
    {
        $out = array_splice($array, $a, 1);
        array_splice($array, $b, 0, $out);
    }
}
