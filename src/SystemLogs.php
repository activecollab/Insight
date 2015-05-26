<?php
  namespace ActiveCollab\Insight;

  use Psr\Log\LoggerInterface;

  /**
   * @package ActiveCollab\Insight
   */
  interface SystemLogs extends LoggerInterface
  {
    /**
     * Paginate log entries
     *
     * @param  int   $page
     * @param  int   $per_page
     * @return array
     */
    public function getLog($page = 1, $per_page = 100);

    /**
     * Return number of log records that are in the log
     *
     * @return integer
     */
    public function countLogs();

    /**
     * Iterate over log entries, for newest to oldest
     *
     * Two arguments are sent to the callback:
     *
     * 1. $record - array with record details
     * 2. $iteration - current iteration #, starting from 1
     *
     * System breaks when it fails to find a record or when callback returns FALSE.
     *
     * @param callable $callback
     * @param string[] $include
     * @param string[] $ignore
     */
    public function forEachLog(callable $callback, array $include = null, array $ignore = null);
  }