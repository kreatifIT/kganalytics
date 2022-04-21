<?php

/**
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 10.12.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kreatif\kganalytics\lib\cron;


use Kreatif\kganalytics\GA4ServerSideTracking;
use Kreatif\kganalytics\lib\Model\Queue;
use Kreatif\kganalytics\Tracking;


class QueueCron extends \KreatifCronjobs
{

    public static function cron_submitQueue(\KreatifCronjobs $job)
    {
        $query = Queue::query();
        $query->where('createdate', date('Y-m-d H:i:s', strtotime('-' . Queue::getMinWaitTime() . ' seconds')), '<=');
        $query->resetOrderBy();
        $query->orderBy('id');
        $collection = $query->find();

        /** @var Queue $dataset */
        foreach ($collection as $dataset) {
            $failedEvents = [];
            $eventList    = $dataset->getArrayValue('events');
            $userProps    = $dataset->getArrayValue('user_properties');
            $clientId     = $dataset->getValue('client_id');
            $userId       = $dataset->getValue('user_id');

            if (!$clientId) {
                $clientId = $dataset->getValue('session_id');
            }

            foreach ($eventList as $timestamp => $events) {
                try {
                    if (!GA4ServerSideTracking::sendEventsViaMeasurementProtocol($events, $clientId, $userId, $userProps, $timestamp)) {
                        $failedEvents[$timestamp] = $events;
                    }
                } catch (\Throwable $ex) {
                    // silence is gold
                }
            }

            if (count($failedEvents)) {
                $dataset->setValue('events', $failedEvents);
                $dataset->insertUpdate();
            } else {
                $dataset->delete();
            }
        }
    }
}