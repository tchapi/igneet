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

    // Utility function factored to guess file type and provider
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
            $guessedType = $file->guessExtension();

        }

        // Guesses type
        if (isset($types[$guessedType])){
            $type = $guessedType;
        } else {
            $type = 'other';
        }

        return array('provider' => $guessedProvider, 'type' => $type);

    }

    public function listResourcesAction(Request $request, $slug, $page)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, false);

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
                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));

                // Guess resource type and provider
                $guess = $this->guessProviderAndType($request->files->get('resource[file]', null, true), $resource->getUrl());
                    $resource->setType($guess['type']);
                    $resource->setProvider($guess['provider']);

                $em = $this->getDoctrine()->getManager();
                $em->persist($resource);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_add_resource', $this->base['standardProject'], array( 'resource' => array( 'routing' => 'resource', 'logName' => $resource->getLogName(), 'args' => $resource->getLogArgs()) ));

                $this->get('session')->setFlash(
                    'success',
                    'Your resource '.$resource->getTitle().' has successfully been added to the project '.$this->base['standardProject']->getName().'.'
                );

                return $this->redirect($this->generateUrl('sp_show_project_list_resources', array('slug' => $this->base['standardProject']->getSlug())));
           
            } else {
               
               $this->get('session')->setFlash(
                    'error',
                    'The information you provided does not seem valid.'
                );

            }
        }


        return $this->render('metaStandardProjectProfileBundle:Resource:listResources.html.twig', 
            array('base' => $this->base, 'types' => $types, 'providers' => $providers, 'form' => $form->createView()));
    }

    public function showResourceAction($slug, $id)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, false);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Base:showRestricted', array('slug' => $slug));

        $types = $this->container->getParameter('standardproject.resource_types');
        $providers = $this->container->getParameter('standardproject.resource_providers');

        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:Resource');
        $resource = $repository->findOneById($id);

        if (!$resource){
            throw $this->createNotFoundException('This resource does not exist');
        }

        $newResource = new Resource();
        $form = $this->createForm(new ResourceType(), $newResource)->remove('title');

        return $this->render('metaStandardProjectProfileBundle:Resource:showResource.html.twig', 
            array('base' => $this->base, 'types' => $types, 'providers' => $providers, 'form' => $form->createView(), 'resource' => $resource));

    }

    public function editResourceAction(Request $request, $slug, $id)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('edit', $request->get('token')))
            return $this->redirect($this->generateUrl('sp_show_project_list_resources', array('slug' => $slug)));
          
        $this->fetchProjectAndPreComputeRights($slug, false, true);
        $error = null;
        $response = null;

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:Resource');
            $resource = $repository->findOneById($id);

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
                    $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_update_resource', $this->base['standardProject'], array( 'resource' => array( 'routing' => 'resource', 'logName' => $resource->getLogName(), 'args' => $resource->getLogArgs()) ) );
                
                } elseif (count($errors) > 0) {

                    $error = $errors[0]->getMessage(); 
                }

            } else {

              $error = 'Invalid request';

            }
            
        } else {

            $error = 'Invalid request';

        }

        // Wraps up and either return a response or redirect
        if (isset($needsRedirect) && $needsRedirect) {

            if (!is_null($error)) {
                $this->get('session')->setFlash(
                        'error', $error
                    );
            }

            return $this->redirect($this->generateUrl('sp_show_project_list_resources', array('slug' => $slug)));

        } else {
        
            if (!is_null($error)) {
                return new Response($error, 406);
            }

            return new Response($response);
        }
    }

    public function deleteResourceAction(Request $request, $slug, $id)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('delete', $request->get('token')))
            return $this->redirect($this->generateUrl('sp_show_project_list_resources', array('slug' => $slug)));
          
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:Resource');
            $resource = $repository->findOneById($id);

            if ($resource){

                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
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

        return $this->redirect($this->generateUrl('sp_show_project_list_resources', array('slug' => $slug)));

    }

    public function downloadResourceAction(Request $request, $slug, $id)
    {
  
        $this->fetchProjectAndPreComputeRights($slug, false, false);

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

        return $this->redirect($this->generateUrl('sp_show_project_list_resources', array('slug' => $slug)));

    }

}
