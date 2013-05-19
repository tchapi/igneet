<?php

namespace meta\StaticBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function homeAction()
    {
        return $this->render('metaStaticBundle:Static:home.html.twig');
    }

    public function contactAction(Request $request)
    {
        
        if(filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL)){ 

          // Sends mail to contact
          $message = \Swift_Message::newInstance()
              ->setSubject('Contact from igneet.com')
              ->setFrom($request->request->get('email'))
              ->setTo($this->container->getParameter('mailer_contact'))
              ->setBody(
                  "Name    : " . $request->request->get('name') . "\n" .
                  "Email   : " . $request->request->get('email') . "\n" .
                  "Message : " . "\n\n" . $request->request->get('message'));

          $this->get('mailer')->send($message);
        
          return new Response(1);

        } else {
        
          return new Response(0);

        }

    }

    public function termsAction()
    {
        return $this->render('metaStaticBundle:Static:terms.html.twig');
    }
}
