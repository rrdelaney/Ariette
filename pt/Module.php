<?php
namespace Pt;

use \Exception;

class Module {
    private $name;
    private $components;
    private $middleware;
    private $endware;

    public $init;

    public function __construct($name, $middleware, $endware) {
        $this->name = $name;
        $this->components = [];
        $this->middleware = $middleware;
        $this->endware = $endware;

        $this->init = false;
    }

    public function __toString() {
        if ($this->init === false) {
            return "0.Module $this->name";
        } else {
            return "Module $this->name";
        }
    }

    public function __get($name) {
        if (array_key_exists($name, $this->components)) {
            return $this->components[$name];
        } else if ($name === '__init__') {
            $this->component('__init__');
            return $this->components['__init__'];
        }

        throw new Exception("Cannot find Component $name in Module $this->name");
    }

    public function __call($name, $arguments) {
        if ($name == '__init__') {
            $this->init();
        } else if (array_key_exists($name, $this->components)) {
            return call_user_func_array($this->components[$name]->func, $arguments);
        } else {
            throw new Exception("Cannot find Component $name in Module $this->name");
        }
    }

    public function __invoke() {
        $this->init();
    }

    public function init() {
        $this->init = true;
        Pt::handle($this->component('__init__'), [], 'NOOP');
    }

    public function component($name, $deps=null, $func=null) {
        if ($name === '__init__' && $deps === null && $func === null) {
            if (array_key_exists('__init__', $this->components)) {
                return $this->components['__init__'];
            } else {
                $this->component('__init__', [], function($input) { return $input; });
                return $this->components['__init__'];
            }
        }

        if (array_key_exists($name, $this->components) && $deps === null) {
            return $this->components[$name];
        } else if (array_key_exists($name, $this->components)) {
            throw new Exception("Cannot redefine Component $name on Module $this->name");
        }

        if ($deps === null) {
            throw new Exception("Illegal declaration of Component!");
        }

        if ($func === null) {
            $func = $deps;
            $deps = [];
        }

        if ($name !== '__init__') {
            $deps = array_merge($this->middleware, $deps, $this->endware);
        }

        $c = new Component($this->name, $name, $deps, $func);
        $this->components[$name] = $c;

        return $this;
    }

    public function printComponents() {
        echo '| ', $this, PHP_EOL;
        foreach ($this->components as $c) {
            echo '|---> ', $c, PHP_EOL;
        }
    }
}
