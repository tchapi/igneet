<?php
namespace meta\GeneralBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;  
use Symfony\Component\Console\Input\InputArgument;  
use Symfony\Component\Console\Input\InputInterface;  
use Symfony\Component\Console\Input\InputOption;  
use Symfony\Component\Console\Output\OutputInterface;  
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use PayPal\Auth\OAuthTokenCredential;

class billingUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();  
        $this->setName('billing:update')->setDescription('Updates the billing status of all communities agreements (only if --force is passed)');

        // By default, no action is done in the database, only a dry run
        $this->addOption('force', null,  InputOption::VALUE_NONE, 'Updates the database for REAL');

        // New style for <important>
        $this->importantStyle = new OutputFormatterStyle('red', null, array('bold'));
        
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

      // Should we really update database
      $force = $input->getOption('force');
      $verbose = (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity());
 
      // Additional styling
      $output->getFormatter()->setStyle('important', $this->importantStyle);

      $output->writeln('');
      $output->writeln('Updating billing agreements at <comment>' . date("D M d, Y G:i") .'.</comment>');

      $output->writeln("");

      // List all the communities
      $communitiesRepository = $this->getContainer()->get('doctrine')->getRepository('metaGeneralBundle:Community\Community');
      $communities = $communitiesRepository->findAllBilledCommunities();

      if (!$verbose) {
        $progress = $this->getHelperSet()->get('progress');
        $progress->start($output, count($communities));
      }

      // Do we have billed communities (AHAHAH) ?
      if ($communities){

        // Obtain credentials
        $token = $this->getContainer()->get('paypalHelper')->getToken();

        if ($token === false){
          $output->writeln('<info>Error contacting Paypal</important> : Exiting');
          return;
        }

        $updatedCommunitiesNb = 0;
        foreach ($communities as $community) {

          if ($verbose) {
            $output->writeln('<info>Community</info> : ' . $community->getName());
          } else {
            $progress->advance();
          }

          $billingAgreement = $this->getContainer()->get('paypalHelper')->getBillingAgreement($token, $community->getBillingAgreement());
          if ($billingAgreement === false) {
            // Error retrieving
            if ($verbose) $output->writeln('  <important>Error retrieving agreement</important> : Skipping');
          } else if ($billingAgreement === null) {
            
            // Not active : we need to change
            if ($force) {
              if ($verbose) $output->writeln('  <comment>Agreement not active anymore</comment> : deleting in database');
              $community->setBillingAgreement(null);
              $community->setBillingPlan(null);
              $this->getContainer()->get('doctrine')->getManager()->flush();
              $updatedCommunitiesNb++;
            } else {
              if ($verbose) $output->writeln('  <comment>Agreement not active anymore</comment> : NOT deleting in database (pass --force to do so)');
            }

          } else {
            // else => ACTIVE, we don't do anything
            if ($verbose) $output->writeln('  <comment>Agreement active</comment> : Skipping');
          }

        }

      } else {

        $output->writeln(' # No billed communities.');

      }

      if (!$verbose) $progress->finish();

      $output->writeln('');
      if ($force) { 
        $output->writeln('<info>Billing update run</info>. ' . $updatedCommunitiesNb . ' communities updated.'); 
      } else {
        $output->writeln('<info>Billing update run</info>. No communities were updated (pass --force to override)'); 
      }

      $output->writeln('');

    }

}