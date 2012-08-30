<?php
namespace ColourStream\Bundle\CronBundle\Command;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Input\ArgvInput;

use ColourStream\Bundle\CronBundle\Entity\CronJobResult;

use ColourStream\Bundle\CronBundle\Entity\CronJob;

use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Input\InputArgument;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class CronRunCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName("cron:run")
             ->setDescription("Runs any currently schedule cron jobs")
             ->addArgument("job", InputArgument::OPTIONAL, "Run only this job (if enabled)");
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        $em = $this->getContainer()->get("doctrine.orm.entity_manager");
        $jobRepo = $em->getRepository('ColourStreamCronBundle:CronJob');
        
        $jobsToRun = array();
        if($jobName = $input->getArgument('job'))
        {
            try
            {
                $jobObj = $jobRepo->findOneByCommand($jobName);
                if($jobObj->getEnabled())
                {
                    $jobsToRun = array($jobObj);
                }
            }
            catch(\Exception $e)
            {
                $output->writeln("Couldn't find a job by the name of $jobName");
                return CronJobResult::FAILED;
            }
        }
        else
        {
            $jobsToRun = $jobRepo->findDueTasks();
        }
        
        $jobCount = count($jobsToRun);
        $output->writeln("Running $jobCount jobs:");
        
        foreach($jobsToRun as $job)
        {
            $this->runJob($job, $output, $em);
        }
        
        // Flush our results to the DB
        $em->flush();
        
        $end = microtime(true);
        $duration = sprintf("%0.2f", $end-$start);
        $output->writeln("Cron run completed in $duration seconds");
    }
    
    protected function runJob(CronJob $job, OutputInterface $output, EntityManager $em)
    {
        $output->write("Running " . $job->getCommand() . ": ");
        
        try
        {
            $commandToRun = $this->getApplication()->get($job->getCommand());
        }
        catch(InvalidArgumentException $ex)
        {
            $output->writeln(" skipped (command no longer exists)");
            $this->recordJobResult($em, $job, 0, "Command no longer exists", CronJobResult::SKIPPED);

            // No need to reschedule non-existant commands
            return;
        }
        
        $emptyInput = new ArgvInput();
        $jobOutput = new MemoryWriter();
        
        $jobStart = microtime(true);
        try
        {
            $returnCode = $commandToRun->execute($emptyInput, $jobOutput);
        }
        catch(\Exception $ex)
        {
            $returnCode = CronJobResult::FAILED;
            $jobOutput->writeln("");
            $jobOutput->writeln("Job execution failed with exception " . get_class($ex) . ":");
            $jobOutput->writeln($ex->__toString());
        }
        $jobEnd = microtime(true);
        
        // Clamp the result to accepted values
        if($returnCode < CronJobResult::RESULT_MIN || $returnCode > CronJobResult::RESULT_MAX)
        {
            $returnCode = CronJobResult::FAILED;
        }
        
        // Output the result
        $statusStr = "unknown";
        if($returnCode == CronJobResult::SKIPPED)
        {
            $statusStr = "skipped";
        }
        elseif($returnCode == CronJobResult::SUCCEEDED)
        {
            $statusStr = "succeeded";
        }
        elseif($returnCode == CronJobResult::FAILED)
        {
            $statusStr = "failed";
        }
        
        $durationStr = sprintf("%0.2f", $jobEnd-$jobStart);
        $output->writeln("$statusStr in $durationStr seconds");
        
        // Record the result
        $this->recordJobResult($em, $job, $jobEnd-$jobStart, $jobOutput->getOutput(), $returnCode);
        
        // And update the job with it's next scheduled time
        $newTime = new \DateTime();
        $newTime = $newTime->add(new \DateInterval($job->getInterval()));
        $job->setNextRun($newTime);
    }
    
    protected function recordJobResult(EntityManager $em, CronJob $job, $timeTaken, $output, $resultCode)
    {
        // Create a new CronJobResult
        $result = new CronJobResult();
        $result->setJob($job);
        $result->setRunTime($timeTaken);
        $result->setOutput($output);
        $result->setResult($resultCode);
        
        // Then update associations and persist it
        $job->setMostRecentRun($result);
        $em->persist($result);
    }
}
