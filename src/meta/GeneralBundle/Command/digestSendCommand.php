<?php
namespace meta\GeneralBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;  
use Symfony\Component\Console\Input\InputArgument;  
use Symfony\Component\Console\Input\InputInterface;  
use Symfony\Component\Console\Input\InputOption;  
use Symfony\Component\Console\Output\OutputInterface;  
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class digestSendCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();  
        $this->setName('digest:send')->setDescription('Sends the automatic mail digests (only if --force is passed)');

        // By default, no mails are sent, the output is just printed out (number of mails sent)
        $this->addOption('force', null,  InputOption::VALUE_NONE, 'Sends the mail FOR REAL');

        // New style for <important>
        $this->importantStyle = new OutputFormatterStyle('red', null, array('bold'));
        
    }
  
    protected function execute(InputInterface $input, OutputInterface $output)
    {

      // Should we really send mails
      $sendMails = $input->getOption('force');
      $verbose = (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity());
 
      // Additional styling
      $output->getFormatter()->setStyle('important', $this->importantStyle);

      $today = $this->getActualDay();
      $sendBiMonthlyEmails = $this->isEvenWeek();
      $sendDefaultDayEmails = $this->isDefaultDay();

      $output->writeln('');
      $output->writeln('Sending digests at <comment>' . date("D M d, Y G:i") .'.</comment>');
      $output->writeln('Today is : <info>' . $today.'.</info>');

      if ($sendBiMonthlyEmails){
        $output->writeln('This week we are sending <info>bi-monthly emails</info>.');
      }

      if ($sendDefaultDayEmails){
        $output->writeln("It's the <info>default</info> day");
      }

      $output->writeln("");

      // List all users to whom we need to send a digest today
      $userRepository = $this->getContainer()->get('doctrine')->getRepository('metaUserBundle:User');
      $usersToSendDigestsTo = $userRepository->findUsersWhoNeedDigestOnDay($today, $sendBiMonthlyEmails, $sendDefaultDayEmails);

      // Do we have users to notify ?
      if ($usersToSendDigestsTo){

        $mailer = $this->getContainer()->get('mailer');
            
        $nbUsers = count($usersToSendDigestsTo);

        $output->writeln(' # We have to notify <comment>' . $nbUsers . ' user(s)</comment> today');
        $userCommunityRepository = $this->getContainer()->get('doctrine')->getRepository('metaUserBundle:UserCommunity');

        if (!$verbose) {
          $progress = $this->getHelperSet()->get('progress');
          $progress->start($output, $nbUsers);
        }

        // Initializes an array of messages
        $messages = array();

        foreach ($usersToSendDigestsTo as $user) {

          $locale = $user->getPreferredLanguage();

          if (!$verbose) {
            $progress->advance();
          } else {
            $output->writeln("   - <info>" . $user->getFullName() . "</info> (Locale : " . $locale . ")");
          }

          if (!($user->getEnableDigest())) {

            if ($verbose) $output->writeln('     --> <important>DOES NOT</important> want digests');
            continue;
          
          }


          // Get userCommunity
          $userCommunities = $userCommunityRepository->findBy(array("user" => $user));

          if (!($user->getEnableSpecificEmails())){
          
            /*
             * One mail for all the notifications
             */

            $nbNotifications = $this->getContainer()->get('logService')->countNotifications($user);
            if ($verbose) $output->writeln('     * ' . $nbNotifications . " aggregate notification(s) to send to " . $user->getEmail());
  
            $notificationsArray = $this->getContainer()->get('logService')->getNotifications($user, null, null, $locale);

            if ($nbNotifications > 0) {

              $messages[] = \Swift_Message::newInstance()
                  ->setSubject($this->getContainer()->get('translator')->trans('user.digest.mail.subject', array(), null, $locale))
                  ->setFrom($this->getContainer()->getParameter('mailer_from'))
                  ->setTo(array($user->getEmail() => $user->getFullName()))
                  ->setBody(
                      $this->getContainer()->get('templating')->render(
                          'metaGeneralBundle:Digest:digest.mail.html.twig',
                          array('notifications' => $notificationsArray['notifications'], 'lastNotified' => $notificationsArray['lastNotified'], 'from' => $notificationsArray['from'], 'community' => null, 'locale' => $locale)
                      ), 'text/html'
                  );

              if ($verbose) $output->writeln('     --> Mail created');
            }
          
          } else {
            
            /*
             * For each community, a separate mail
             */

            $nbNotifications = 0;

            foreach ($userCommunities as $userCommunity) {
              
              if ($userCommunity->isGuest()) continue;

              $nbNotificationsCommunity = $this->getContainer()->get('logService')->countNotifications($user, $userCommunity->getCommunity());
              $nbNotifications += $nbNotificationsCommunity;

              if ($verbose) $output->writeln('     * ' . $userCommunity->getCommunity()->getName() . " : " . $nbNotificationsCommunity . " notification(s) to send to " . $userCommunity->getEmail());

              // When we're sending one mail per community, we're only sending if the number of notifs is not 0 to avoid spamming.
              if ($nbNotificationsCommunity > 0) {

                $notificationsArray = $this->getContainer()->get('logService')->getNotifications($user, null, $userCommunity->getCommunity(), $locale);
                
                $messages[] = \Swift_Message::newInstance()
                  ->setSubject($this->getContainer()->get('translator')->trans('user.digest.mail.subject', array(), null, $locale))
                  ->setFrom(array($this->getContainer()->getParameter('mailer_from') => $this->getContainer()->getParameter('mailer_from_name')))
                  ->setTo(array($user->getEmail() => $user->getFullName()))
                  ->setBody(
                      $this->getContainer()->get('templating')->render(
                          'metaGeneralBundle:Digest:digest.mail.html.twig',
                          array('notifications' => $notificationsArray['notifications'], 'lastNotified' => $notificationsArray['lastNotified'], 'from' => $notificationsArray['from'], 'community' => $userCommunity->getCommunity(), 'locale' => $locale)
                      ), 'text/html'
                  );

                if ($verbose) $output->writeln('     --> Mail created');

              }

            }

          }

        }

        // We have the notifications, send the mail (or not)
        if ($sendMails){

          $countActualMails = 0;
          $failedRecipients = array();

          foreach ($messages as $message) {
            $countActualMails += $mailer->send($message, $failedRecipients);
          }
          
          if ($verbose) $output->writeln("\n" . $countActualMails . ' mail(s) queued in the spool, ready to send');

          $transport = $mailer->getTransport();

          if ($transport instanceof \Swift_Transport_SpoolTransport) {

              $spool = $transport->getSpool();

              if ($spool instanceof \Swift_ConfigurableSpool) {

                  $spool->setMessageLimit($input->getOption('message-limit'));
                  $spool->setTimeLimit($input->getOption('time-limit'));

              }

              if ($spool instanceof \Swift_FileSpool) {

                  if (null !== $input->getOption('recover-timeout')) {
                      $spool->recover($input->getOption('recover-timeout'));
                  } else {
                      $spool->recover();
                  }

              }

              $sentMails = $spool->flushQueue($this->getContainer()->get('swiftmailer.transport.real'));

              if ($verbose) $output->writeln('Spool <info>FLUSHED</info> : <comment>' . $sentMails . '</comment> mail(s) were sent.');

          } else {

            if ($verbose) $output->writeln('Spool <important>NOT FLUSHED</important> : Error getting transport.');

          }
          
        } else {

          if ($verbose) $output->writeln("\n" . '<important>NO</important> mail(s) queued in the spool. Use <comment>--force</comment> to override.');
          if ($verbose) $output->writeln('Spool <important>NOT FLUSHED</important> : no mails were sent.');

        }

        if (!$verbose) $progress->finish();

      } else {

        $output->writeln(' # No users to notify today.');

      }

      $output->writeln('');

    }


    private function getActualDay(){

      return strtolower(date('l'));

    }

    private function isEvenWeek(){

      return (intval(date('W')) % 2 == 0);

    }

    private function isDefaultDay(){

      $defaultDay = $this->getContainer()->getParameter('digest.day');
      return ($this->getActualDay() == $defaultDay);

    }
}
