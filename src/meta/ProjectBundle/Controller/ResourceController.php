<?php

namespace meta\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\ProjectBundle\Entity\Resource,
    meta\GeneralBundle\Entity\Behaviour\Tag,
    meta\ProjectBundle\Form\Type\ResourceType;

class ResourceController extends BaseController
{

    /*
     * Helper function to guess the provider and the type of a newly created or updated resource
     */
    private function guessProviderAndType($file, $url){

        $types = $this->container->getParameter('standardproject.resource_types');
        $providers = $this->container->getParameter('standardproject.resource_providers');

        if ($file == null) {

            $guessedProvider = 'other';

            foreach ($providers as $provider => $provider_infos) {
                if ($provider != 'other' && $provider != 'local' && preg_match($provider_infos['pattern'], $url)){
                    $guessedProvider = $provider;
                    break;
                }
            }

            $guessedType = explode('.', $url);
            $guessedType = $guessedType[count($guessedType)-1];

        } else {

            $guessedProvider = 'local';

            // Why all this ? 
            // To account for PPTX files that are of ZIP Mime/type (F*** YOU MICROSOFT)
            $guessedType = $file->getExtension();

            if ($guessedType == ''){
                $guessedType = $file->guessExtension();
                if (is_null($guessedType)){
                    $guessedType = 'other';
                }
            }
            // ---------

        }

        // Guesses type
        if (isset($types[$guessedType])){
            $type = $guessedType;
        } else {
            $type = 'other';
        }

        return array('provider' => $guessedProvider, 'type' => $type);

    }

    /*
     * List all the resources of the project
     */
    public function listResourcesAction(Request $request, $uid, $page)
    {
        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($uid, false, $menu['resources']['private']);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

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
                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));

                // Guess resource type and provider
                $guess = $this->guessProviderAndType($request->files->get('resource[file]', null, true), $resource->getUrl());
                    $resource->setType($guess['type']);
                    $resource->setProvider($guess['provider']);

                $em = $this->getDoctrine()->getManager();
                $em->persist($resource);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_add_resource', $this->base['standardProject'], array( 'resource' => array( 'logName' => $resource->getLogName(), 'identifier' => $resource->getId()) ));

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.resources.created', array( '%resource%' => $resource->getTitle(), '%project%' => $this->base['standardProject']->getName()))
                );

                return $this->redirect($this->generateUrl('p_show_project_list_resources', array('uid' => $uid)));
           
            } else {
               
               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );

            }
        }


        return $this->render('metaProjectBundle:Resource:listResources.html.twig', 
            array('base' => $this->base, 'types' => $types, 'providers' => $providers, 'form' => $form->createView()));
    }

    /*
     * Show a resource of a project
     */
    public function showResourceAction($uid, $resource_uid)
    {
        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($uid, false, $menu['resources']['private']);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        $types = $this->container->getParameter('standardproject.resource_types');
        $providers = $this->container->getParameter('standardproject.resource_providers');

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:Resource');
        $resource = $repository->findOneById($this->container->get('uid')->fromUId($resource_uid));

        if (!$resource){
            throw $this->createNotFoundException($this->get('translator')->trans('project.resources.not.found'));
        }

        $newResource = new Resource();
        $form = $this->createForm(new ResourceType(), $newResource)->remove('title');

        return $this->render('metaProjectBundle:Resource:showResource.html.twig', 
            array('base' => $this->base, 'types' => $types, 'providers' => $providers, 'form' => $form->createView(), 'resource' => $resource));

    }

    /*
     * Edit a resource (via X-Editable)
     */
    public function editResourceAction(Request $request, $uid, $resource_uid)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('edit', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project_list_resources', array('uid' => $uid)));
          
        $this->fetchProjectAndPreComputeRights($uid, false, true);
        $error = null;
        $response = null;

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:Resource');
            $resource = $repository->findOneById($this->container->get('uid')->fromUId($resource_uid));

            if ($resource) {

                $objectHasBeenModified = false;
                $em = $this->getDoctrine()->getManager();

                switch ($request->request->get('name')) {
                    case 'title':
                        $resource->setTitle($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                    case 'urlOrFile':
                        $uploadedFile = $request->files->get('resource[file]', null, true);
                        if (null !== $uploadedFile) {
                            $resource->setFile($uploadedFile);
                            $resource->setLatestVersionUploadedAt(new \DateTime('now'));
                        } else {
                            $resource->setUrl($request->request->get('resource[url]', null, true));
                            $resource->setOriginalFilename(null);
                        }

                        // Guess resource type and provider
                        $guess = $this->guessProviderAndType($uploadedFile, $resource->getUrl());
                            $resource->setType($guess['type']);
                            $resource->setProvider($guess['provider']);

                        $objectHasBeenModified = true;
                        $needsRedirect = true;
                        break;
                    case 'tags':
                        $tagsAsArray = array_map('strtolower', $request->request->get('value'));

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
                    $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_update_resource', $this->base['standardProject'], array( 'resource' => array( 'logName' => $resource->getLogName(), 'identifier' => $resource->getId()) ) );
                
                } elseif (count($errors) > 0) {

                    $error = $errors[0]->getMessage(); 
                }

            } else {

              $error = $this->get('translator')->trans('invalid.request', array(), 'errors');

            }
            
        } else {

            $error = $this->get('translator')->trans('invalid.request', array(), 'errors');

        }

        // Wraps up and either return a response or redirect
        if (isset($needsRedirect) && $needsRedirect) {

            if (!is_null($error)) {
                $this->get('session')->getFlashBag()->add(
                    'error', $error
                );
            }

            return $this->redirect($this->generateUrl('p_show_project_list_resources', array('uid' => $uid)));

        } else {
        
            if (!is_null($error)) {
                return new Response($error, 406);
            }

            return new Response($response);
        }
    }

    /*
     * Delete a resource in the project
     */
    public function deleteResourceAction(Request $request, $uid, $resource_uid)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('delete', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project_list_resources', array('uid' => $uid)));
          
        $this->fetchProjectAndPreComputeRights($uid, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:Resource');
            $resource = $repository->findOneById($this->container->get('uid')->fromUId($resource_uid));

            if ($resource){

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_delete_resource', $this->base['standardProject'], array( 'resource' => array( 'logName' => $resource->getLogName(), 'identifier' => $resource->getId())) );

                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
                $em = $this->getDoctrine()->getManager();
                $em->remove($resource);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.resources.deleted', array( '%project%' => $this->base['standardProject']->getName()))
                );

            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('project.resources.not.found')
                );

            }
            
        }

        return $this->redirect($this->generateUrl('p_show_project_list_resources', array('uid' => $uid)));

    }

    /*
     * Download a resource
     */
    public function downloadResourceAction(Request $request, $uid, $resource_uid)
    {
        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($uid, false, $menu['resources']['private']);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:Resource');
            $resource = $repository->findOneById($this->container->get('uid')->fromUId($resource_uid));

            if ($resource && $resource->getOriginalFilename() !== ""){

              $content = @file_get_contents($resource->getAbsoluteUrlPath());

              if ($content == false){

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('project.resources.not.downloadable')
                );

              } else {

                  $response = new Response();
                  $response->headers->set('Content-type', 'application/octet-stream');
                  $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $resource->getOriginalFilename()));
                  $response->setContent($content);

                  return $response;
              }

            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('project.resources.not.found')
                );

            }
            
        }

        return $this->redirect($this->generateUrl('p_show_project_list_resources', array('uid' => $uid)));

    }

}
