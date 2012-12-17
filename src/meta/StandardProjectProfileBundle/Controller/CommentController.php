<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class CommentController extends Controller
{

    public function commentBoxAction($object)
    {
        
        return $this->render('metaStandardProjectProfileBundle:Comment:commentBox.html.twig', array('object' => $object));
    }
}
