<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\DiscussionController;
use App\Core\Database;
use App\Models\CommentModel;
use App\Models\DiscussionModel;
use App\Models\UserModel;
use Bramus\Router\Router;

$config = require __DIR__ . '/../config/config.php';

$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pdo = Database::getConnection($config['db']);

$mustache = new Mustache_Engine([
    'loader'          => new Mustache_Loader_FilesystemLoader(__DIR__ . '/../src/Views'),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/../src/Views'),
    'escape'          => static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
]);

$userModel       = new UserModel($pdo);
$discussionModel = new DiscussionModel($pdo);
$commentModel    = new CommentModel($pdo);

$discussionCtrl = new DiscussionController($discussionModel, $commentModel, $mustache, $config['display']);
$authCtrl       = new AuthController($userModel, $mustache);

$router = new Router();
$registerRoutes = require __DIR__ . '/../config/routes.php';
$registerRoutes($router, $discussionCtrl, $authCtrl);

$router->run();