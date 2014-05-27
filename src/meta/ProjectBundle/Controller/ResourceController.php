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

            // Once the provider is guessed, let's try to guess type
            if (isset($provider_infos['types'])) {

                if ($provider_infos['types']['type'] == "fixed") {
                    $guessedType = $provider_infos['types']['value'];
                } elseif ($provider_infos['types']['type'] == "regex") {
                    preg_match($provider_infos['types']['value'], $url, $matches);
                    if (isset($matches[1])) {
                        $guessedType = $guessedProvider . "_" . $matches[1];
                    }
                }
                
            } else {

                // Just in case, let's try this as a last resort
                $guessedType = 'other';
            
            }

            

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
        if ($guessedType != "" && isset($types[$guessedType])){
            $type = $guessedType;
        } else {
            $type = 'other';
        }

        return array('provider' => $guessedProvider, 'type' => $type);

    }

    /*
     * List all the resources of the project
     */
    public function listResourcesAction(Request $request, $uid)
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
                    if ($title != "") {
                        $resource->setTitle($title);
                    }
                    
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
                        if (@$doc->loadHTMLFile($resource->getUrl())) {
                            $xpath = new \DOMXPath($doc);
                            $title = $xpath->query('//title')->item(0)->nodeValue;
                        } else {
                            $title = "";
                        }
                        if ($title != "") {
                            $resource->setTitle($title);
                        }
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
                if ($uploadedFile != null) {
                    return new Response(json_encode(array('redirect' => $this->generateUrl('p_show_project_list_resources', array('uid' => $uid)))), 200, array('Content-Type'=>'application/json'));
                } else {
                    return $this->redirect($this->generateUrl('p_show_project_list_resources', array('uid' => $uid)));
                }

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
                 
                    // Specific "error" token for Dropzone
                    return new Response(json_encode(array('error' => $this->get('translator')->trans('file.too.large', array(), 'errors'))), 413, array('Content-Type'=>'application/json'));
           
                }

                // Any other errors ?
                $errors = $form->getErrorsAsString();
                // Get children errors too, in case
                foreach ($form->getChildren() as $child) {
                    $errors .= " - " . $child->getErrorsAsString();
                }

                // Same "error" token for Dropzone
                return new Response(json_encode(array('error' => $errors)), 413, array('Content-Type'=>'application/json'));
        
            }
        }

        return $this->render('metaProjectBundle:Project:showResources.html.twig', 
            array('base' => $this->base, 'types' => $types, 'providers' => $providers, 'form' => $form->createView(), 'resource' => null));
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

        $resource = new Resource();
        $resource->setTitle($this->get('translator')->trans('project.resources.default.link'));
        $form = $this->createForm(new ResourceType(), $resource);

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:Resource');
        $resource = $repository->findOneById($this->container->get('uid')->fromUId($resource_uid));

        if (!$resource){
            throw $this->createNotFoundException($this->get('translator')->trans('project.resources.not.found'));
        }

        return $this->render('metaProjectBundle:Project:showResources.html.twig', 
            array('base' => $this->base, 'types' => $types, 'providers' => $providers, 'form' => $form->createView(), 'resource' => $resource));

    }

    /*
     * Edit a resource
     * NEEDS JSON
     */
    public function editResourceAction(Request $request, $uid, $resource_uid)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('edit', $request->get('token'))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }
          
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        $response = null;
        $error = null;

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:Resource');
            $resource = $repository->findOneById($this->container->get('uid')->fromUId($resource_uid));

            if ($resource) {

                $objectHasBeenModified = false;
                $em = $this->getDoctrine()->getManager();

                if ($request->request->get('name') == "file" && $_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0 )
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
                 
                    $this->get('session')->getFlashBag()->add(
                                'error',
                                $this->get('translator')->trans('file.too.large', array(), 'errors')
                            );

                    return new Response(json_encode(array('redirect' => $this->generateUrl('p_show_project_list_resources', array('uid' => $uid)))), 413, array('Content-Type'=>'application/json'));
           
                }

                switch ($request->request->get('name')) {
                    case 'title':
                        $resource->setTitle($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                    case 'url':
                        $newUrl = trim($request->request->get('value'));

                        if (substr($newUrl, 0, 4) !== "http") { // http://stackoverflow.com/questions/834303/php-startswith-and-endswith-functions
                            $newUrl = "http://" . $newUrl;
                        }

                        $pattern = "|^http(s)?://[a-z0-9-]+(.[a-z0-9-_]+)*(:[0-9]+)?(/.*)?$|i";
                        
                        if (preg_match( $pattern, $newUrl ) != 1 ) {
                        
                            $error = $this->get('translator')->trans('project.resources.not.valid.url');
                        
                        } else {
                        
                            $resource->setUrl($newUrl);
                            // Guess resource type and provider
                            $guess = $this->guessProviderAndType(null, $resource->getUrl());
                                $resource->setType($guess['type']);
                                $resource->setProvider($guess['provider']);

                            $objectHasBeenModified = true;
                        
                        }

                        break;
                    case 'file':

                        $uploadedFile = $request->files->get('value', null, true);

                        if (null !== $uploadedFile) {
                            
                            $resource->setFile($uploadedFile);
                            $resource->setLatestVersionUploadedAt(new \DateTime('now'));

                            $this->get('session')->getFlashBag()->add(
                                'success',
                                $this->get('translator')->trans('project.resources.changed', array( '%project%' => $this->base['project']->getName()))
                            );
                            // Guess resource type and provider
                            $guess = $this->guessProviderAndType($uploadedFile, $resource->getUrl());
                                $resource->setType($guess['type']);
                                $resource->setProvider($guess['provider']);

                            $objectHasBeenModified = true;
                            $needsRedirect = true;
       
                        } else {
    
                            $error = $this->get('translator')->trans('project.resources.error.uploading');
                        
                        }

                        break;
                    case 'tags':

                        $tag = strtolower($request->request->get('key'));

                        $tagRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Behaviour\Tag');
                        $existingTag = $tagRepository->findOneBy(array('name' => $tag));

                        if ($request->request->get('value') == 'remove' && $existingTag && $resource->hasTag($existingTag)) {
                            $resource->removeTag($existingTag);
                            $objectHasBeenModified = true;
                        } else if ($request->request->get('value') == 'add' && $existingTag && !$resource->hasTag($existingTag)) {
                            $resource->addTag($existingTag);
                            $objectHasBeenModified = true;
                            $response = array('tag' => $this->renderView('metaGeneralBundle:Tags:tag.html.twig', array( 'tag' => $existingTag, 'canEdit' => true)));
                        } else if ($request->request->get('value') == 'add' && !$existingTag ){
                            $newTag = new Tag($tag);
                            $em->persist($newTag);
                            $resource->addTag($newTag);
                            $response = array('tag' => $this->renderView('metaGeneralBundle:Tags:tag.html.twig', array( 'tag' => $newTag, 'canEdit' => true)));
                            $objectHasBeenModified = true;
                        } else {
                            $error = $this->get('translator')->trans('project.resources.tag.already');
                        }

                        break;
                }

                $errors = $this->get('validator')->validate($resource);

                if ($objectHasBeenModified === true && count($errors) == 0){

                    $resource->setUpdatedAt(new \DateTime('now')); 
                    $this->base['project']->setUpdatedAt(new \DateTime('now'));
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_update_resource', $this->base['project'], array( 'resource' => array( 'logName' => $resource->getLogName(), 'identifier' => $resource->getId()) ) );

                } elseif (count($errors) > 0) {

                    $error = $this->get('translator')->trans($errors[0]->getMessage());

                } else {
                    
                    if ($response == null) {
                        $error = $this->get('translator')->trans('unnecessary.request', array(), 'errors');
                    }

                }

            }
        }

        // Wraps up and either return a response or redirect
        if (isset($needsRedirect) && $needsRedirect) {

            if (!is_null($error)) {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $error
                );
            }

            return $this->redirect($this->generateUrl('p_show_project_resource', array('uid' => $uid, 'resource_uid' => $resource_uid)));

        } else {
        
            if (!is_null($error)) {
                return new Response(json_encode(array('message' => $error)), 406, array('Content-Type'=>'application/json'));
            }

            return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));
        }
        
    }

    /*
     * Delete a resource in the project
     */
    public function deleteResourceAction(Request $request, $uid, $resource_uid)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('delete', $request->get('token'))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('p_show_project_list_resources', array('uid' => $uid)));
        }
          
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

    /*
     * Redirect to a resource
     */
    public function linkResourceAction(Request $request, $uid, $resource_uid)
    {
        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => $menu['resources']['private']));

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:Resource');
            $resource = $repository->findOneById($this->container->get('uid')->fromUId($resource_uid));

            if ($resource){

                $type = $resource->getType();
                $types = $this->container->getParameter('project.resource_types');

              if ($resource->getProvider() === "local" && $types[$type]['displayable'] == false){

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('project.resources.not.linkable')
                );

              } else {

                  return $this->redirect($resource->getUrl());
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
