<?php

namespace Symfony\Bundle\FrameworkBundle;

use Symfony\Components\HttpKernel\LoggerInterface;
use Symfony\Components\EventDispatcher\EventDispatcher;
use Symfony\Components\EventDispatcher\Event;
use Symfony\Components\Routing\RouterInterface;
use Symfony\Components\HttpKernel\HttpKernelInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * RequestListener.
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class RequestListener
{
    protected $router;
    protected $logger;

    public function __construct(RouterInterface $router, LoggerInterface $logger = null)
    {
        $this->router = $router;
        $this->logger = $logger;
    }

    /**
     * Registers a core.request listener.
     *
     * @param EventDispatcher $dispatcher An EventDispatcher instance
     */
    public function register(EventDispatcher $dispatcher)
    {
        $dispatcher->connect('core.request', array($this, 'resolve'));
    }

    public function resolve(Event $event)
    {
        $request = $event->getParameter('request');

        if (HttpKernelInterface::MASTER_REQUEST === $event->getParameter('request_type')) {
            // set the context even if the parsing does not need to be done
            // to have correct link generation
            $this->router->setContext(array(
                'base_url'  => $request->getBaseUrl(),
                'method'    => $request->getMethod(),
                'host'      => $request->getHost(),
                'is_secure' => $request->isSecure(),
            ));
        }

        if ($request->attributes->has('_controller')) {
            return;
        }

        if (false !== $parameters = $this->router->match($request->getPathInfo())) {
            if (null !== $this->logger) {
                $this->logger->info(sprintf('Matched route "%s" (parameters: %s)', $parameters['_route'], str_replace("\n", '', var_export($parameters, true))));
            }

            $request->attributes->replace($parameters);
        } elseif (null !== $this->logger) {
            $this->logger->err(sprintf('No route found for %s', $request->getPathInfo()));
        }
    }
}
