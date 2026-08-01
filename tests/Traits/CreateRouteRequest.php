<?php

namespace Tests\Traits;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

trait CreateRouteRequest
{
    public function createRequestWithRouteParameters(array $parameters): Request
    {
        $request = Request::create('/test', 'POST');
        $request->headers->set('Accept', 'application/json');
        $route = new Route(['POST'], '/test', fn () => null);
        $route->bind($request);

        foreach ($parameters as $name => $value) {
            $route->setParameter($name, $value);
        }

        $request->setRouteResolver(fn () => $route);

        return $request;
    }
}
