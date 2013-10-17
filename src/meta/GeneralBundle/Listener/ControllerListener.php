<?php
namespace meta\GeneralBundle\Listener;
 
use Symfony\Component\EventDispatcher\Event,
    Symfony\Component\HttpKernel\HttpKernelInterface,
    Symfony\Component\HttpKernel\Event\FilterControllerEvent,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse;

/*
    Reference : http://blog.hio.fr/2012/04/17/symfony2-preexecute-controller.html
    Docs : http://symfony.com/doc/master/cookbook/event_dispatcher/before_after_filters.html
*/

class ControllerListener {
 
    public function onCoreController(FilterControllerEvent $event) {

        // if(HttpKernelInterface::MASTER_REQUEST == $event->getRequestType()) {

        $_controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure. This is not usual in Symfony2 but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($_controller)) {
            return;
        }

        if(isset($_controller[0])) {
            $controller = $_controller[0];
            if(method_exists($controller, 'preExecute')) {

                $response = $controller->preExecute($event->getRequest());

                if ($response instanceof Response || $response instanceof RedirectResponse){
                    /* SO DIRTYYYYYY */
                    /* http://stackoverflow.com/a/16167953/1741150 */
                    $event->setController(function() use ($response) {
                        return $response;
                    });
                }

            }
        }

        // }

    }
}