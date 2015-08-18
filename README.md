# mindplay/session

This library implements a type-hinted container for session model objects.

This approach gives you type-hinted closures whenever you're working with session
state of any sort, which is great for IDE support and code comprehension in general.

[![Build Status](https://travis-ci.org/mindplay-dk/session.svg?branch=master)](https://travis-ci.org/mindplay-dk/session)

[![Code Coverage](https://scrutinizer-ci.com/g/mindplay-dk/session/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/session/?branch=master)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mindplay-dk/session/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/session/?branch=master)

### Usage

Note that `SessionContainer` does not attempt to control the PHP session lifecycle - it only provides a
type-safe means of storing serialized objects in session variables; you are still in charge of e.g.
starting the session with `session_start()`, etc.

#### Setting up

Let's assume you have a session model like this:

```PHP
class Cart
{
    /** @var int */
    public $user_id;

    /** @var int[] */
    public $product_ids = array();
}
```

In a real project, you probably want to use a dependency injection container or some other
means of centrally managing your `SessionContainer` instance.

At the end of your request cycle (centrally, e.g. after dispatching a controller, but before
sending the response), you must call `commit()` to store the session data:

```PHP
// commit session container contents to session variables:

$session->commit();
```

This ensures you don't have partial changes made to session variables in case of errors.
If you don't care about transactional sessions and want changes committed automatically,
you can register a shutdown function, for example:

```PHP
register_shutdown_function(function () use ($session) {
    $session->commit();
});
```

#### Working with the session model container

In the following examples, for simplicty, we'll assume your session container is a global variable:

```PHP
use mindplay\session\SessionContainer;

$session = new SessionContainer();
```

To access/update a session model object, pass a type-hinted closure to the `update()` method:

```PHP
// add some products to the Cart:

$session->update(
    function (Cart $cart) {
        $cart->product_ids[] = 777;
        $cart->product_ids[] = 555;
    }
);
```

The `update()` method will construct `Cart` for you - it's therefore important to note
that session model classes must always have an empty constructor.

You can take values out of a container as well:

```PHP
$cart = $session->update(function (Cart $cart) {
    return $cart;
});
```

But do note that it's generally not very good practice to take session model
objects out of the container, as this blurs the fact that you're making changes
to session state - the call to `update()` clarifies what you're doing.

To remove a session model object:

```PHP
// empty the cart:

$session->remove(Cart::class);
```

If you have a reference to the session model object, `remove()` will also accept that.

##### Optional session models

If you don't know if a session model has been constructed yet, and you want to avoid
creating an empty session model, you can use a default `null` parameter in the closure:

```PHP
$session->update(
    function (Cart $cart = null) {
        if ($cart) {
            // ...
        }
    }
);
```

##### Multiple models in one call

If you need two (or more) session model objects at the same time, just ask for them:

```PHP
$session->update(function (User $user, Cart $cart) {
    // ...
});
```

##### Remove all session models

To remove all session models, call the `clear()` method.

Note that the session models are not removed from underlying storage until `commit()`
is called.
