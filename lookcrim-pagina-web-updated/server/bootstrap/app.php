<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

// Support per-environment .env files (e.g. .env.staging) for Artisan and HTTP.
// Priority: APP_ENV env var, then Artisan CLI --env option.
$environment = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: null;

if (!$environment && isset($_SERVER['argv']) && is_array($_SERVER['argv'])) {
    $argv = $_SERVER['argv'];
    foreach ($argv as $idx => $arg) {
        if (!is_string($arg)) {
            continue;
        }

        if (str_starts_with($arg, '--env=')) {
            $environment = substr($arg, strlen('--env='));
            break;
        }

        if ($arg === '--env' && isset($argv[$idx + 1]) && is_string($argv[$idx + 1])) {
            $environment = $argv[$idx + 1];
            break;
        }
    }
}

if (is_string($environment) && $environment !== '') {
    $envFile = '.env.'.$environment;
    $envPath = dirname(__DIR__).DIRECTORY_SEPARATOR.$envFile;
    if (is_file($envPath)) {
        $app->loadEnvironmentFrom($envFile);
    }
}

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
