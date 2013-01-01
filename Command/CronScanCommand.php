<?php
namespace ColourStream\Bundle\CronBundle\Command;
use ColourStream\Bundle\CronBundle\Entity\CronJob;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Command\Command;

use ColourStream\Bundle\CronBundle\Annotation\CronJob as CronJobAnno;

use Symfony\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Input\InputOption;


class CronScanCommand extends CronBaseCommand
{
    protected function configure()
    {
        $this->setName("cron:scan")
             ->setDescription("Scans for any new or deleted cron jobs")
             ->addOption('keep-deleted', 'k', InputOption::VALUE_NONE, 'If set, deleted cron jobs will not be removed')
             ->addOption('default-disabled', 'd', InputOption::VALUE_NONE, 'If set, new jobs will be disabled by default');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $keepDeleted = $input->getOption("keep-deleted");
        $defaultDisabled = $input->getOption("default-disabled");
        $em = $this->getEntityManger();

        // Enumerate the known jobs
        $jobRepo = $em->getRepository('ColourStreamCronBundle:CronJob');
        $knownJobs = $jobRepo->getKnownJobs();
        $knownJobs = array_fill_keys($knownJobs, true);

        // Enumerate all the jobs currently loaded
        $reader = $this->getContainer()->get("annotation_reader");

        foreach($this->getApplication()->all() as $command)
        {
            // Check for an @CronJob annotation
            $reflClass = new \ReflectionClass($command);
            foreach($reader->getClassAnnotations($reflClass) as $anno)
            {
                if($anno instanceof CronJobAnno)
                {
                    $job = $command->getName();
                    if(array_key_exists($job, $knownJobs))
                    {
                        // Clear it from the known jobs so that we don't try to delete it
                        unset($knownJobs[$job]);

                        // Update the job if necessary
                        $currentJob = $jobRepo->findOneByCommand($job);
                        $currentJob->setDescription($command->getDescription());
                        if($currentJob->getInterval() != $anno->value)
                        {
                            $newTime = new \DateTime();
                            $newTime = $newTime->add(new \DateInterval($anno->value));

                            $currentJob->setInterval($anno->interval);
                            $currentJob->setNextRun($newTime);
                            $output->writeln("Updated interval for $job to {$anno->value}");
                        }
                    }
                    else
                    {
                        $this->newJobFound($em, $output, $command, $anno, $defaultDisabled);
                    }
                }
            }
        }

        // Clear any jobs that weren't found
        if(!$keepDeleted)
        {
            foreach(array_keys($knownJobs) as $deletedJob)
            {
                $output->writeln("Deleting job: $deletedJob");
                $jobToDelete = $jobRepo->findOneByCommand($deletedJob);
                $em->remove($jobToDelete);
            }
        }

        $em->flush();
        $output->writeln("Finished scanning for cron jobs");
    }

    protected function newJobFound(EntityManager $em, OutputInterface $output, Command $command, CronJobAnno $anno, $defaultDisabled = false)
    {
        $newJob = new CronJob();
        $newJob->setCommand($command->getName());
        $newJob->setDescription($command->getDescription());
        $newJob->setInterval($anno->value);
        $newJob->setNextRun(new \DateTime());
        $newJob->setEnabled(!$defaultDisabled);

        $output->writeln("Added the job " . $newJob->getCommand() . " with interval " . $newJob->getInterval());
        $em->persist($newJob);
    }
}
