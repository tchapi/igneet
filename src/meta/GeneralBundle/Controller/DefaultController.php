<?php

namespace meta\GeneralBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\Security\Csrf\CsrfToken;

use meta\GeneralBundle\Entity\Comment\CommunityComment,
    meta\IdeaBundle\Entity\Comment\IdeaComment,
    meta\ProjectBundle\Entity\Comment\StandardProjectComment;

class DefaultController extends Controller
{
     
    /*
     * Allow to choose for a file
     */
    public function chooseFileAction(Request $request, $targetAsBase64)
    {

        $target = json_decode(base64_decode($targetAsBase64), true);

        if ($request->isMethod('POST')) {

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
             
                $this->get('session')->getFlashBag()->add(
                        'warning',
                        $this->get('translator')->trans('file.too.large', array(), 'errors')
                    );
                
                return $this->render('metaGeneralBundle:Default:chooseFile.html.twig', array('targetAsBase64' => $targetAsBase64, 'backLink' => isset($target['backLink'])?$target['backLink']:null, 'token' => $request->get('token')));

            }

            $uploadedFile = $request->files->get('file');

            if (null !== $uploadedFile) {

                // An upload was performed

                // Is the file uploaded of the good format ?
                if (isset($target['filetypes'])) {

                    $nb_filetypes = count($target['filetypes']);
                    $extension = strtolower($uploadedFile->guessExtension());
                    $allowed = false;

                    for ($x=0; $x < $nb_filetypes; $x++) {
                        if ($extension == $target['filetypes'][$x]) { $allowed = true; }
                    } 

                    if (!$allowed) {
                        $this->get('session')->getFlashBag()->add(
                                'warning',
                                $this->get('translator')->trans('file.type.not.allowed', array(), 'errors')
                            );
                        return $this->render('metaGeneralBundle:Default:chooseFile.html.twig', array('targetAsBase64' => $targetAsBase64, 'filetypes' => $target['filetypes'], 'backLink' => isset($target['backLink'])?$target['backLink']:null, 'token' => $request->get('token')));
                    }

                }
            
                // Do we go to crop and resize ?
                if ($target['crop'] == true){

                    $filename = sha1(uniqid(mt_rand(), true));
                    $picture = $filename.'-toCropAndResize.'.$extension;
                    $uploadedFile->move(__DIR__.'/../../../../web/uploads/tmp', $picture);
                    unset($uploadedFile);

                    return $this->render('metaGeneralBundle:Default:resizeCrop.html.twig', array('targetAsBase64' => $targetAsBase64, 'image' => '/uploads/tmp/'.$picture, 'backLink' => isset($target['backLink'])?$target['backLink']:null, 'token' => $request->get('token')));

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

        return $this->render('metaGeneralBundle:Default:chooseFile.html.twig', array('targetAsBase64' => $targetAsBase64, 'filetypes' => $target['filetypes'], 'backLink' => isset($target['backLink'])?$target['backLink']:null, 'token' => $request->get('token')));

    }

    /*
     * Toggles validation for a comment
     */
    public function validateCommentAction(Request $request, $id)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('validateComment', $request->get('token'))))
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

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('deleteComment', $request->get('token'))))
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
     * Toggles validation for a comment
     */
    public function addNoteCommentAction(Request $request, $id)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('addNoteComment', $request->get('token'))))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

        $note = $request->request->get('note');
        if ($note === "") {
            return new Response($this->get('translator')->trans('comment.note.cannot.empty', array(), 'errors'), 400);
        }

        $authenticatedUser = $this->getUser();

        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Comment\BaseComment');
        $comment = $repository->findOneById($id);

        if ($comment instanceof CommunityComment) {
            $userManagerCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $authenticatedUser, 'community' => $authenticatedUser->getCurrentCommunity(), 'manager' => true));
            if (!$userManagerCommunity){
                return new Response($this->get('translator')->trans('invalid.request', array(), 'errors'), 400);
            }
        } elseif ($comment instanceof IdeaComment) {
            if (!$comment->getIdea()->getCreators()->contains($authenticatedUser)){
                return new Response($this->get('translator')->trans('invalid.request', array(), 'errors'), 400);
            }
        } elseif ($comment instanceof StandardProjectComment) {
            if (!$comment->getStandardProject()->getOwners()->contains($authenticatedUser)){
                return new Response($this->get('translator')->trans('invalid.request', array(), 'errors'), 400);
            }
        } else {
            return new Response($this->get('translator')->trans('invalid.request', array(), 'errors'), 400);
        }

        if ($authenticatedUser && $comment && !$comment->isDeleted() && !$comment->hasNote()){

            $comment->setNote($note);
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $renderedNote = $this->renderView('metaGeneralBundle:Comment:commentNote.html.twig', array('note' => $note));
            return new Response($renderedNote);

        } else {

            return new Response($this->get('translator')->trans('invalid.request', array(), 'errors'), 400);

        }

    }

    public function chooseCommunityAction(Request $request, $targetAsBase64)
    {

        $target = json_decode(base64_decode($targetAsBase64), true);

        if ($request->isMethod('POST')) {

            $communityUId = $request->request->get('value');

            $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
            $community = $repository->findOneById($this->container->get('uid')->fromUId($communityUId));

            if ($community) {

                $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $this->getUser()->getId(), 'community' => $community->getId(), 'guest' => false));

                if ($userCommunity && isset($target['slug']) && isset($target['params']) ){

                    $target['params']['community'] = $community->getId();
                    $target['params']['token'] = $request->get('token'); // For CSRF
                    return $this->forward($target['slug'], $target['params']);

                } else {

                    throw $this->createNotFoundException();

                }

            } else {

                throw $this->createNotFoundException();

            }

        } else {

            $repository = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity');
            $userCommunities = $repository->findBy(array( 'user' => $this->getUser(), 'guest' => false));

            if (count($userCommunities) == 0 ){

                $this->get('session')->getFlashBag()->add(
                        'warning',
                        $this->get('translator')->trans('member.have.no.community')
                    );

                return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $this->getUser()->getUsername())));
            }

            return $this->render('metaGeneralBundle:Community:chooseCommunity.html.twig', array('userCommunities' => $userCommunities, 'targetAsBase64' => $targetAsBase64, 'token' => $request->get('token')));

        }

    }

    public function switchCommunityAction(Request $request, $uid)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('switchCommunity', $request->get('token')))) {
            
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );

            return $this->redirect($this->generateUrl('u_me'));
        }

        if ($uid === null){ // Private space

            if (!$request->get('redirect')){
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('member.in.private.space')
                );
            }
            $em = $this->getDoctrine()->getManager();
            $this->getUser()->setCurrentCommunity(null);
            $em->flush();

            return $this->redirect($this->generateUrl('g_home_community'));   
        }

        // Or a real community ?
        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
        $community = $repository->findOneById($this->container->get('uid')->fromUId($uid));

        if ($community) {
            
            $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $this->getUser()->getId(), 'community' => $community->getId()));

            if ($userCommunity ){
                
                // We put this test after knowing that we can go to the community, since
                // we don't want a unauthorized user to know if a community has not been
                // paid for.
                if ( !($community->isValid()) ){

                     $this->get('session')->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans('community.invalid', array('%community%' => $community->getName()))
                    );

                    return $this->redirect($this->generateUrl('g_upgrade_community', array( 'uid' => $this->container->get('uid')->toUId($community->getId()))));

                }

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('member.in.community', array( '%community%' => $community->getName()))
                );

                $em = $this->getDoctrine()->getManager();
                $this->getUser()->setCurrentCommunity($community);
                $em->flush();

                return $this->redirect($this->generateUrl('g_home_community'));

            } else {
                
                throw $this->createNotFoundException($this->get('translator')->trans('community.not.found')); // Which is false, but we should not reveal its existence

            }

        } else {
                
            throw $this->createNotFoundException($this->get('translator')->trans('community.not.found')); // Which is false, but we should not reveal its existence

        }

    }

    public function switchLanguageAction(Request $request)
    {
     
        $locale = strtolower(substr($request->get('value'), 0, 2));

        // Get available languages
        $available_languages  = $this->container->getParameter('available.languages');

        if (array_key_exists($locale, $available_languages)){

            // Updates session
            $this->get('session')->set('_locale', $available_languages[$locale]['code']);

            // If user is logged, set preferred language
            if ($this->getUser()){

                $em = $this->getDoctrine()->getManager();
                $this->getUser()->setPreferredLanguage($available_languages[$locale]['code']);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('user.language.preferred', array(), 'messages', $available_languages[$locale]['code'])
                );
                
                return new Response(json_encode(array("redirect" => $this->generateUrl('u_show_user_settings'))));

            }
            
        } else {

            return new Response(json_encode(array("message" => $this->get('translator')->trans('user.language.not.supported'))));

        }

    }

    public function showCreditsAction() 
    {

        return $this->render('metaGeneralBundle:Default:credits.html.twig');

    }
}
