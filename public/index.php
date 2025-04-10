    <?php

use App\Handler\GetBlobHandler;
use App\Handler\GetContainerHandler;
use App\Handler\GetStorageHandler;
use App\Handler\PutContainerHandler;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Create the Container builder.
$containerBuilder = new ContainerBuilder();

// Add service definitions to the Container.
    $containerBuilder->addDefinitions(include __DIR__ . '/../config/container.php');

    //Build the Container instance.
    $container = $containerBuilder->build();

    // Set Container into Factory before create a new App instance.
AppFactory::setContainer($container);

/**
 * Instantiate App
 *
 * In order for the factory to work you need to ensure you have installed
 * a supported PSR-7 implementation of your choice e.g.: Slim PSR-7 and a supported
 * ServerRequest creator (included with Slim PSR-7)
 */
$app = AppFactory::create();

/**
  * The routing middleware should be added earlier than the ErrorMiddleware
  * Otherwise exceptions thrown from it will not be handled by the middleware
  */
$app->addRoutingMiddleware();

/**
 * Add Error Middleware
 *
 * @param bool                  $displayErrorDetails -> Should be set to false in production
 * @param bool                  $logErrors -> Parameter is passed to the default ErrorHandler
 * @param bool                  $logErrorDetails -> Display error details in error log
 * @param LoggerInterface|null  $logger -> Optional PSR-3 Logger  
 *
 * Note: This middleware should be added last. It will not handle any exceptions/errors
 * for middleware added after it.
 */
$settings = $container->get('settings');
$errorMiddleware = $app->addErrorMiddleware($settings['displayErrorDetails'], $settings['logErrors'], $settings['logErrorDetails']);

// Define app routes
$app->get('/', GetStorageHandler::class);
$app->get('/{container}', GetContainerHandler::class);
$app->put('/{container}', PutContainerHandler::class);
$app->get('/{container}/[{blob:.+}]', GetBlobHandler::class);

// Run app
$app->run();