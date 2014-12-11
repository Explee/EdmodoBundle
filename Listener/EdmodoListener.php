<?php

namespace Explee\EdmodoBundle\Listener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Session\Session;

class EdmodoListener
{
    protected $session;
    
    protected $formLoginCheckPath;

    public function __construct(Session $session, $formLoginCheckPath)
    {
        $this->session = $session;
        $this->formLoginCheckPath = $formLoginCheckPath;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (rawurldecode($request->getPathInfo()) === $this->formLoginCheckPath && !$request->hasPreviousSession()) {
            $request->cookies->set(session_name(), 'tmp');
            $request->setSession($this->session);
        }
    }
}