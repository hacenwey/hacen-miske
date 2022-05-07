Laravel Code Generator
A simple Laravel library that allows you to create crud operations with a single command

Installation
composer require hacen-miske/code-generator
Features
Controller (with all the code already written)
Model
Migration
Requests
Routes
Enable the package (Optional)
This package implements Laravel auto-discovery feature. After you install it the package provider and facade are added automatically for laravel >= 5.5.

Configuration
Publish the configuration file

This step is required

php artisan vendor:publish --provider="HacenMiske\\CodeGenerator\\CodeGeneratorServiceProvider"
Usage
After publishing the configuration file just run the below command

php artisan crud:generate
Just it, Now all of your Model Controller, Migration, routes and Request will be created automatically with all the code required for basic crud operations
