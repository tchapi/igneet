<?php

namespace meta\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use meta\AdminBundle\Stats\Stats;

class AdminController extends Controller
{

    public function homeAction()
    {

      return new Response('home');

    }


    /* ********************************************************************* */
    /*                           Non-routed actions                          */
    /*                     are NOT subject to Pre-execute                    */
    /* ********************************************************************* */

    public function currentUserMenuAction()
    {
        return $this->render('metaAdminBundle:Default:_menu.html.twig');
    }

}
