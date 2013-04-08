<?php

namespace meta\GeneralBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
     
    /*
     * Allow to choose for a file
     */
    public function chooseFileAction(Request $request, $targetAsBase64)
    {

        $target = json_decode(base64_decode($targetAsBase64), true);

        if ($request->isMethod('POST')) {

            $uploadedFile = $request->files->get('file');

            if (null !== $uploadedFile) {

                // An upload was performed

                // Do we go to crop and resize ?
                if ($target['crop'] == true){
    
                    $filename = sha1(uniqid(mt_rand(), true));
                    $picture = $filename.'-toCropAndResize.'.$uploadedFile->guessExtension();
                    $uploadedFile->move(__DIR__.'/../../../../web/uploads/tmp', $picture);
                    unset($uploadedFile);

                    return $this->render('metaGeneralBundle:Default:resizeCrop.html.twig', array('targetAsBase64' => $targetAsBase64, 'image' => '/uploads/tmp/'.$picture, 'token' => $request->get('token')));

                } else {

                    $this->getRequest()->request->set('token', $request->get('token'));
                    return $this->forward($target['slug'], $target['params']);

                }

            } else {

                $this->getRequest()->request->set('token', $request->get('token'));
                // A crop was performed
                return $this->forward($target['slug'], $target['params']);

            }

        } 

        return $this->render('metaGeneralBundle:Default:chooseFile.html.twig', array('targetAsBase64' => $targetAsBase64, 'token' => $request->get('token')));

    }

    /*
     * Toggles validation for a comment
     */
    public function validateCommentAction(Request $request, $id)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('validateComment', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

        $authenticatedUser = $this->getUser();

        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Comment\BaseComment');
        $comment = $repository->findOneById($id);

        if ($authenticatedUser && $comment && !$comment->isDeleted()){

            $comment->toggleValidator($authenticatedUser);
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return new Response(count($comment->getValidators()));

        } else {

            return new Response($this->get('translator')->trans('invalid.request', array(), 'errors'), 400);

        }

    }

    /*
     * Deletes a comment
     */
    public function deleteCommentAction(Request $request, $id)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('deleteComment', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

        $authenticatedUser = $this->getUser();

        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Comment\BaseComment');
        $comment = $repository->findOneById($id);

        if ($authenticatedUser && $comment && $comment->getUser() === $authenticatedUser){

            $comment->delete();
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return new Response();
            
        } else {

            return new Response($this->get('translator')->trans('invalid.request', array(), 'errors'), 400);

        }

    }

    /*
     * Renders pagination
     */
    public function paginationAction($route, $page, $total, $params = array())
    {
        
        $objects_per_page  = $this->container->getParameter('listings.number_of_items_per_page');
        $last_page         = ceil($total / $objects_per_page);
        $previous_page     = $page > 1 ? $page - 1 : 1;
        $next_page         = $page < $last_page ? $page + 1 : $last_page;

        return $this->render('metaGeneralBundle:Default:pagination.html.twig', array('route' => $route, 'params' => $params, 'current_page' => $page, 'total' => $total, 'objects_per_page' => $objects_per_page, 'last_page' => $last_page, 'previous_page' => $previous_page, 'next_page' => $next_page));

    }

    public function chooseCommunityAction(Request $request, $targetAsBase64)
    {

        $target = json_decode(base64_decode($targetAsBase64), true);

        if ($request->isMethod('POST')) {

            $communityId = $request->request->get('value');

            $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
            $community = $repository->findOneById($communityId);

            if ($community && $this->getUser()->belongsTo($community) && isset($target['slug']) && isset($target['params']) ){

                $target['params']['community'] = $communityId;
                $target['params']['token'] = $request->get('token'); // For CSRF
                return $this->forward($target['slug'], $target['params']);

            } else {

                throw $this->createNotFoundException();

            }

        } else {

            $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
            $communities = $repository->findAllCommunitiesForUser($this->getUser());

            if (count($communities) == 0 ){

                $this->get('session')->setFlash(
                        'warning',
                        $this->get('translator')->trans('user.have.no.community')
                    );

                return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $this->getUser()->getUsername())));
            }

            return $this->render('metaGeneralBundle:Community:chooseCommunity.html.twig', array('communities' => $communities, 'targetAsBase64' => $targetAsBase64, 'token' => $request->get('token')));

        }

    }

    public function switchCommunityAction(Request $request, $id)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('switchCommunity', $request->get('token'))) {
            
            $this->get('session')->setFlash(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );

            return $this->redirect($this->generateUrl('u_me'));
        }

        if ($id === null){ // Private space

            $this->get('session')->setFlash(
                'success',
                $this->get('translator')->trans('user.in.private.space')
            );

            $em = $this->getDoctrine()->getManager();
            $this->getUser()->setCurrentCommunity(null);
            $em->flush();

            return $this->redirect($this->generateUrl('g_home_community'));   
        }

        // Or a real community ?
        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
        $community = $repository->findOneById($id);

        if ($community && ( $this->getUser()->belongsTo($community) || $this->getUser()->isGuestOf($community) ) ){
            
            $this->get('session')->setFlash(
                'success',
                $this->get('translator')->trans('user.in.community', array( '%community%' => $community->getName()))
            );

            $em = $this->getDoctrine()->getManager();
            $this->getUser()->setCurrentCommunity($community);
            $em->flush();

            return $this->redirect($this->generateUrl('g_home_community'));

        } else {
            
            throw $this->createNotFoundException($this->get('translator')->trans('community.notFound')); // Which is false, but we should not reveal its existence

        }

    }
}
