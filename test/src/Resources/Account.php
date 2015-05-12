<?php
  namespace ActiveCollab\Insight\Test;

  use ActiveCollab\Insight\Properties;
  use ActiveCollab\Insight\Properties\Implementation as PropertiesImplementation;
  use ActiveCollab\Insight\SystemLogs;
  use ActiveCollab\Insight\SystemLogs\Implementation as SystemLogsImplementation;
  use ActiveCollab\Insight\Utilities\Keyspace;
  use Predis\Client;

  /**
   * @package ActiveCollab\Insight\Test
   */
  class Account implements Properties, SystemLogs
  {
    use Keyspace, PropertiesImplementation, SystemLogsImplementation;

    /**
     * @var Client
     */
    private $redis_client;

    /**
     * @var int
     */
    private $id = 1;

    /**
     * @param Client   $redis_client
     * @param int|null $id
     */
    public function __construct(Client &$redis_client, $id = null)
    {
      $this->redis_client = $redis_client;

      if ($id) {
        $this->id = $id;
      }
    }

    /**
     * @return int
     */
    public function getInsightAccountId()
    {
      return $this->id;
    }

    /**
     * @return Client
     */
    protected function &getInsightRedisClient()
    {
      return $this->redis_client;
    }
  }