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

                return $this->forward($target['slug'], $target['params']);
            }

        } 

        return $this->render('metaGeneralBundle:Default:chooseFile.html.twig', array('targetAsBase64' => $targetAsBase64));

    }
}
