<?php


namespace Modules;


use ArrayAccess;

/**
 * Interface ModuleInterface
 * @package Modules
 */
interface ModuleInterface
{

    /**
     * @return void
     */
    function initialize();

    /**
     * @param $func
     * @param mixed ...$params
     * @return array|ArrayAccess|mixed|null
     */
    function call($func, ...$params);

}
