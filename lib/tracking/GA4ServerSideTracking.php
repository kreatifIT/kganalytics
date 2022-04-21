<?php

/**
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 19.05.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kreatif\kganalytics;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Request;
use Kreatif\kganalytics\lib\Model\Queue;

class GA4ServerSideTracking extends Tracking
{
    const MEASUREMENT_URL            = 'https://www.google-analytics.com/mp/collect';
    const MEASUREMENT_VALIDATION_URL = 'https://www.google-analytics.com/debug/mp/collect';

    public array $events         = [];
    public array $userProperties = [];
    public array $delayKeys      = ['_overdue', '_default'];

    public function saveDelayedEvents($params)
    {
        $collection = $this->getDelayedEvents();

        if (count($collection) && PHP_SESSION_ACTIVE == session_status()) {
            foreach ((array)$collection['_default'] as $event) {
                $event->setDelayKey('_overdue');
            }
            $_events = [];
            foreach ($this->events as $event) {
                if (!$event->isProcessed()) {
                    $_events[] = $event;
                }
            }
            $this->events = $_events;
            self::appendDebugLog('requested uri = ' . rex_server('REQUEST_URI', 'string'));
            self::appendDebugLog("save remaining events to session; caller = {$params['caller']}");
            rex_set_session('kreatif.analytics.delayed_tracking', $this);
        }
        if ('shutdown' == $params['caller'] && \rex::getProperty('kreatif.analytics.debug_log_written-'.get_class($this), false)) {
            self::appendDebugLog("-------- END ---------\n\n");
        }
    }

    public function enqueueEventsForServersidePush()
    {
        $events = $this->getEventsForMeasurementProtocol();

        if (count($events)) {
            $timestamp = microtime(true) * 1000000;
            $userId    = self::getUserId();
            $clientId  = rex_session('kganalytics/Tracking.clientId', 'string', null);
            $dataset   = Queue::getByCurrentSessionId();

            if (!$dataset) {
                $dataset = Queue::create();
                $dataset->setValue('session_id', session_id());
                $dataset->setValue('createdate', date('Y-m-d H:i:s'));
            }
            if ($clientId) {
                $dataset->setValue('client_id', $clientId);
            }
            if ($userId) {
                $dataset->setValue('user_id', $userId);
            }
            if ($this->userProperties) {
                $dataset->setValue('user_properties', json_encode($this->userProperties));
            }

            $_events             = $dataset->getArrayValue('events');
            $_events[$timestamp] = $events;

            $dataset->setValue('events', json_encode($_events));
            $dataset->setValue('updatedate', date('Y-m-d H:i:s'));
            $sql = $dataset->insertUpdate();

            if (self::$debug && $sql->hasError()) {
                pr($sql->getError(), 'red');
            }
        }
    }

    public static function sendEventsViaMeasurementProtocol(
        array $events,
        ?string $clientId,
        string $userId = null,
        array $userProperties = [],
        int $timestamp = null
    ): bool {
        if (count($events)) {
            $clientId = $clientId ?? self::getClientId();
            self::appendEventLog((string)$clientId, (string)$userId, $events, $timestamp);

            $queryParams = Query::build([
                                            'measurement_id' => Settings::getValue('measurement_id'),
                                            'api_secret'     => Settings::getValue('measurement_api_secret'),
                                        ]);
            $bodyParams  = [
                'json' => [
                    'client_id' => $clientId,
                    'events'    => $events,
                ],
            ];

            if ($userId) {
                $bodyParams['json']['user_id']             = $userId;
                $bodyParams['json'][self::EVENT_USERPROPS] = $userProperties;
            }
            if ($timestamp) {
                $bodyParams['json']['timestamp_micros'] = $timestamp;
            }

            if (self::$debug) {
                if (\rex::isBackend()) {
                    dump(self::MEASUREMENT_VALIDATION_URL . "?{$queryParams}");
                    dump($bodyParams);
                }

                $client    = new Client();
                $request   = new Request('POST', self::MEASUREMENT_VALIDATION_URL . "?{$queryParams}");
                $response  = $client->send($request, $bodyParams);
                $_response = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);

                $errors = [];
                foreach ($_response['validationMessages'] as $error) {
                    $errors[] = "{$error['validationCode']}: {$error['description']}";
                }
                if (count($errors)) {
                    throw new TrackingException(implode("\n", $errors));
                }
            }

            $client     = new Client();
            $request    = new Request('POST', self::MEASUREMENT_URL . "?{$queryParams}");
            $response   = $client->send($request, $bodyParams);
            $respStatus = $response->getStatusCode();

            return $respStatus >= 200 && $respStatus < 300;
        }
    }

    private function getEventsForMeasurementProtocol(): array
    {
        $result = [];
        /** @var Event $event */
        foreach ($this->getEventsToProcess() as $event) {
            $result[] = $event->getAsMeasurementObject();
        }
        return $result;
    }
}