<?php

namespace mindplay\session;

use Closure;

/**
 * This defines the consumer-facing portion of the SessionService API.
 *
 * You can type-hint against this interface in controllers, where you most likely
 * don't want somebody calling e.g. commit()
 */
interface SessionContainer
{
    /**
     * Access one or more session model objects in this container.
     *
     * @param Closure $func function(MyModel $object, ...) : mixed|void
     *
     * @return mixed|void return value from the called function (if any)
     */
    public function update(Closure $func);

    /**
     * Remove a session model object from this session container.
     *
     * Note that the change is not effective until you call commit()
     *
     * @param string|object $model session model object or type (e.g. MyModel::class)
     *
     * @return void
     */
    public function remove($model);

    /**
     * Destroy all objects in this session store.
     *
     * Note that the change is not effective until you call commit()
     *
     * @return void
     */
    public function clear();
}
