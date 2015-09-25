<?php
namespace meta\GeneralBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;  
use Symfony\Component\Console\Input\InputArgument;  
use Symfony\Component\Console\Input\InputInterface;  
use Symfony\Component\Console\Input\InputOption;  
use Symfony\Component\Console\Output\OutputInterface;  
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class expiringCommunitiesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();  
        $this->setName('communities:expiring:send')->setDescription('Sends the expiring communities mails (only if --force is passed)');

        // By default, no mails are sent, the output is just printed out (number of mails sent)
        $this->addOption('force', null,  InputOption::VALUE_NONE, 'Sends the mails FOR REAL');

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

      $output->writeln('');
      $output->writeln('Sending expiring communities mails at <comment>' . date("D M d, Y G:i") .'.</comment>');
      $output->writeln('');

      // List all communities that are soon to expire
      $communityRepository = $this->getContainer()->get('doctrine')->getRepository('metaGeneralBundle:Community\Community');
      $expiringCommunities = $communityRepository->findAllExpiringCommunity(new \DateTime('now + ' . $this->getContainer()->getParameter('community.soon_to_expire')));

      // Do we have communities ?
      if ($expiringCommunities) {

        $mailer = $this->getContainer()->get('mailer');
            
        $nbCommunities = count($expiringCommunities);

        $output->writeln(' # We have to notify <comment>' . $nbCommunities . ' community(ies)</comment> today');
        $userCommunityRepository = $this->getContainer()->get('doctrine')->getRepository('metaUserBundle:UserCommunity');

        if (!$verbose) {
          $progress = $this->getHelperSet()->get('progress');
          $progress->start($output, $nbCommunities);
        }

        // Initializes an array of messages
        $messages = array();

        foreach ($expiringCommunities as $community) {

          $expiringIn = $community->getValidUntil()->diff(new \DateTime('now'));

          if (!$verbose) {
            $progress->advance();
          } else {
            $output->writeln("   - <info>" . $community->getName() . "</info> (Expiring in " . $expiringIn->days . " days)");
          }

          // Get all managers of the community
          $userCommunities = $userCommunityRepository->findBy(array("community" => $community, "manager" => true));

          foreach ($userCommunities as $userCommunity) {

            $user = $userCommunity->getUser();
            $locale = $user->getPreferredLanguage();

            if ($verbose) {
              $output->write("     * <comment>" . $user->getFullName() . "</comment> (Locale : " . $locale . ")");
            }
         
            $messages[] = \Swift_Message::newInstance()
                ->setSubject($this->getContainer()->get('translator')->trans('community.expiry.mail.subject', array(), null, $locale))
                ->setFrom($this->getContainer()->getParameter('mailer_from'))
                ->setTo(array($user->getEmail() => $user->getFullName()))
                ->setBody(
                    $this->getContainer()->get('templating')->render(
                        'metaGeneralBundle:Community:expiry.mail.html.twig',
                        array('community' => $community, 'locale' => $locale)
                    ), 'text/html'
                );

            $messages[] = \Swift_Message::newInstance()
                ->setSubject($this->getContainer()->get('translator')->trans('community.expiry.mail.subject', array(), null, $locale))
                ->setFrom($this->getContainer()->getParameter('mailer_from'))
                ->setTo($this->getContainer()->getParameter('mailer_admin'))
                ->setBody(
                    $this->getContainer()->get('templating')->render(
                        'metaGeneralBundle:Community:expiry.mail.admin.html.twig',
                        array('community' => $community, 'locale' => $locale)
                    ), 'text/html'
                );

            if ($verbose) $output->writeln(' --> Mail created');

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

        $output->writeln(' # No expiring communities to notify today.');

      }

      $output->writeln('');

    }

}
