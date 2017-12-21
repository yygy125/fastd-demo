<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2017
 *
 * @see      https://www.github.com/janhuang
 * @see      http://www.fast-d.cn/
 */

namespace Middleware;


use FastD\Middleware\DelegateInterface;
use FastD\Middleware\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DemoMiddleware extends Middleware
{

    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $next
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request, DelegateInterface $next)
    {
        // TODO: Implement handle() method.
    }
}