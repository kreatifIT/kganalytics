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
    const EVENT_PAGEVIEW = 'page_view';


    public static $debug = false;

    private $eventCount = 0;
    private $events     = [];
    private $outputing  = true;

    public final static function factory(): Tracking
    {
        $_this = \rex::getProperty('kreatif.analytics.tracking');

        if (!$_this) {
            $caller      = get_called_class();
            $_this       = new $caller();
            self::$debug = \rex_addon::get('project')->getProperty('compile') == 1;

            if (self::$debug) {
                $_this->events[] = "console.log('kreatif.analytics init');";
            }
            $_this->events[] = 'window.dataLayer = window.dataLayer || [];';
            \rex::setProperty('kreatif.analytics.tracking', $_this);
        }
        return $_this;
    }

    public final function getScriptTag(): string
    {
        $result = '';
        if ($this->eventCount > 0) {
            $tagName = \rex_request::isPJAXRequest() ? 'pjax-script' : 'script';
            if ($this->outputing) {
                $result = "<{$tagName}>\n" . implode("\n", $this->events) . "\n</{$tagName}>";
            } else if (self::$debug) {
                $result = "<{$tagName}>console.error('kreatif.analytics.tracking: getScriptTag was called twice')</{$tagName}>";
            }
            $this->outputing = false;
        }
        return $result;
    }

    public function addEvent(string $event, array $properties = [], string $debugInfo = null): void
    {
        $properties = array_merge($properties, ['event' => $event]);

        if (self::$debug) {
            $this->eventCount++;
            $debugInfo      = $debugInfo ?? $event;
            $this->events[] = "console.log('" . $this->eventCount . ": kreatif.analytics.tracking: {$debugInfo}; ". json_encode($properties) ."');";
        }
        $this->events[$event] = 'window.dataLayer.push(' . json_encode($properties) . ');';
    }

    public function addPageView(array $properties = []): void
    {
        $this->addEvent(self::EVENT_PAGEVIEW, $properties);
    }
}