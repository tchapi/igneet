<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class ResourceController extends BaseController
{

    /*  ####################################################
     *                        RESOURCES
     *  #################################################### */

    public function showResourcesAction($slug, $page)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Base:showRestricted', array('slug' => $slug));

        return $this->render('metaStandardProjectProfileBundle:Resource:showResources.html.twig', 
            array('base' => $this->base));
    }

}
