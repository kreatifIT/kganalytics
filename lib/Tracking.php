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


use rex;
use rex_addon;
use rex_login;
use rex_path;
use rex_request;
use Whoops\Exception\ErrorException;


class Tracking
{
    const EVENT_ADD_TO_CART    = 'add_to_cart';
    const EVENT_LOGIN          = 'login';
    const EVENT_LEAD           = 'generate_lead';
    const EVENT_PAGEVIEW       = 'page_view';
    const EVENT_SEARCH         = 'search';
    const EVENT_SEARCH_RESULTS = 'view_search_results';
    const EVENT_SIGNUP         = 'sign_up';
    const EVENT_USERPROPS      = 'user_properties';

    const CUSTOM_EVENT_VISIT = 'visit';

    const DIMENSION_VISIT_TIMESTAMP = 'visit_timestamp';

    const DEBUG_LOG_FILENAME = 'ga-tracking.log';


    public static $debug = false;

    private $events    = [];
    private $delayKeys = ['_overdue', '_default'];

    public final static function factory(): Tracking
    {
        rex_login::startSession();
        self::debugLog('Tracking::factory call');

        if (PHP_SESSION_ACTIVE == session_status()) {
            if ($tracking = rex_session('kreatif.analytics.delayed_tracking')) {
                self::debugLog('recover instance from session');
                rex_unset_session('kreatif.analytics.delayed_tracking');
                $tracking->start();
            }
        }

        $_this = rex::getProperty('kreatif.analytics.tracking');

        if (!$_this) {
            self::debugLog('instantiate Tracking');
            $caller = get_called_class();
            $_this  = new $caller();
            $_this->start();
        }
        return $_this;
    }

    private function start()
    {
        register_shutdown_function([$this, 'saveDelayedEvents'], ['caller' => 'shutdown']);
        $this->events = array_values($this->events);
        self::$debug  = rex_addon::get('project')->getProperty('compile') == 1;
        self::$debug  = self::$debug || rex_addon::get('kganalytics')->getProperty('debug') == 1;
        rex::setProperty('kreatif.analytics.tracking', $this);
    }

    public static function debugLog($content)
    {
        if (rex_addon::get('kganalytics')->getProperty('debug') == 1) {
            rex::setProperty('kreatif.analytics.debug_log_written', true);
            $filePath = rex_path::base(self::DEBUG_LOG_FILENAME);
            file_put_contents($filePath, "{$content}\n", FILE_APPEND);
        }
    }

    public final function getScriptTag(): string
    {
        $result  = '';
        $events  = $this->getEventsToProcess();
        $tagName = rex_request::isPJAXRequest() ? 'pjax-script' : 'script';

        if (count($events)) {
            $initEvent = 'window.dataLayer = window.dataLayer || [];';
            $pushs     = implode("\n", $events);

            if (self::$debug) {
                $delayData = [];
                foreach ($this->getDelayedEvents() as $delayKey => $_events) {
                    $delayData[$delayKey] = count($_events);
                }

                $initEvent .= "\nconsole.log('kreatif.analytics init');";
                $initEvent .= "\nconsole.log('Delayed Event Count:');";
                $initEvent .= "\nconsole.log(" . json_encode($delayData) . ");";
            }
            $result = "<{$tagName}>\n" . $initEvent . "\n" . $pushs . "\n</{$tagName}>";
        } elseif (self::$debug) {
            $delayData = [];
            foreach ($this->getDelayedEvents() as $delayKey => $_events) {
                $delayData[$delayKey] = count($_events);
            }
            $initEvent = "\nconsole.log('Delayed Event Count:');";
            $initEvent .= "\nconsole.log(" . json_encode($delayData) . ");";
            $result    = "<{$tagName}>\n" . $initEvent . "\n" . "console.log('No Tracking [kreatif.analytics]');</{$tagName}>";
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
        self::debugLog("+ add event '{$eventName}' with delayKey = {$delayKey}");
        $event->addProperties($properties);
        $this->events[$eventKey] = $event;
    }

    public function addUserProperties(array $properties = []): void
    {
        $this->addEvent(self::EVENT_USERPROPS, $properties);
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

    public function addSearchResults(string $searchTerm = null, array $items = [], array $properties = []): void
    {
        if ($searchTerm) {
            $properties['search_term'] = $searchTerm;
        }

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
            self::debugLog('requested uri = ' . rex_server('REQUEST_URI', 'string'));
            self::debugLog("save remaining events to session; caller = {$params['caller']}");
            rex_set_session('kreatif.analytics.delayed_tracking', $this);
        }
        if ('shutdown' == $params['caller'] && rex::getProperty('kreatif.analytics.debug_log_written', false)) {
            self::debugLog("-------- END ---------\n\n");
        }
    }
}


class TrackingException extends ErrorException
{
}