<?php

namespace meta\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use meta\AdminBundle\Stats\Stats,
    meta\GeneralBundle\Entity\Community\Community,
    meta\AdminBundle\Form\Type\CommunityType;

class CommunitiesController extends Controller
{
    /*
     * Helper to convert bytes into readable size format
     */
    private function convertReadable($bytes)
    {
        $suffixes = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f ", $bytes / pow(1024, $factor)) . (($factor==0)?"octets":(@$suffixes[$factor] . "o"));
    }

    public function listAction()
    {

        $communitiesRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
        $communities = $communitiesRepository->findAll();

        return $this->render('metaAdminBundle:Communities:list.html.twig', array("communities" => $communities));

    }

    public function editAction(Request $request, $uid)
    {

        $communitiesRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
        $community = $communitiesRepository->findOneById($this->container->get('uid')->fromUId($uid));

        $form = $this->createForm(new CommunityType(), $community);

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {

                $em = $this->getDoctrine()->getManager();
                
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('community.saved')
                );

                return $this->redirect($this->generateUrl('a_communities'));
           
            } else {
               
               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );

            }

        }

        return $this->render('metaAdminBundle:Communities:edit.html.twig', array('form' => $form->createView(), 'uid' => $uid));

    }
    
    public function storageAction(Request $request, $uid)
    {

        $communitiesRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
       
        if ($uid !== null) {
            $files = $communitiesRepository->findAllFilesInCommunity($this->container->get('uid')->fromUId($uid));
        } else {
            $files = $communitiesRepository->findAllFiles();
        }

        $detail = array();
        $total = 0;
        $count = 0;

        foreach ($files as $file) {
            $path = $file->getAbsoluteUrlPath();
            $size = filesize($path);

            $detail[] = array(
                'file' => $path,
                'size' => $size?$size:"DELETED"
                );

            $total += $size?$size:0;
            $count += $size?1:0;

        }

        return new Response(json_encode(array( 'details' => $detail, 'count' => $count, 'total' => $this->convertReadable($total) )), 200, array('Content-Type'=>'application/json'));
    }

    /*
     * List all files that are still present on the disk but that are not linked anymore in the app, 
     * because the project has been deleted
     */
    public function pruneAction(Request $request)
    {

        $communitiesRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
        $files = $communitiesRepository->findAllPrunableFiles();

        $detail = array();
        $saved = 0;

        foreach ($files as $file) {
            $path = $file->getAbsoluteUrlPath();
            if (file_exists($path)) {
                $detail[] = $path;
                $saved += filesize($path);
            }
        }

        return $this->render('metaAdminBundle:Default:prune.html.twig', array('details' => $detail, 'saved' => $this->convertReadable($saved)));

    }
}
