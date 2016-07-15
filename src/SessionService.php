<?php

namespace mindplay\session;

use Closure;
use ReflectionFunction;

/**
 * This class implements a type-safe container for session model objects.
 *
 * Your session model classes must have empty constructors, and will be constructed for you.
 *
 * Session model objects are singletons - only one instance of each type of session object
 * can be kept in the same container - if you need multiple instances of something, you
 * should put those instances in an array in the session object itself.
 *
 * Session model classes must be compatible with `serialize()` and `unserialize()`.
 *
 * Keep as little information as possible in session objects - that is, do not keep domain
 * objects such as `User` in the container; instead, keep an active `$user_id` in a session
 * model separate from your domain model.
 */
class SessionService implements SessionContainer
{
    /**
     * @var SessionStorage storage implementation
     */
    protected $storage;

    /**
     * @var (object|null)[] map where type-name => object (or NULL, if the object has been removed)
     */
    protected $cache = array();

    /**
     * @var boolean flag for clearing storage at commit.
     */
    protected $clear_storage = false;

    /**
     * @param string|null|SessionStorage $storage session storage implementation; or a string to use native session
     *                                            storage and specify the root namespace; or NULL to use the class
     *                                            name as the default namespace.
     */
    public function __construct($storage = null)
    {
        $this->storage = $storage instanceof SessionStorage
            ? $storage
            : new NativeSessionStorage(is_string($storage) ? $storage : get_class($this));
    }

    /**
     * @inheritdoc
     */
    public function update(Closure $func)
    {
        $reflection = new ReflectionFunction($func);

        $params = $reflection->getParameters();

        $args = array();

        foreach ($params as $param) {
            $type = $param->getClass()->getName();

            if ($param->isDefaultValueAvailable()) {
                $args[] = $this->fetch($type) ?: $param->getDefaultValue();
            } else {
                $args[] = $this->fetch($type) ?: $this->create($type);
            }
        }

        return call_user_func_array($func, $args);
    }

    /**
     * @inheritdoc
     */
    public function remove($model)
    {
        $this->cache[is_object($model) ? get_class($model) : (string) $model] = null;
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->clear_storage = true;

        $this->cache = array_fill_keys(array_keys($this->cache), null);
    }

    /**
     * Commit any changes made to objects in this session container.
     */
    public function commit()
    {
        if ($this->clear_storage) {
            $this->storage->clear();
        }

        foreach ($this->cache as $type => $object) {
            $this->storage->set($type, $object);
        }

        $this->clear_storage = false;
    }

    /**
     * @param string $type fully-qualified class name
     *
     * @return object|null sesion model (or NULL, if undefined)
     */
    protected function fetch($type)
    {
        if (! isset($this->cache[$type])) {
            //After clear(), before commit(), fetch() behaves as if storage is empty.
            $this->cache[$type] = $this->clear_storage ? null : $this->storage->get($type);
        }

        return $this->cache[$type];
    }

    /**
     * @param string $type fully-qualified class name
     *
     * @return object created session model
     */
    protected function create($type)
    {
        $object = new $type();

        $this->cache[$type] = $object;

        return $object;
    }
}
