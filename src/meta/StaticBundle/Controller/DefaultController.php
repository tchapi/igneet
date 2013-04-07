<?php

namespace meta\StaticBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('metaStaticBundle:Default:index.html.twig', array('name' => $name));
    }
}
