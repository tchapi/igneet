<?php

namespace meta\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request;

use meta\AdminBundle\Entity\Announcement,
    meta\AdminBundle\Form\Type\AnnouncementType;

class AnnouncementController extends Controller
{

    public function listAction()
    {

        $repository = $this->getDoctrine()->getRepository('metaAdminBundle:Announcement');
        $announcements = $repository->findAll();

        return $this->render('metaAdminBundle:Announcements:list.html.twig', array( 'announcements' => $announcements ));
    }

   /*
     * Create a form for a new announcement AND process result when POSTed
     */
    public function createAction(Request $request)
    {

        $announcement = new Announcement();
        $form = $this->createForm(new AnnouncementType(), $announcement);

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {

                $em = $this->getDoctrine()->getManager();
                $em->persist($announcement);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('announcement.created')
                );

                return $this->redirect($this->generateUrl('a_announcements' ));
           
            } else {
               
               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );

            }

        }

        return $this->render('metaAdminBundle:Announcements:create.html.twig', array('form' => $form->createView()));

    }
}
