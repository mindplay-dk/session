mindplay/session
================

This class implements a type-safe session object container.

It does not attempt to hijack the PHP session lifecycle - it only provides a
type-safe means of storing serialized objects in session variables; you are
still in charge of e.g. starting the session with session_start() etc.

Example:

    use mindplay\session\SessionContainer;

    class Cart
    {
        /** @var int */
        public $user_id;

        /** @var int[] */
        public $product_ids = array();
    }

    $session = new SessionContainer();

    // add some products to the Cart:

    $session->update(
        function (Cart $cart) {
            $cart->product_ids[] = 777;
            $cart->product_ids[] = 555;
        }
    );

    // commit session container contents to session variables:

    $session->commit();

    // empty the cart:

    $session->update(
        function (Cart $cart) use ($session) {
            $session->remove($cart);
        }
    );

This approach gives you type-hinted closures whenever you're working with session
state of any sort, which is great for IDE support and code comprehension in general.
