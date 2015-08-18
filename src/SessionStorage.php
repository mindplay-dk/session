<?php

namespace mindplay\session;

/**
 * This interface defines a means of storing session key/value pairs.
 */
interface SessionStorage
{
    /**
     * @param string $namespace root session-variable "namespace"
     */
    public function __construct($namespace);

    /**
     * @param string $key
     *
     * @return mixed|null data (or NULL, if no value exists)
     */
    public function get($key);

    /**
     * @param string $key
     * @param mixed|null $value data (or NULL to remove the key/value)
     *
     * @return void
     */
    public function set($key, $value);

    /**
     * @return void
     */
    public function clear();
}
