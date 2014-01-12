<?php

namespace meta\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

    
class SettingsController extends BaseController
{
    
    /*
     * Show the settings page
     */
    public function showSettingsAction($uid)
    {

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => false));
        
        if ($this->access == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        return $this->render('metaProjectBundle:Project:showSettings.html.twig', 
                array('base' => $this->base));

    }
}
