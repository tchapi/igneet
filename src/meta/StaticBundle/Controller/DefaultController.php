<?php

namespace meta\StaticBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function staticAction($template)
    {
        return $this->render('metaStaticBundle:Default:' . $template . '.html.twig');
    }
}
