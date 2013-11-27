<?php

namespace meta\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use meta\AdminBundle\Stats\Stats;

class CommunitiesController extends Controller
{
    public function listAction()
    {

        $communitiesRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
        $communities = $communitiesRepository->findAll();

        return $this->render('metaAdminBundle:Communities:list.html.twig', array("communities" => $communities));

    }

    public function extendAction(Request $request)
    {

        $uid = $request->request->get('pk');
        $value = $request->request->get('value');

        // Translate to ID
        $id = $this->container->get('uid')->fromUId($uid);
        
        $communitiesRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
        $community = $communitiesRepository->findOneById($id);

        if ($community && strtotime($value) != FALSE ){

          $community->setValidUntil(new \DateTime($value));
          $em = $this->getDoctrine()->getManager();
          $em->flush();

          return new Response();

        } else {

          return new Response("Community does not exist, or wrong date format", 400);
        }

    }

}
