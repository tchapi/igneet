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

        $this->timeframe = array( 'today' => array( 'name' => 'today', 'data' => array()),
                            'd-1'   => array( 'name' => date("M j", strtotime("-1 day")), 'data' => array() ),
                            'd-2'   => array( 'name' => date("M j", strtotime("-2 day")), 'data' => array() ),
                            'd-3'   => array( 'name' => date("M j", strtotime("-3 day")), 'data' => array() ),
                            'd-4'   => array( 'name' => date("M j", strtotime("-4 day")), 'data' => array() ),
                            'd-5'   => array( 'name' => date("M j", strtotime("-5 day")), 'data' => array() ),
                            'd-6'   => array( 'name' => date("M j", strtotime("-6 day")), 'data' => array() ),
                            'before'=> array( 'name' => 'before', 'data' => array() )
                            );

        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\StandardProjectLogEntry');
        $entries = $repository->findByStandardProject($this->base['standardProject']);

        $log_types = $this->container->getParameter('general.log_types');
        $logService = $this->container->get('logService');

        foreach ($entries as $entry) {
          
          $text = $logService->getHTML($entry);
          
          $createdAt = date_create($entry->getCreatedAt()->format('Y-m-d H:i:s'));
          $startOfToday = date_create('midnight');

          if ( $createdAt > $startOfToday ) {
            
            // Today
            array_unshift($this->timeframe['today']['data'], $text );

          } else if ( $createdAt < date_create('midnight 6 days ago') ) {

            // Before
            array_unshift($this->timeframe['before']['data'], $text );

          } else {

            // Last seven days, by day
            $days = date_diff($createdAt, $startOfToday)->days + 1;

            array_unshift($this->timeframe['d-'.$days]['data'], $text );

          }

        }

        return $this->render('metaStandardProjectProfileBundle:Timeline:timelineHistory.html.twig', 
            array('base' => $this->base,
                  'timeframe' => $this->timeframe));

    }

}
