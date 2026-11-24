<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DiscussionController;
use Bramus\Router\Router;

return function (Router $router, DiscussionController $discussionCtrl, AuthController $authCtrl): void {
    $router->get('/', function () use ($discussionCtrl): void {
        $discussionCtrl->index();
    });

    $router->get('/discussions/(\d+)', function (string $id) use ($discussionCtrl): void {
        $discussionCtrl->show($id);
    });

    $router->post('/discussions/(\d+)/replies', function (string $id) use ($discussionCtrl): void {
        $discussionCtrl->storeReply($id);
    });

    $router->get('/auth', function () use ($authCtrl): void {
        $authCtrl->showAuthForm();
    });

    $router->post('/register', function () use ($authCtrl): void {
        $authCtrl->register();
    });

    $router->post('/login', function () use ($authCtrl): void {
        $authCtrl->login();
    });

    $router->post('/logout', function () use ($authCtrl): void {
        $authCtrl->logout();
    });

    $router->set404('/discussions/.*', function () use ($discussionCtrl): void {
        $discussionCtrl->show('invalid');
    });

    $router->set404(function (): void {
        http_response_code(404);
        echo '404 Not Found';
    });
};