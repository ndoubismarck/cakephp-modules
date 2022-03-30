<?php


namespace Modules;


/**
 * Trait ModuleAwareTrait
 * @package Modules
 */
trait ModuleAwareTrait
{
    /**
     * @var null|ModuleCollection
     */
    private $modules = null;

    /**
     * @var string
     */
    private $moduleSuffix = 'Module';

    /**
     * @param $name
     * @return mixed
     */
    public function moduleExists($name)
    {
        return $this->modules->exists($name);
    }

    /**
     * @param $name
     * @param array $config
     * @throws Exception\ModuleNotFoundException
     */
    public function loadModule($name, array $config = [])
    {
        if ($this->modules === null) {
            $this->modules = new ModuleCollection($this->moduleSuffix);
        }
        if ($this->moduleExists($name)) {
            $moduleClass = $this->modules->get($name);
        } else {
            $moduleClass = $this->modules->load($name, $config);
        }
        $this->{basename($moduleClass->name) . $this->moduleSuffix} = $moduleClass;
    }
}
