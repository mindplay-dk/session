<?php

/** @var \Composer\Autoload\ClassLoader $autoloader */
$autoloader = require __DIR__ . '/vendor/autoload.php';
$autoloader->addPsr4('mindplay\session\\', __DIR__ . '/src');

use mindplay\session\SessionContainer;

// TEST FIXTURES:

class User
{
    public $name;
}

class Cart
{
    /** @var string[] */
    public $products = array();
}

// UNIT TEST:

@session_destroy();

session_start();

header('Content-type: text/plain');

if (coverage()) {
    coverage('test')->filter()->addDirectoryToWhitelist(__DIR__ . '/src');
}

test(
    'SessionContainer behaviors',
    function () {
        $session_name = 'SessionService_TEST';

        $session = new SessionContainer($session_name);

        ok(! isset($_SESSION[$session_name]), 'Session should be empty');

        $session->update(
            function(User $user, Cart $cart) {
                $user->name = 'Rasmus';

                $cart->products[] = 'Milk';
                $cart->products[] = 'Cookies';
            }
        );

        ok(! isset($_SESSION[$session_name]), 'Session has not yet been committed');

        $session->commit();

        eq(count($_SESSION[$session_name]), 2, 'Session should contain 2 objects');

        unset($session);

        $session = new SessionContainer($session_name);

        $session->update(
            function(Cart $cart) use ($session) {
                $session->remove($cart); // remove Cart from session
            }
        );

        eq(count($_SESSION[$session_name]), 2, 'Session should contain 2 objects (not yet committed)');

        $session->commit();

        eq(count(array_filter($_SESSION[$session_name])), 1, 'Session should contain 1 object');

        $session->clear();

        ok(! isset($_SESSION[$session_name]), 'Session has been destroyed');
    }
);

if (coverage()) {
    $report = new PHP_CodeCoverage_Report_Text(10, 90, false, false);

    echo $report->process(coverage(), false);

    $report = new PHP_CodeCoverage_Report_Clover();

    $report->process(coverage(), 'build/logs/clover.xml');
}

exit(status()); // exits with errorlevel (for CI tools etc.)

// https://gist.github.com/mindplay-dk/4260582

/**
 * @param string   $name     test description
 * @param callable $function test implementation
 */
function test($name, $function)
{
    echo "\n=== $name ===\n\n";

    try {
        call_user_func($function);
    } catch (Exception $e) {
        ok(false, "UNEXPECTED EXCEPTION", $e);
    }
}

/**
 * @param bool   $result result of assertion
 * @param string $why    description of assertion
 * @param mixed  $value  optional value (displays on failure)
 */
function ok($result, $why = null, $value = null)
{
    if ($result === true) {
        echo "- PASS: " . ($why === null ? 'OK' : $why) . ($value === null ? '' : ' (' . format($value) . ')') . "\n";
    } else {
        echo "# FAIL: " . ($why === null ? 'ERROR' : $why) . ($value === null ? '' : ' - ' . format($value, true)) . "\n";
        status(false);
    }
}

/**
 * @param mixed  $value    value
 * @param mixed  $expected expected value
 * @param string $why      description of assertion
 */
function eq($value, $expected, $why = null)
{
    $result = $value === $expected;

    $info = $result
        ? format($value)
        : "expected: " . format($expected, true) . ", got: " . format($value, true);

    ok($result, ($why === null ? $info : "$why ($info)"));
}

/**
 * @param mixed $value
 * @param bool  $verbose
 *
 * @return string
 */
function format($value, $verbose = false)
{
    if ($value instanceof Exception) {
        return get_class($value)
        . ($verbose ? ": \"" . $value->getMessage() . "\"" : '');
    }

    if (! $verbose && is_array($value)) {
        return 'array[' . count($value) . ']';
    }

    if (is_bool($value)) {
        return $value ? 'TRUE' : 'FALSE';
    }

    return print_r($value, true);
}

/**
 * @param bool|null $status test status
 *
 * @return int number of failures
 */
function status($status = null)
{
    static $failures = 0;

    if ($status === false) {
        $failures += 1;
    }

    return $failures;
}

/**
 * @param string|null $text description (to start coverage); or null (to stop coverage)
 * @return PHP_CodeCoverage|null
 */
function coverage($text = null)
{
    static $coverage = null;
    static $running = false;

    if ($coverage === false) {
        return null; // code coverage unavailable
    }

    if ($coverage === null) {
        try {
            $coverage = new PHP_CodeCoverage;
        } catch (PHP_CodeCoverage_Exception $e) {
            echo "# Notice: no code coverage run-time available\n";
            $coverage = false;
            return null;
        }
    }

    if (is_string($text)) {
        $coverage->start($text);
        $running = true;
    } else {
        if ($running) {
            $coverage->stop();
            $running = false;
        }
    }

    return $coverage;
}
