<?php

namespace mindplay\session;

/**
 * A session storage implementation for integration testing.
 */
class MockSessionStorage implements SessionStorage
{
    /**
     * @var array
     */
    public $data = array();

    /**
     * @var string
     */
    public $namespace;

    public function __construct($namespace)
    {
        $this->namespace = $namespace;
    }

    public function get($name)
    {
        return @$this->data[$name];
    }

    public function set($name, $value)
    {
        if ($value === null) {
            unset($this->data[$name]);
        } else {
            $this->data[$name] = $value;
        }
    }

    public function clear()
    {
        $this->data = array();
    }
}
