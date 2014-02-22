<?php

namespace meta\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use meta\AdminBundle\Entity\Announcement,
    meta\AdminBundle\Form\Type\AnnouncementType;

class AnnouncementController extends Controller
{

    public function listAction()
    {

        $repository = $this->getDoctrine()->getRepository('metaAdminBundle:Announcement');
        $announcements = $repository->findAll();

        $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
        $totalUsers = count($userRepository->findBy(array('deleted_at' => null)));

        return $this->render('metaAdminBundle:Announcements:list.html.twig', array( 'announcements' => $announcements, 'totalUsers' => $totalUsers ));
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

    /*
     * Create a form for edition of an announcement
     */
    public function editAction(Request $request, $uid)
    {

        $repository = $this->getDoctrine()->getRepository('metaAdminBundle:Announcement');
        $announcement = $repository->findOneById($this->container->get('uid')->fromUId($uid));
        $form = $this->createForm(new AnnouncementType(), $announcement);

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {

                $em = $this->getDoctrine()->getManager();
                
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('announcement.saved')
                );

                return $this->redirect($this->generateUrl('a_announcements'));
           
            } else {
               
               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );

            }

        }

        return $this->render('metaAdminBundle:Announcements:edit.html.twig', array('form' => $form->createView(), 'uid' => $uid));

    }

    /*
     * Delete an announcement
     */
    public function deleteAction(Request $request, $uid)
    {

        $repository = $this->getDoctrine()->getRepository('metaAdminBundle:Announcement');
        $announcement = $repository->findOneById($this->container->get('uid')->fromUId($uid));

        if ($announcement) {
            
            $em = $this->getDoctrine()->getManager();
            $em->remove($announcement);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('announcement.deleted')
            );
        }

        return $this->redirect($this->generateUrl('a_announcements'));
   
    }


    /*
     * A user has closed an announcement
     */
    public function statAction($uid)
    {

        $repository = $this->getDoctrine()->getRepository('metaAdminBundle:Announcement');
        $announcement = $repository->findOneById($this->container->get('uid')->fromUId($uid));

        $authenticatedUser = $this->getUser();

        if ($announcement) {
            
            if (!$authenticatedUser->getViewedAnnouncements()->contains($announcement)) {
                $authenticatedUser->addViewedAnnouncement($announcement);
            }
            $em = $this->getDoctrine()->getManager();
            $em->flush();

        }

        return new Response(json_encode("OK"), 200, array('Content-Type'=>'application/json'));

    }

}
