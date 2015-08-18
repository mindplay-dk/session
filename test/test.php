<?php

require __DIR__ . '/header.php';

use mindplay\session\MockSessionStorage;
use mindplay\session\NativeSessionStorage;
use mindplay\session\SessionContainer;
use mindplay\session\SessionStorage;

// TEST FIXTURES:

class User
{
    public $name;
}

class Cart
{}

// BOOTSTRAP FOR INTEGRATION TEST:

@session_destroy();

session_start();

// UNIT TEST:

header('Content-type: text/plain');

if (coverage()) {
    coverage('test')->filter()->addDirectoryToWhitelist(__DIR__ . '/src');
}

// STORAGE TEST:

function test_storage($type)
{
    test(
        "{$type} behavior",
        function () use ($type) {
            /** @var SessionStorage $storage */
            $storage = new $type('foo');

            $storage->set('a', 'b');

            eq($storage->get('a'), 'b', 'stores and returns the value');

            $storage->set('c', 'd');

            $storage->set('a', null);

            eq($storage->get('a'), null, 'removes keys for NULL values');

            eq($storage->get('c'), 'd', 'returns other values');

            $storage->clear();

            eq($storage->get('c'), null, 'can remove all data');
        }
    );
}

test_storage('mindplay\session\NativeSessionStorage');
test_storage('mindplay\session\MockSessionStorage');

// INTEGRATION TEST:

test(
    'Native session storage integration',
    function () {
        $NAME = 'session_test';

        $storage = new NativeSessionStorage($NAME);

        ok(!isset($_SESSION[$NAME]), 'pre-condition');

        $storage->set('a', 'b');

        eq($_SESSION[$NAME]['a'], serialize('b'), 'it sets the serialized session value');

        $storage->set('a', null);

        ok(!isset($_SESSION[$NAME]['a']), 'it removes the session value');

        $storage->set('a', 'x');

        eq($_SESSION[$NAME]['a'], serialize('x'), 'session has serialized contents');

        $storage->clear();

        ok(!isset($_SESSION[$NAME]), 'root session variable removed');
    }
);

test(
    'SessionContainer behavior and integration with SessionStorage',
    function () {
        $storage = new MockSessionStorage('foo');

        $container = new SessionContainer($storage);

        $user = $container->update(function (User $user) {
            $user->name = 'bob';
            return $user;
        });

        eq($user->name, 'bob', 'can create and update session model objects');

        $user_again = $container->update(function (User $user) {
            return $user;
        });

        eq($user, $user_again, 'it returns the same model instance');

        eq($storage->data, array(), 'it does not make changes to storage before commit()');

        $container->commit();

        eq($storage->data, array('User' => $user), 'it stores the session model object');

        $null = $container->update(function (Cart $null = null) {
            return $null;
        });

        eq($null, null, 'returns NULL for optional session model');

        $cart = $container->update(function (Cart $cart) {
            return $cart;
        });

        $not_null = $container->update(function (Cart $cart = null) {
            return $cart;
        });

        ok(!is_null($not_null), 'does not return NULL if model is present');

        $container->commit();

        eq($storage->data, array('User' => $user, 'Cart' => $cart), 'it stores another model object');

        $container->remove('User');

        $container->commit();

        $null = $container->update(function (User $null = null) {
            return $null;
        });

        eq($null, null, 'can remove model object by name');

        eq($storage->data, array('Cart' => $cart), 'object removed from underlying storage');

        $container->remove($user);

        $container->commit();

        $null = $container->update(function (User $null = null) {
            return $null;
        });

        eq($null, null, 'can remove model object by reference');

        $got_both = $container->update(function (User $user, Cart $cart) {
            return ($user instanceof User) && ($cart instanceof Cart);
        });

        ok($got_both, 'can get multiple model objects in the same call');

        $container->commit();

        eq(count($storage->data), 2, 'it stores both objects');

        $container->clear();

        eq(count($storage->data), 2, 'clear() does not commit to storage');

        $container->commit();

        eq($storage->data, array(), 'it removes everything from storage');
    }
);

// REPORTING:

if (coverage()) {
    $report = new PHP_CodeCoverage_Report_Text(10, 90, false, false);

    echo $report->process(coverage(), false);

    $report = new PHP_CodeCoverage_Report_Clover();

    $report->process(coverage(), __DIR__ . '/build/logs/clover.xml');
}

exit(status()); // exits with errorlevel (for CI tools etc.)
