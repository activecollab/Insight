<?php
  namespace ActiveCollab\Insight\Test;

  use ActiveCollab\Insight\Account as AccountInsight;
  use ActiveCollab\Insight\Account\Implementation as AccountInsightImplementation;
  use Predis\Client;

  /**
   * @package ActiveCollab\Insight\Test
   */
  class Account implements AccountInsight
  {
    use AccountInsightImplementation;

    /**
     * @var string
     */
    private $redis_namespace;

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
     * @param string   $redis_namespace
     * @param int|null $id
     */
    public function __construct($redis_namespace, Client &$redis_client, $id = null)
    {
      $this->redis_namespace = $redis_namespace;
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
     * Return namespace that is used to prefix Insight database entries
     *
     * @return string
     */
    protected function getInsightRedisNamespace()
    {
      return $this->redis_namespace;
    }

    /**
     * @return Client
     */
    protected function &getInsightRedisClient()
    {
      return $this->redis_client;
    }
  }