<?php


namespace Modules;


use ArrayAccess;
use Cake\Utility\Hash;

/**
 * Class BaseModule
 * @package Modules
 */
class BaseModule implements ModuleInterface
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var ModuleCollection
     */
    private $moduleCollection;

    /**
     * @param array|null $config
     * @param $moduleCollection ModuleCollection
     */

    public function __construct($config, $moduleCollection)
    {
        $this->name = Hash::get($config, 'name');
        $this->moduleCollection = $moduleCollection;
    }

    /**
     *
     */
    public function initialize()
    {

    }

    /**
     * @param $func
     * @param mixed ...$params
     * @return array|ArrayAccess|mixed|null
     */
    public function call($func, ...$params)
    {
        return call_user_func_array([$this, $func], $params);
    }

    /**
     * @param $name
     * @return ModuleInterface
     * @throws Exception\ModuleNotFoundException
     */
    protected function module($name): ModuleInterface
    {
        if (!$this->moduleCollection->exists($name)) {
            $this->moduleCollection->load($name);
        }
        return $this->moduleCollection->get($name);
    }

}
