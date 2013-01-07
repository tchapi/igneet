<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class TimelineController extends BaseController
{

    /*  ####################################################
     *                        TIMELINE
     *  #################################################### */

    public function showTimelineAction($slug, $page)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Base:showRestricted', array('slug' => $slug));

        return $this->render('metaStandardProjectProfileBundle:Timeline:showTimeline.html.twig', 
            array('base' => $this->base));
    }

    public function historyAction($slug, $page){

        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Base:showRestricted', array('slug' => $slug));

        $this->timeframe = array( 'today' => array( 'name' => 'today', 'data' => null),
                            'd-1'   => array( 'name' => date("M j", strtotime("-1 day")), 'data' => null ),
                            'd-2'   => array( 'name' => date("M j", strtotime("-2 day")), 'data' => null ),
                            'd-3'   => array( 'name' => date("M j", strtotime("-3 day")), 'data' => null ),
                            'd-4'   => array( 'name' => date("M j", strtotime("-4 day")), 'data' => null ),
                            'd-5'   => array( 'name' => date("M j", strtotime("-5 day")), 'data' => null ),
                            'd-6'   => array( 'name' => date("M j", strtotime("-6 day")), 'data' => null ),
                            'before'=> array( 'name' => 'before', 'data' => null )
                            );

        // Today


        // Last seven days, by day



        // before

        return $this->render('metaStandardProjectProfileBundle:Timeline:timelineHistory.html.twig', 
            array('base' => $this->base,
                  'timeframe' => $this->timeframe));

    }

}
