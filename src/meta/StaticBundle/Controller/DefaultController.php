<?php

namespace meta\StaticBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function homeAction()
    {
        return $this->render('metaStaticBundle:Default:home.html.twig');
    }
}
