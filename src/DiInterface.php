<?php
/**
 * Created by PhpStorm.
 * User: allowing
 * Date: 2016/8/12
 * Time: 21:24
 */

namespace allowing\core;


interface DiInterface
{
    public function set($class, $constructorArgs = []);

    public function setSingleton($class, $constructorArgs = []);

    public function get($class, $constructorArgs = []);
}