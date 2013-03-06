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
            return new Response();

        $authenticatedUser = $this->getUser();

        if ($authenticatedUser){

            $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Comment\BaseComment');
            $comment = $repository->findOneById($id);

            if ($comment && !$comment->isDeleted()){

                $comment->toggleValidator($authenticatedUser);
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                return new Response(count($comment->getValidators()));

            }
        }

        return new Response();
    }

    /*
     * Deletes a comment
     */
    public function deleteCommentAction(Request $request, $id)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('deleteComment', $request->get('token')))
            return new Response();

        $authenticatedUser = $this->getUser();

        if ($authenticatedUser){

            $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Comment\BaseComment');
            $comment = $repository->findOneById($id);

            if ($comment && $comment->getUser() === $authenticatedUser){

                $comment->delete();
                $em = $this->getDoctrine()->getManager();
                $em->flush();

            }
        }

        return new Response();
    }

    /*
     * Renders pagination
     */
    public function paginationAction($route, $page, $total)
    {
        
        $objects_per_page  = $this->container->getParameter('listings.number_of_items_per_page');
        $last_page         = ceil($total / $objects_per_page);
        $previous_page     = $page > 1 ? $page - 1 : 1;
        $next_page         = $page < $last_page ? $page + 1 : $last_page;

        return $this->render('metaGeneralBundle:Default:pagination.html.twig', array('route' => $route, 'current_page' => $page, 'total' => $total, 'objects_per_page' => $objects_per_page, 'last_page' => $last_page, 'previous_page' => $previous_page, 'next_page' => $next_page));

    }
}
