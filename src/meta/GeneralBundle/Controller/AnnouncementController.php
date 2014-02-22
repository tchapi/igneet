<?php

namespace meta\GeneralBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use meta\AdminBundle\Entity\Announcement;

class AnnouncementController extends Controller
{
  
  public function getAnnouncementsAction(Request $request)
  {

      $repository = $this->getDoctrine()->getRepository('metaAdminBundle:Announcement');
      $announcements = $repository->findAnnouncementsForUser($this->getUser());

      if (count($announcements) > 0) {

        return $this->render('metaGeneralBundle:Default:announcements.html.twig', array('announcements' => $announcements));

      } else {
        
        return new Response();

      }

  }

}
