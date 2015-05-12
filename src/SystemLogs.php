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
    public function getLogSize();
  }