<?php namespace App\Jobs;

use google\appengine\api\taskqueue\PushTask;

/**
 * Job Base Class
 *
 * Base class for all classes that perform background jobs. Requires all
 * subclasses to define a perform() function which does the actual job and
 * providing a static enqueue() method, queueing the job
 * for later processing.
 *
 * This is heavily coupled with the "Jobs" Controller. Invoking
 * MyJob::enqueue() will queue a job order to App Engine's Task Queue which in
 * turn will call /jobs/my-job later on.
 * This allows us to focus writing our job classes instead of modifying the
 * jobs controller every time we add/change/remove jobs.
 *
 * @package Silver
 * @author Brux
 */
abstract class Job
{
  
  /**
   * The method that will perform the task/job. Define this
   * in your job class.
   */
  abstract public function perform();
  
  /**
   * Queues this job for processing later.
   *
   * @param array $args optional arguments
   * @return void
   */
  public static function enqueue()
  {
    $class = (new \ReflectionClass(get_called_class()))->getShortName();
    $dashed_name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $class));
    $args = array_map('rawurlencode', func_get_args());
    $args = implode('/', $args);
    (new PushTask("/jobs/$dashed_name/$args"))->add();
  }
  
}