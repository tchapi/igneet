<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class DefaultController extends BaseController
{

    /*  ####################################################
     *                    PROJECT LIST
     *  #################################################### */

    public function listAction($max)
    {

        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');

        $standardProjects = $repository->findRecentlyCreatedStandardProjects($max);

        return $this->render('metaStandardProjectProfileBundle:Default:list.html.twig', array('standardProjects' => $standardProjects));

    }


    /*  ####################################################
     *                   WATCH / UNWATCH
     *  #################################################### */

    public function watchAction($slug)
    {

        $authenticatedUser = $this->getUser();

        // The actually authenticated user now watches the project with $slug
        if ($authenticatedUser) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');
            $standardProject = $repository->findOneBySlug($slug);

            if ( !($authenticatedUser->isWatching($standardProject)) ){

                $authenticatedUser->addProjectsWatched($standardProject);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'You are now watching '.$standardProject->getName().'.'
                );

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'You are already watching '.$standardProject->getName().'.'
                );

            }

        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
    }

    public function unwatchAction($slug)
    {
        $authenticatedUser = $this->getUser();

        // The actually authenticated user now follows $user if they are not the same
        if ($authenticatedUser) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');
            $standardProject = $repository->findOneBySlug($slug);

            if ( $authenticatedUser->isWatching($standardProject) ){

                $authenticatedUser->removeProjectsWatched($standardProject);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'You are not watching '.$standardProject->getName().' anymore.'
                );

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'You are not watching '.$standardProject->getName().'.'
                );

            }

        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
    }

}
