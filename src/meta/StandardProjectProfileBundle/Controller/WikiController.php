<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class WikiController extends BaseController
{

    /*  ####################################################
     *                          WIKI
     *  #################################################### */

    public function showWikiHomeAction($slug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Base:showRestricted', array('slug' => $slug));

        return $this->render('metaStandardProjectProfileBundle:Wiki:showWiki.html.twig', 
            array('base' => $this->base));
    }

}
