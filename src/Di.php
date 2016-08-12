<?php
/**
 * Created by PhpStorm.
 * User: allowing
 * Date: 2016/8/12
 * Time: 21:25
 */

namespace allowing\core;


use Exception;
use ReflectionClass;

class Di implements DiInterface
{
    private $_depend = [];

    private $_dependSingleton = [];

    private $_singletonCache = [];

    public function set($class, $constructorArgs = [])
    {
        $this->_depend[$class] = $constructorArgs;
        if (isset($this->_dependSingleton[$class])) {
            unset($this->_dependSingleton[$class]);
        }
        return $this;
    }

    public function setSingleton($class, $constructorArgs = [])
    {
        $this->_dependSingleton[$class] = $constructorArgs;
        if (isset($this->_depend[$class])) {
            unset($this->_depend[$class]);
        }
        return $this;
    }

    /**
     * @param $class
     * @param array $constructorArgs
     * @return object
     * @throws Exception
     */
    public function get($class, $constructorArgs = [])
    {
        $refClass = new ReflectionClass($class);

        if ($refClass->isInterface()) {
            $implClassName = $refClass->getName();
            if (!isset($this->_depend[$implClassName])) {
                throw new Exception("Not found implement: $implClassName");
            }
            $refClass = new ReflectionClass($this->_depend[$implClassName]);
        }

        $wantGetClassName = $refClass->getName();

        if (
            isset($this->_dependSingleton[$wantGetClassName]) &&
            isset($this->_singletonCache[$wantGetClassName])
        ) {
            return $this->_singletonCache[$wantGetClassName];
        }

        $wantGetObj = null;
        $constructor = $refClass->getConstructor();
        if ($constructor && $params = $constructor->getParameters()) {
            $callParams = [];

            foreach ($params as $position => $param) {
                $dependClass = $param->getClass();

                if ($dependClass instanceof ReflectionClass) {
                    $dependClassName = $dependClass->getName();

                    if (isset($this->_depend[$dependClassName])) {
                        $callParams[$position] = $this->get($dependClassName, $this->_depend[$dependClassName]);
                    } elseif (isset($this->_dependSingleton[$dependClassName])) {
                        $callParams[$position] = $this->get($dependClassName, $this->_dependSingleton[$dependClassName]);
                    } else {
                        $callParams[$position] = $this->get($dependClassName);
                    }

                } else {
                    $paramName = $param->getName();
                    if (isset($constructorArgs[$paramName])) {
                        $callParams[$position] = $constructorArgs[$paramName];
                    } elseif (
                        isset($this->_depend[$wantGetClassName]) &&
                        isset($this->_depend[$wantGetClassName][$paramName])
                    ) {
                        $callParams[$position] = $this->_depend[$wantGetClassName][$paramName];
                    } elseif (
                        isset($this->_dependSingleton[$wantGetClassName]) &&
                        isset($this->_dependSingleton[$wantGetClassName][$paramName])
                    ) {
                        $callParams[$position] = $this->_dependSingleton[$wantGetClassName][$paramName];
                    } else {
                        $callParams[$position] = $param->getDefaultValue();
                    }
                }
            }

            $wantGetObj = $refClass->newInstanceArgs($callParams);
        } else {
            $wantGetObj = $refClass->newInstance();
        }

        if (isset($this->_dependSingleton[$wantGetClassName])) {
            $this->_singletonCache[$wantGetClassName] = $wantGetObj;
        }
        return $wantGetObj;
    }
}