<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\StandardProjectProfileBundle\Entity\Resource,
    meta\GeneralBundle\Entity\Behaviour\Tag,
    meta\StandardProjectProfileBundle\Form\Type\ResourceType;

class ResourceController extends BaseController
{

    /*  ####################################################
     *                        RESOURCES
     *  #################################################### */

    public function showResourcesAction(Request $request, $slug, $page)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Base:showRestricted', array('slug' => $slug));

        $types = $this->container->getParameter('standardproject.resource_types');
        $providers = $this->container->getParameter('standardproject.resource_providers');

        $resource = new Resource();
        $form = $this->createForm(new ResourceType(), $resource);

        if ($request->isMethod('POST')) {

            $form->bind($request);

            $pattern = "|^http(s)?://[a-z0-9-]+(.[a-z0-9-_]+)*(:[0-9]+)?(/.*)?$|i";
            
            if ($form->isValid() && 
              ($request->files->get('resource[file]', null, true) != null || preg_match( $pattern, $resource->getUrl() ) == 1 ) ) {

                $this->base['standardProject']->addResource($resource);

                // Guess resource type and provider

                // Tries to guess platform from absolute url
                if ($request->files->get('resource[file]', null, true) == null) {

                    $resource->setProvider('other');

                    foreach ($providers as $provider => $provider_infos) {
                        if ($provider != 'other' && $provider != 'local' && preg_match($provider_infos['pattern'], $resource->getUrl())){
                            $resource->setProvider($provider);
                            break;
                        }
                    }

                    $guessedType = explode('.', $resource->getUrl());
                    $guessedType = $guessedType[count($guessedType)-1];

                } else {

                    $resource->setProvider('local');
                    $guessedType = $resource->getFile()->guessExtension();

                }

                // Guesses type
                if (isset($types[$guessedType])){
                    $resource->setType($guessedType);
                } else {
                    $resource->setType('other');
                }

                $em = $this->getDoctrine()->getManager();
                $em->persist($resource);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_add_resource', $this->base['standardProject'], array( 'resource' => array( 'routing' => 'resource', 'logName' => $resource->getLogName(), 'args' => $resource->getLogArgs()) ));

                $this->get('session')->setFlash(
                    'success',
                    'Your resource '.$resource->getTitle().' has successfully been added to the project '.$this->base['standardProject']->getName().'.'
                );

                return $this->redirect($this->generateUrl('sp_show_project_resources', array('slug' => $this->base['standardProject']->getSlug())));
           
            } else {
               
               $this->get('session')->setFlash(
                    'error',
                    'The information you provided does not seem valid.'
                );

            }
        }


        return $this->render('metaStandardProjectProfileBundle:Resource:showResources.html.twig', 
            array('base' => $this->base, 'types' => $types, 'providers' => $providers, 'form' => $form->createView()));
    }

    public function editResourceAction(Request $request, $slug, $id)
    {
  
        $this->fetchProjectAndPreComputeRights($slug, false, true);
        $error = null;

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:Resource');
            $resource = $repository->findOneById($id);

            $objectHasBeenModified = false;
            $em = $this->getDoctrine()->getManager();

            switch ($request->request->get('name')) {
                case 'title':
                    $resource->setTitle($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'tags':
                    $tagsAsArray = $request->request->get('value');

                    $resource->clearTags();

                    $tagRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Behaviour\Tag');
                    $existingTags = $tagRepository->findBy(array('name' => $tagsAsArray));
                    $existingTagNames = array();

                    foreach ($existingTags as $tag) {
                      $resource->addTag($tag);
                      $existingTagNames[] = $tag->getName();
                    }

                    foreach ($tagsAsArray as $name) {
                      if ( in_array($name, $existingTagNames) ){ continue; }
                      $tag = new Tag($name);
                      $em->persist($tag);
                      $resource->addTag($tag);
                    }

                    $objectHasBeenModified = true;
                    break;
            }

            $validator = $this->get('validator');
            $errors = $validator->validate($resource);

            if ($objectHasBeenModified === true && count($errors) == 0){
                $resource->setUpdatedAt(new \DateTime('now'));
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_update_resource', $this->base['standardProject'], array( 'resource' => array( 'routing' => 'resource', 'logName' => $resource->getLogName(), 'args' => $resource->getLogArgs()) ) );

            } elseif (count($errors) > 0) {
                $error = $errors[0]->getMessage(); 
            }
            
        }

        return new Response($error);
    }

    public function deleteResourceAction(Request $request, $slug, $id)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('delete', $request->get('token')))
            return $this->redirect($this->generateUrl('sp_show_project_resources', array('slug' => $slug)));
          
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:Resource');
            $resource = $repository->findOneById($id);

            if ($resource){

                $em = $this->getDoctrine()->getManager();
                $em->remove($resource);
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'This resource has been successfully deleted.'
                );

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_delete_resource', $this->base['standardProject'], array( 'resource' => array( 'routing' => 'resource', 'logName' => $resource->getLogName(), 'args' => $resource->getLogArgs())) );


            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'This resource does not exist.'
                );

            }
            
        }

        return $this->redirect($this->generateUrl('sp_show_project_resources', array('slug' => $slug)));

    }

    public function downloadResourceAction(Request $request, $slug, $id)
    {
  
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:Resource');
            $resource = $repository->findOneById($id);

            if ($resource){

              $response = new Response();
              $response->headers->set('Content-type', 'application/octet-stream');
              $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $resource->getOriginalFilename()));
              $response->setContent(file_get_contents($resource->getAbsoluteUrlPath()));

              return $response;

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'This resource does not exist.'
                );

            }
            
        }

        return $this->redirect($this->generateUrl('sp_show_project_resources', array('slug' => $slug)));

    }

}
