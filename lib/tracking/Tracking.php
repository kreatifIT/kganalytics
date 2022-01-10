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
use Kreatif\IubendaCookie;
use Kreatif\kganalytics\lib\Log;
use Kreatif\kganalytics\lib\Model\Queue;
use rex;
use rex_login;
use rex_request;
use Whoops\Exception\ErrorException;


class Tracking
{
    const EVENT_ADD_TO_CART    = 'add_to_cart';
    const EVENT_LOGIN          = 'login';
    const EVENT_LEAD           = 'generate_lead';
    const EVENT_PAGEVIEW       = 'page_view';
    const EVENT_SEARCH         = 'search';
    const EVENT_SEARCH_RESULTS = 'view_item_list';
    const EVENT_SIGNUP         = 'sign_up';
    const EVENT_GENERAL        = 'general';
    const EVENT_USERPROPS      = 'user_properties';

    const CUSTOM_EVENT_VISIT = 'visit';

    const USER_DIMENSION_REDAXO_ID  = 'redaxo_id';
    const DIMENSION_PAGING_INDEX    = 'visit_paging_index';
    const DIMENSION_VISIT_TIMESTAMP = 'visit_timestamp';

    const DEBUG_LOG_FILENAME = 'kganalytics_debug.log';
    const EVENT_LOG_FILENAME = 'kganalytics_events.log';

    const MEASUREMENT_URL            = 'https://www.google-analytics.com/mp/collect';
    const MEASUREMENT_VALIDATION_URL = 'https://www.google-analytics.com/debug/mp/collect';


    public static bool $debug = false;

    private array $events         = [];
    private array $userProperties = [];
    private array $delayKeys      = ['_overdue', '_default'];

    private function __construct() { }

    public final static function factory(): Tracking
    {
        rex_login::startSession();
        self::appendDebugLog('Tracking::factory call');

        if (PHP_SESSION_ACTIVE == session_status()) {
            if ($tracking = rex_session('kreatif.analytics.delayed_tracking')) {
                self::appendDebugLog('recover instance from session');
                rex_unset_session('kreatif.analytics.delayed_tracking');
                $tracking->start();
            }
        }

        $_this = rex::getProperty('kreatif.analytics.tracking');

        if (!$_this) {
            self::appendDebugLog('instantiate Tracking');
            $caller = get_called_class();
            $_this  = new $caller();
            $_this->start();
        }
        return $_this;
    }

    protected function start()
    {
        register_shutdown_function([$this, 'saveDelayedEvents'], ['caller' => 'shutdown']);
        $this->events = array_values($this->events);
        self::$debug  = Settings::getValue('debug');
        rex::setProperty('kreatif.analytics.tracking', $this);
    }

    public static function appendDebugLog($content)
    {
        if (self::$debug) {
            rex::setProperty('kreatif.analytics.debug_log_written', true);
            $log = new \rex_log_file(\rex_path::log(self::DEBUG_LOG_FILENAME), 2000000);
            $log->add([$content]);
        }
    }

    public static function appendEventLog(string $clientId, string $userId, array $events, int $timestamp = null)
    {
        $log = new \rex_log_file(\rex_path::log(self::EVENT_LOG_FILENAME), 2000000);

        $eventNames = [];
        foreach ($events as $event) {
            $eventNames[] = $event['name'];
        }
        $log->add([$clientId, $userId, implode(', ', $eventNames), json_encode($events), $timestamp]);
    }

    public static function getClientId(): ?string
    {
        if (rex::isBackend()) {
            $clientId = 'kganalytics-backend';
        } else {
            $clientId = rex_session('kganalytics/Tracking.clientId', 'string', 'unknown');
        }
        return $clientId;
    }

    public static function setClientId(string $clientId): void
    {
        if ($dataset = Queue::getByCurrentSessionId()) {
            $dataset->setValue('client_id', $clientId);
            $dataset->inserUpdate();
        }
        rex_set_session('kganalytics/Tracking.clientId', $clientId);
    }

    public static function getUserId(): ?string
    {
        return rex_session('kganalytics/Tracking.userId', 'string', null);
    }

    public static function setUserId(string $userId): void
    {
        if ($dataset = Queue::getByCurrentSessionId()) {
            $dataset->setValue('user_id', $userId);
            $dataset->inserUpdate();
        }
        rex_set_session('kganalytics/Tracking.userId', $userId);
    }

    public final function getScriptTag(): string
    {
        $result  = '';
        $events  = $this->getEventsToProcess();
        $tagName = rex_request::isPJAXRequest() ? 'pjax-script' : 'script ' . IubendaCookie::getAttributes('2,3,4,5');

        if (Settings::getValue('push_from_server')) {
            $initEvent = "console.log('Events are pushed server-side');";
            $pushs     = [];
        } else {
            $initEvent = 'window.dataLayer = window.dataLayer || [];';
            $pushs     = implode("\n", $events);
        }

        if ($userId = self::getUserId()) {
            $messId    = Settings::getValue('measurement_id');
            $initEvent .= "\nconsole.log('user_id = {$userId}');";
            $initEvent .= "gtag('config', '{$messId}', {'user_id': '{$userId}'});";
            $initEvent .= "gtag('set', 'user_properties', {'" . self::USER_DIMENSION_REDAXO_ID . "': '{$userId}'});";
        }

        if (count($events)) {
            if (self::$debug) {
                $delayData = [];
                foreach ($this->getDelayedEvents() as $delayKey => $_events) {
                    $delayData[$delayKey] = count($_events);
                }

                $initEvent .= "\nconsole.log('kreatif.analytics init');";

                if (count($delayData)) {
                    $initEvent .= "\nconsole.log('Delayed Events:');";
                    $initEvent .= "\nconsole.log(" . json_encode($delayData) . ");";
                }
                $initEvent .= "\nconsole.log('Pushing " . count($events) . " Events');";
            }
            $result = "<$tagName>\n" . $initEvent . "\n" . $pushs . "\n</$tagName>";
        } elseif (self::$debug) {
            $delayData = [];
            foreach ($this->getDelayedEvents() as $delayKey => $_events) {
                $delayData[$delayKey] = count($_events);
            }

            if (count($delayData)) {
                $initEvent .= "\nconsole.log('Delayed Events:');";
                $initEvent .= "\nconsole.log(" . json_encode($delayData) . ");";
            }
            $result = "<$tagName>\n" . $initEvent . "</$tagName>";
        }
        return $result;
    }

    private function getDelayedEvents(): array
    {
        $collection = [];
        foreach ($this->events as $event) {
            if (!$event->isProcessed()) {
                $collection[$event->getDelayKey()][] = $event;
            }
        }
        return $collection;
    }

    private function getEventsToProcess(): array
    {
        $events = [];

        foreach ($this->delayKeys as $delayKey) {
            foreach ($this->events as $event) {
                if (!$event->isProcessed() && $delayKey == $event->getDelayKey()) {
                    $events[] = $event;
                }
            }
        }
        if (count($events) && $userId = self::getUserId()) {
            $this->addUserProperties([self::USER_DIMENSION_REDAXO_ID => $userId]);
        }
        return $events;
    }

    public function addDelayKeyToProcess(string $delayKey): void
    {
        $this->delayKeys[] = $delayKey;
        $this->delayKeys   = array_unique($this->delayKeys);
    }

    public function addEvent(
        string $eventName,
        array $properties = [],
        string $delayKey = '_default'
    ): void {
        $eventNames = rex::getProperty('kreatif.analytics.uq_event_names', []);
        $eventKey   = array_key_exists($eventName, $eventNames) ? $eventNames[$eventName] : false;

        if (false === $eventKey) {
            $eventKey               = count($this->events);
            $event                  = new Event($eventName);
            $eventNames[$eventName] = $eventKey;
            rex::setProperty('kreatif.analytics.uq_event_names', $eventNames);
        } else {
            $event = $this->events[$eventKey];
        }
        if ($delayKey != '') {
            $event->setDelayKey($delayKey);
        }
        if (!array_key_exists(self::DIMENSION_PAGING_INDEX, $properties)) {
            $properties[self::DIMENSION_PAGING_INDEX] = 0;
        }
        $event->addProperties($properties);
        $this->events[$eventKey] = $event;
        self::appendDebugLog("+ add event '$eventName' with delayKey = $delayKey");
    }

    public function addUserProperties(array $properties = []): void
    {
        foreach ($properties as $key => $value) {
            $this->userProperties[$key] = ['value' => $value];
        }
    }

    public function addPageView(array $properties = [], string $delayVisitWithKey = ''): void
    {
        $this->addEvent(self::EVENT_PAGEVIEW, $properties);
        $properties[self::DIMENSION_VISIT_TIMESTAMP] = time();
        $this->addEvent(self::CUSTOM_EVENT_VISIT, $properties, $delayVisitWithKey);
    }

    public function addSearch(string $searchTerm, array $properties = []): void
    {
        $properties['search_term'] = $searchTerm;
        $this->addEvent(self::EVENT_SEARCH, $properties);
    }

    public function addViewItemList(array $items = [], array $properties = []): void
    {
        foreach ($items as $item) {
            if (!($item instanceof SearchItem)) {
                throw new TrackingException('item must be instance of SearchItem', 2);
            }
            $properties['items'][] = $item->getEventProperties();
        }
        $this->addEvent(self::EVENT_SEARCH_RESULTS, $properties);
    }

    public function addRegistration(string $method = 'custom'): void
    {
        $this->addEvent(self::EVENT_SIGNUP, ['method' => $method]);
    }

    public function addLogin(string $method = 'custom'): void
    {
        $this->addEvent(
            self::EVENT_LOGIN,
            [
                'method'  => $method,
                'success' => true,
            ]
        );
    }

    public function addFailedLogin(string $method = 'custom'): void
    {
        $this->addEvent(
            self::EVENT_LOGIN,
            [
                'method'  => $method,
                'success' => false,
            ]
        );
    }

    public function addLead(float $price = null, array $properties = []): void
    {
        $properties = array_merge(['currency' => 'EUR'], $properties);

        if ($price !== null) {
            $properties['value'] = $price;
        }
        $this->addEvent(self::EVENT_LEAD, $properties);
    }

    public function addReferer(string $referer, array $properties = []): void
    {
        $host       = parse_url($referer, PHP_URL_HOST);
        $properties = array_merge(
            [
                'category' => 'referer',
                'host'     => $host,
                'uri'      => $referer,
            ],
            $properties
        );
        $this->addEvent(self::EVENT_GENERAL, $properties);
    }

    public function addItemsToCart(array $items, array $properties = []): void
    {
        $sum        = 0;
        $properties = array_merge(['currency' => 'EUR'], $properties);

        foreach ($items as $item) {
            if (!($item instanceof CartItem)) {
                throw new TrackingException('item must be instance of CartItem', 1);
            }
            $sum                   += $item->getPrice();
            $properties['items'][] = $item->getEventProperties();
        }
        $properties['value'] = $sum;
        $this->addEvent(self::EVENT_ADD_TO_CART, $properties);
    }

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
        if ('shutdown' == $params['caller'] && rex::getProperty('kreatif.analytics.debug_log_written', false)) {
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
            $sql = $dataset->inserUpdate();

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

            $queryParams = Query::build(
                [
                    'measurement_id' => Settings::getValue('measurement_id'),
                    'api_secret'     => Settings::getValue('measurement_api_secret'),
                ]
            );
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
                if (rex::isBackend()) {
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

    public static function ext_trackYComLogin(\rex_extension_point $ep): void
    {
        /** @var \rex_ycom_user $user */
        $user = $ep->getSubject();
        self::setUserId($user->getId());
    }
}


class TrackingException extends ErrorException
{
}