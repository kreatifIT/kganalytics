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


class Tracking
{
    const EVENT_LOGIN     = 'login';
    const EVENT_LEAD      = 'generate_lead';
    const EVENT_PAGEVIEW  = 'page_view';
    const EVENT_SEARCH    = 'search';
    const EVENT_SIGNUP    = 'sign_up';
    const EVENT_USERPROPS = 'user_properties';

    const CUSTOM_EVENT_VISIT = 'visit';

    const DIMENSION_VISIT_TIMESTAMP = 'visit_timestamp';


    public static $debug = false;

    private $events    = [];
    private $delayKeys = ['_overdue', '_default'];

    public final static function factory(): Tracking
    {
        $_this       = \rex::getProperty('kreatif.analytics.tracking');
        self::$debug = \rex_addon::get('project')->getProperty('compile') == 1;

        if (!$_this) {
            $caller = get_called_class();
            $_this  = new $caller();
            \rex::setProperty('kreatif.analytics.tracking', $_this);
            register_shutdown_function([$_this, 'saveDelayedEvents']);
        }
        return $_this;
    }

    public final function getScriptTag(): string
    {
        $result  = '';
        $events  = $this->getEventsToProcess();
        $tagName = \rex_request::isPJAXRequest() ? 'pjax-script' : 'script';

        file_put_contents(\rex_path::base('tracking.log'), "log\n", FILE_APPEND);

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

    private function getEventsByDelayKey(string $delayKey, $excludeProcessed = true): array
    {
        $events = [];
        foreach ($this->events as $event) {
            if ($delayKey == $event->getDelayKey() && (!$excludeProcessed || !$event->isProcessed())) {
                $events[] = $event;
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
        bool $isUnique = true,
        string $delayKey = '_default'
    ): void {
        if ($isUnique && isset($this->events[$eventName])) {
            $key   = $eventName;
            $event = $this->events[$eventName];
        } else {
            $key   = $isUnique ? $eventName : count($this->events);
            $event = new Event($eventName, $key);
        }
        if ($delayKey != '') {
            $event->setDelayKey($delayKey);
        }
        file_put_contents(\rex_path::base('tracking.log'), "event: " . $event->isProcessed() . "\n", FILE_APPEND);
        $event->addProperties($properties);
        $this->events[$key] = $event;
    }

    public function addUserProperties(array $properties = []): void
    {
        $this->addEvent(self::EVENT_USERPROPS, $properties, true);
    }

    public function addPageView(array $properties = [], string $delayVisitWithKey = ''): void
    {
        $this->addEvent(self::EVENT_PAGEVIEW, $properties, true);
        $properties[self::DIMENSION_VISIT_TIMESTAMP] = time();
        $this->addEvent(self::CUSTOM_EVENT_VISIT, $properties, false, $delayVisitWithKey);
    }

    public function addSearch(string $searchTerm, array $properties = []): void
    {
        $properties['search_term'] = $searchTerm;
        $this->addEvent(self::EVENT_SEARCH, $properties, false);
    }

    public function addRegistration(string $method = 'custom'): void
    {
        $this->addEvent(self::EVENT_SIGNUP, ['method' => $method], true);
    }

    public function addLogin(string $method = 'custom'): void
    {
        $this->addEvent(
            self::EVENT_LOGIN,
            [
                'method'  => $method,
                'success' => true,
            ],
            true
        );
    }

    public function addFailedLogin(string $method = 'custom'): void
    {
        $this->addEvent(
            self::EVENT_LOGIN,
            [
                'method'  => $method,
                'success' => false,
            ],
            true
        );
    }

    public function addLead(float $price = null, array $properties = []): void
    {
        $properties = array_merge(['currency' => 'EUR'], $properties);

        if ($price !== null) {
            $properties['value'] = $price;
        }
        $this->addEvent(self::EVENT_LEAD, $properties, false);
    }

    public function saveDelayedEvents()
    {
        $collection = $this->getDelayedEvents();

        if (count($collection) && PHP_SESSION_ACTIVE == session_status()) {
            foreach ((array)$collection['_default'] as $event) {
                $event->setDelayKey('_overdue');
            }
            foreach ($this->events as $index => $event) {
                if ($event->isProcessed()) {
                    unset($this->events[$index]);
                }
            }
            rex_set_session('kreatif.analytics.tracking', $this);
        }
    }

    public static function ext__init(\rex_extension_point $ep): void
    {
        \rex_login::startSession();

        if (PHP_SESSION_ACTIVE == session_status()) {
            $tracking = rex_session('kreatif.analytics.tracking');

            if ($tracking) {
                rex_unset_session('kreatif.analytics.tracking');
                \rex::setProperty('kreatif.analytics.tracking', $tracking);
                register_shutdown_function([$tracking, 'saveDelayedEvents']);
                $tracking::factory();
            }
        }
    }
}