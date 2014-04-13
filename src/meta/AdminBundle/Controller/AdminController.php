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

        return $this->render('metaAdminBundle:Default:home.html.twig');

    }

    public function changelogAction()
    {

        // Read changelog
        $log = file_get_contents($this->get('kernel')->getRootDir() . '/../web/CHANGELOG.txt', FILE_USE_INCLUDE_PATH);

        // FIX ME
        
        // Link to latest commit

        // Display last changes in files from last commit, github style

        return $this->render('metaAdminBundle:Default:changelog.html.twig', array( 'log' => $log ));

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
