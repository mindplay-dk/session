<?php

namespace mindplay\session;

/**
 * The default session storage implementation wrapping $_SESSION.
 */
class NativeSessionStorage implements SessionStorage
{
    /**
     * @var string
     */
    private $namespace;

    public function __construct($namespace)
    {
        $this->namespace = $namespace;
    }

    public function get($name)
    {
        $data = @$_SESSION[$this->namespace][$name];

        return $data === null ? null : $this->unserialize($data);
    }

    public function set($name, $value)
    {
        if ($value === null) {
            unset($_SESSION[$this->namespace][$name]);
        } else {
            $_SESSION[$this->namespace][$name] = $this->serialize($value);
        }
    }

    public function clear()
    {
        unset($_SESSION[$this->namespace]);
    }

    /**
     * @param $object
     *
     * @return string serialized object
     */
    protected function serialize($object)
    {
        return serialize($object);
    }

    /**
     * @param string $str serialized object
     *
     * @return object
     */
    protected function unserialize($str)
    {
        return unserialize($str);
    }
}
