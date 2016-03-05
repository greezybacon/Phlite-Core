The logging module is based on Monolog, but is focused on better integration
with frameworks. It is designed so that handlers can be associated with higher
levels of a logging framework and affect all sub-levels. For instance, a
handler can be associated with the logger `phlite` and will automatically
handle all log events posted to the `phlite` logger and all its children.
Therefore, things logged to `phlite.db` and `phlite.web` will be handled by
handlers attached to the `phlite` logger.

Namespaces in the Phlite logging system are dotted. Parent and child loggers
are established based on the dots in their name.

### Getting a Logger
    use Phlite\Logging\Registry;
    $logger = Registry::getLogger('myproject');

If the log does not yet exist, it is created automatically. For frameworks, the
current namespace can also be used. It is silently converted to a lowercase,
dotted name.

    $logger = Registry::getLogger(__NAMESPACE__, true);

### Parents and children
Building off the namespace idea, loggers for child namespaces can be accessed
via `getChild`, and from the child namespaces can access the parent logger with
`getParent`. Doing so isolates the logging system from the project's namespace
and will continue to work if the project's namespace is refactored.

### Caveats
Unlike Monlog 1.x, Phlite does not automatically add a handler to write things
to STDOUT. However, unlike Monolog, if you would like everything to be logged
via a single handler, a single handler can be associated with the root logger.

    $root = Registry::getRoot();
    $root->pushHandler(new Monolog\Handler\StreamHandler('php://stderr'));
