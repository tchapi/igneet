<?php

namespace meta\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request;

use meta\AdminBundle\Stats\Stats;

class CommunitiesController extends Controller
{
    public function listAction()
    {

        $communitiesRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
        $communities = $communitiesRepository->findAll();

        return $this->render('metaAdminBundle:Communities:list.html.twig', array("communities" => $communities));

    }
}
