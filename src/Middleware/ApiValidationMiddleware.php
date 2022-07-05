<?php
declare(strict_types=1);

namespace Hyperf\Apidog\Middleware;

use Hyperf\Di\Annotation\Inject;
use App\Service\LogService;
use FastRoute\Dispatcher;
use Hyperf\Apidog\Exception\ApiDogException;
use Hyperf\Apidog\Validation\ValidationApi;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\HttpServer\CoreMiddleware;
use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiValidationMiddleware extends CoreMiddleware
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    protected $validationApi;

    /**
     * @Inject()
     * @var LogService
     */
    protected LogService $logService;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request, ValidationApi $validation)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
        $this->validationApi = $validation;
        parent::__construct($container, 'http');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->logService->log("Hyperf\Apidog\Middleware\ApiValidationMiddleware->process begin");

        //$response = Context::get(ResponseInterface::class);
        $this->response = $this->response->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Headers', 'DNT,Keep-Alive,User-Agent,Cache-Control,Content-Type,Authorization,X-Requested-With,X-Token,user-type')
            ->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS');
        Context::set(ResponseInterface::class, $this->response);

        $this->logService->log("Hyperf\Apidog\Middleware\ApiValidationMiddleware->this->response",[$this->response]);


        $uri = $request->getUri();
        $routes = $this->dispatcher->dispatch($request->getMethod(), $uri->getPath());
        if ($routes[0] !== Dispatcher::FOUND) {
            return $handler->handle($request);
        }

        // do not check Closure
        if ($routes[1]->callback instanceof \Closure) {
            $this->logService->log("Hyperf\Apidog\Middleware\ApiValidationMiddleware->process do not check Closure end");
            return $handler->handle($request);
        }

        [$controller, $action] = $this->prepareHandler($routes[1]->callback);

        $result = $this->validationApi->validated($controller, $action);
        if ($result !== true) {
            $config = $this->container->get(ConfigInterface::class);
            $exceptionEnable = $config->get('apidog.exception_enable', false);
            if ($exceptionEnable) {
                $fieldErrorMessage = $config->get('apidog.field_error_message', 'message');
                throw new ApiDogException($result[$fieldErrorMessage]);
            }
            $httpStatusCode = $config->get('apidog.http_status_code', 400);
            return $this->response->json($result)->withStatus($httpStatusCode);
        }
        $this->logService->log("Hyperf\Apidog\Middleware\ApiValidationMiddleware->process end");
        return $handler->handle($request);
    }
}
