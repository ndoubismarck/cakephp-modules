<?php


namespace Modules;


use Cake\Utility\Hash;
use Modules\Exception\ModuleNotFoundException;

/**
 * Class ModuleCollection
 * @package Modules
 */
class ModuleCollection
{
    /**
     * @var string
     */
    private $suffix;
    
    /**
     * @var array
     */
    private $modules = [];

    /**
     * ModuleCollection constructor.
     * @param string $suffix
     */
    public function __construct($suffix = 'Module')
    {
        $this->suffix = $suffix;
    }

    /**
     * @param $name
     * @return ModuleInterface
     * @throws ModuleNotFoundException
     */
    public function get($name): ModuleInterface
    {
        $module = Hash::get($this->modules, $name);
        if ($module === null) {
            throw new ModuleNotFoundException('module does not exist');
        }
        return $module;
    }

    /**
     * @param $name
     * @return bool
     */
    public function exists($name): bool
    {
        return Hash::check($this->modules, $name);
    }

    /**
     * @param $name
     * @param array $config
     * @return ModuleInterface
     * @throws ModuleNotFoundException
     */
    public function load($name, array $config = []): ModuleInterface
    {
        $suffix = $this->suffix;
        $base = basename($name);
        $path = ROOT . DS . 'modules' . DS . $name . DS . 'src' . DS . $base . $suffix . '.php';
        $config += [
            'name' => $name,
            'file' => $path,
        ];
        $relPath = str_replace(ROOT . DS, '', $path);
        if (strpos($name, '\\') !== false) {
            $className = $name;
            if (!class_exists($className)) {
                throw new ModuleNotFoundException('module "' . $className . '" does not exist in ' . $relPath);
            }
            $moduleClass = new $name($config, $this);
        } else {
            $namespace = 'Modules/' . $name;
            $className = str_replace('/', '\\', $namespace) . '\\' . $base . $suffix;
            if (!file_exists($path)) {
                throw new ModuleNotFoundException('module "' . $relPath . '" does not exist');
            }
            if (!class_exists($className)) {
                throw new ModuleNotFoundException('module "' . $className . '" does not exist in ' . $relPath);
            }
            $moduleClass = new $className($config, $this);
        }
        $moduleClass->initialize();
        $this->modules[$name] = $moduleClass;
        return $moduleClass;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->modules;
    }
}
