<?php

namespace meta\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use meta\AdminBundle\Stats\Stats;

class AdminController extends Controller
{

    public function homeAction()
    {

        return $this->render('metaAdminBundle:Default:home.html.twig');

    }

    public function changelogAction()
    {

        // Read changelog
        $log = file_get_contents($this->get('kernel')->getRootDir() . '/../web/CHANGELOG.txt', FILE_USE_INCLUDE_PATH);

        // Parse
        $lines = preg_split("/((\r?\n)|(\r\n?))/", $log);

        $parsable = false; $last = false;
        $last_one = ""; $last_ten = array();
        foreach($lines as $line){

            // do stuff with $line
            if ($line == '##') {
                break;
            }

            if (strpos($line, '#') === 0 || $line == "") {
                continue;
            }

            if ($line == '--') {
                $last = true;
                continue;
            }
            if ($line == '&&') {
                $last = false;
                $parsable = true;
                continue;
            }
            if ($last) {
                $last_one .= $line;
            }
            if ($parsable) {
                //var_dump($line);
                $date = explode("|", $line);
                $commit = explode("<", $line);
                $mail = explode(">", $commit[1]);
                $tags = explode(")", $mail[1]);
                if (isset($tags[1])) {
                    $logs = explode("|", $tags[1]);
                } else {
                    $logs = explode("|", $mail[1]);
                }
                $last_ten[] = array('commit' => trim($commit[0]), 'tags' => trim($tags[0]), 'logs' => trim($logs[0]), 'author' => trim($mail[0]), 'date' => trim($date[1]));
            }

        }

        return $this->render('metaAdminBundle:Default:changelog.html.twig', array( 'full' => $log, 'last_ten' => $last_ten, 'last' => $last_one ));

    }

    /* ********************************************************************* */
    /*                           Non-routed actions                          */
    /*                     are NOT subject to Pre-execute                    */
    /* ********************************************************************* */

    public function currentUserMenuAction()
    {
        return $this->render('metaAdminBundle:Default:_menu.html.twig');
    }

}
