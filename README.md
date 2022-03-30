# CakePHP Modules

This CakePHP plugin intends to provide a framework around modules.
Modules are a set of reusable classes that can be accessed from anywhere within the application.
The idea is to improve maintainability and keep code clean and modular.

## Installation

You can install this plugin into your CakePHP application using
[Composer](https://getcomposer.org):

```
composer require ndoubismarck/cakephp-modules
```

Load the plugin by adding the following statement in your project's `src/Application.php`:
```php
public function bootstrap(): void
{
    parent::bootstrap();
    $this->addPlugin('Modules');
}
```

## Baking modules
Bake a module by executing:
```
bin/cake bake module Example
```
This will create a module named `Example` in `modules/Example`

## Loading modules
The `ModuleAwareTrait` provides functionality for loading module classes as properties of the host object

For example:
1. In app controller
```php
use Cake\Controller\Controller;
use Modules\ModuleAwareTrait;

/**
 * @property ExampleModule $ExampleModule
 */
class AppController extends Controller
{
    use ModuleAwareTrait;
    
    public function initialize(): void
    {
        $this->loadModule('Example');
    }
}
```

2. In any other class
```php
use Modules\ModuleAwareTrait;

/**
 * @property ExampleModule $ExampleModule
 */
class MyClass
{
    use ModuleAwareTrait;
    
    public function __construct()
    {
        $this->loadModule('Example');
    }
}
```

