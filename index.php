<?php

use App\Authorization;
use App\AuthorizationException;
use App\Database;
use App\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require __DIR__ . '/vendor/autoload.php';

$loader = new FilesystemLoader('templates');
$twig = new Environment($loader);

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$session = new Session();
$config = include_once 'config/database.php';

$database = new Database($config['dsn'], $config['username'], $config['password']);
$authorization = new Authorization($database, $session);

$sessionMiddleware = function (Request $request, RequestHandlerInterface $handler) use ($session) {
    $session->start();
    $response = $handler->handle($request);
    $session->save();
    return $response;
};
$app->add($sessionMiddleware);

$app->get('/', function (Request $request, Response $response) use ($twig, $session) {
    $body = $twig->render('index.twig', [
        'user' => $session->getData('user'),
    ]);
    $response->getBody()->write($body);
    return $response;
});


$app->get('/login', function (Request $request, Response $response) use ($twig, $session) {
    $body = $twig->render('login.twig', [
        'form' => $session->flush('form'),
        'message' => $session->flush('message'),
    ]);
    $response->getBody()->write($body);
    return $response;
});

$app->post('/login-post', function (Request $request, Response $response) use ($authorization, $session) {
    $params = (array) $request->getParsedBody();

    try {
        $authorization->login($params['email'], $params['password']);
    } catch (AuthorizationException $exception) {
        $session->setData('form', $params);
        $session->setData('message', $exception->getMessage());
        return $response->withHeader('Location', '/login')
            ->withStatus(302);
    }

    return $response->withHeader('Location', '/')
        ->withStatus(302);
});

$app->get('/logout', function (Request $request, Response $response) use ($session) {
    $session->setData('user', null);
    return $response->withHeader('Location', '/')
        ->withStatus(302);
});

$app->get('/register', function (Request $request, Response $response) use ($twig, $session) {
    $body = $twig->render('register.twig', [
        'message' => $session->flush('message'),
        'form' => $session->flush('form'),
    ]);
    $response->getBody()->write($body);
    return $response;
});

$app->post('/register-post', function (Request $request, Response $response) use ($authorization, $session) {
    $params = (array) $request->getParsedBody();

    try {
        $authorization->register($params);
    } catch (AuthorizationException $exception) {
        $session->setData('form', $params);
        $session->setData('message', $exception->getMessage());
        return $response->withHeader('Location', '/register')
            ->withStatus(302);
    }

    return $response->withHeader('Location', '/')
        ->withStatus(302);
});
$app->run();
