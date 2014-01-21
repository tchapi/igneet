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

        $types = $this->container->getParameter('project.resource_types');
        $providers = $this->container->getParameter('project.resource_providers');

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
        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => $menu['resources']['private']));

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Security:showRestricted', array('uid' => $uid));

        $types = $this->container->getParameter('project.resource_types');
        $providers = $this->container->getParameter('project.resource_providers');

        $resource = new Resource();
        $resource->setTitle($this->get('translator')->trans('project.resources.default.link'));
        $form = $this->createForm(new ResourceType(), $resource);

        if ($request->isMethod('POST')) {

            $form->bind($request);

            $pattern = "|^http(s)?://[a-z0-9-]+(.[a-z0-9-_]+)*(:[0-9]+)?(/.*)?$|i";

            if ($form->isValid()) {

                $this->base['project']->addResource($resource);
                $this->base['project']->setUpdatedAt(new \DateTime('now'));

                // What is it ?
                $uploadedFile = $request->files->get('resource[file]', null, true);
                if ($uploadedFile != null) {

                    // A file
                    $resource->setTitle($this->get('translator')->trans('project.resources.default.file'));
                    $title = $uploadedFile->getClientOriginalName();
                    if ($title != "") $resource->setTitle($title);
                    
                } else {

                    // A url, but wait ...
                    if (preg_match( $pattern, $resource->getUrl() ) != 1 ) {
                        // It's an url but it's not valid
                        $this->get('session')->getFlashBag()->add(
                            'error',
                            $this->get('translator')->trans('project.resources.not.valid.url')
                        );
                        return $this->redirect($this->generateUrl('p_show_project_list_resources', array('uid' => $uid)));
                    } else {
                        // It's good, let's try to get the title
                        $doc = new \DOMDocument();
                        @$doc->loadHTMLFile($resource->getUrl());
                        $xpath = new \DOMXPath($doc);
                        $title = $xpath->query('//title')->item(0)->nodeValue;
                        if ($title != "") $resource->setTitle($title);
                    }
                }

                // Guess resource type and provider
                $guess = $this->guessProviderAndType($request->files->get('resource[file]', null, true), $resource->getUrl());
                    $resource->setType($guess['type']);
                    $resource->setProvider($guess['provider']);

                $em = $this->getDoctrine()->getManager();
                $em->persist($resource);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_add_resource', $this->base['project'], array( 'resource' => array( 'logName' => $resource->getLogName(), 'identifier' => $resource->getId()) ));

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.resources.created', array( '%resource%' => $resource->getTitle(), '%project%' => $this->base['project']->getName()))
                );

                return new Response(json_encode(array('redirect' => $this->generateUrl('p_show_project_list_resources', array('uid' => $uid)))), 200, array('Content-Type'=>'application/json'));
           
            } else {

                // Is the file uploaded too large ?
                if ( $_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0 )
                {      
                    $displayMaxSize = ini_get('post_max_size');

                    switch ( substr($displayMaxSize,-1) ) {
                        case 'G':
                            $displayMaxSize = $displayMaxSize * 1024;
                        case 'M':
                            $displayMaxSize = $displayMaxSize * 1024;
                        case 'K':
                            $displayMaxSize = $displayMaxSize * 1024;
                    }
                 
                    return new Response(json_encode(array('error' => $this->get('translator')->trans('file.too.large', array(), 'errors'))), 500, array('Content-Type'=>'application/json'));
           
                }

               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );

            }
        }

        return $this->render('metaProjectBundle:Project:showResources.html.twig', 
            array('base' => $this->base, 'types' => $types, 'providers' => $providers, 'form' => $form->createView()));
    }

    /*
     * Show a resource of a project
     */
    public function showResourceAction($uid, $resource_uid)
    {
        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => $menu['resources']['private']));

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        $types = $this->container->getParameter('project.resource_types');
        $providers = $this->container->getParameter('project.resource_providers');

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
          
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));
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
                        } else if ($request->request->get('resource[url]', null, true) != "" ) {
                            $resource->setUrl($request->request->get('resource[url]', null, true));
                            $resource->setOriginalFilename(null);
                        } else {
                            $needsRedirect = true;
                            break;
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
                    $this->base['project']->setUpdatedAt(new \DateTime('now'));
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_update_resource', $this->base['project'], array( 'resource' => array( 'logName' => $resource->getLogName(), 'identifier' => $resource->getId()) ) );
                
                } elseif (count($errors) > 0) {

                    $error = $this->get('translator')->trans($errors[0]->getMessage()); 
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
          
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:Resource');
            $resource = $repository->findOneById($this->container->get('uid')->fromUId($resource_uid));

            if ($resource){

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_delete_resource', $this->base['project'], array( 'resource' => array( 'logName' => $resource->getLogName(), 'identifier' => $resource->getId())) );

                $this->base['project']->setUpdatedAt(new \DateTime('now'));
                $em = $this->getDoctrine()->getManager();
                $em->remove($resource);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.resources.deleted', array( '%project%' => $this->base['project']->getName()))
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
        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => $menu['resources']['private']));

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
