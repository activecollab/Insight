<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

declare (strict_types = 1);

namespace ActiveCollab\Insight\Test\Fixtures;

use ActiveCollab\Insight\Account\DataSetTimelineInterface;
use ActiveCollab\Insight\Account\EventsInterface;
use ActiveCollab\Insight\Account\GoalsInterface;
use ActiveCollab\Insight\Account\LogsInterface;
use ActiveCollab\Insight\Account\PropertiesInterface;
use ActiveCollab\Insight\AccountInterface;
use ActiveCollab\Insight\DataSetTimeline\Implementation as DataSetTimelineImplementation;
use ActiveCollab\Insight\Events\Implementation as EventsImplementation;
use ActiveCollab\Insight\Goals\Implementation as GoalsImplementation;
use ActiveCollab\Insight\Properties\Implementation as PropertiesImplementation;
use ActiveCollab\Insight\StorageInterface;
use ActiveCollab\Insight\SystemLogs\Implementation as SystemLogsImplementation;

/**
 * @package ActiveCollab\Insight\Test
 */
//class Account implements AccountInterface, PropertiesInterface, EventsInterface, LogsInterface, DataSetTimelineInterface, GoalsInterface
class Account implements AccountInterface, EventsInterface
{
//    use PropertiesImplementation, EventsImplementation, SystemLogsImplementation, DataSetTimelineImplementation, GoalsImplementation;
    use EventsImplementation;

    /**
     * @var StorageInterface
     */
    private $metrics_storage;

    /**
     * @var int
     */
    private $id = 1;

    /**
     * @param StorageInterface $metrics_storage
     * @param int|null         $id
     */
    public function __construct(StorageInterface &$metrics_storage, int $id)
    {
        $this->metrics_storage = $metrics_storage;
        $this->id = $id;

        $this->onBeforeSetProperty('clean_version_number', function (&$value) {
            if (strpos($value, '-')) {
                $value = explode('-', $value)[0];
            }
        });
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return StorageInterface
     */
    public function &getMetricsStorage(): StorageInterface
    {
        return $this->metrics_storage;
    }
}
