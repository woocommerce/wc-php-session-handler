# WooCommerce PHP Session Handler

This is an example session handling class which swaps out the default handler (which uses a combination of cookies and database tables) with a handler which only uses PHP Sessions.

## Replacing the default handler

WooCommerce has a filter which allows you to define which class is responsible for handling sessions named `woocommerce_session_handler`.

Hook a function or method into this filter to return your custom class name. In the case of this plugin, we're using a class named `SessionHandler` under the `WC\PHPSessionHandler` namespace.

## Building the session handler class

To build a custom session handler, your class should extend `WC_Session` which is WooCommerce's abstract session handler class.

```php
class Your_Session_Handler extends WC_Session {
    // ...
}
```

The abstract class has built in getters and setters which manipulate an array variable named `_data`. When this data array is updated, `_dirty` is set to true.

Therefore, in your class as long as you leave these methods in place you only need to store `_data` to your session when `_dirty` is true.

Other methods to extend include:

- `init()` - This is where you should init your session and load any data, as well as add any hooks that need to listen for WordPress events such as logout.
- `cleanup_sessions()` - If your session stores data in the database or filesystem, this method should cleanup expired data. The core WooCommerce session handler uses this to clear out rows from the sessions table. This plugin does not need it as sessions are cleaned up automatically.

Finally there is a `get_customer_id()` method which returns the `_customer_id` variable. This is an ID that can be generated for both logged in and logged out users so they can be tracked in the session. In this plugin, we're using it to setup unique nonces for logged out users. In WooCommerce core, it's also used to control the ID stored in the database so a user is associated with the correct session.

A barebones handler would look like this:

```php
class Your_Session_Handler extends WC_Session {

    public function init() {
        // Load data from disk/database/session here and set $this->_data
        // Set _customer_id here

        add_action( 'shutdown', array( $this, 'save_data' ) )
        add_action( 'wp_logout', array( $this, 'destroy_session' ) );
    }

    public function save_data() {
        if ( $this->_dirty ) {
            // Save data to disk/database/session here
        }
    }

    public function destroy_session() {
        // Remove data from disk/database/session here
    }

}
```