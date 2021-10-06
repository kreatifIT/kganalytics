<?php

/**
 * This file is part of the Kreatif\Project package.
 *
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 27.05.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kreatif\kganalytics;


class Event
{
    private string $eventName;
    private bool   $isProcessed = false;
    private string $delayKey    = '_default';
    private array  $properties  = [];


    public function __construct(string $eventName)
    {
        $this->eventName = $eventName;
    }

    public function addProperties(array $properties): void
    {
        foreach ($properties as $_key => $_value) {
            if (is_string($_value)) {
                $_value = trim(strip_tags($_value));
            }
            $this->properties[$_key] = $_value;
        }
    }

    public function setDelayKey(string $key): void
    {
        $this->delayKey = $key;
    }

    public function getDelayKey(): string
    {
        return $this->delayKey;
    }

    public function resetProcessed(): void
    {
        $this->isProcessed = false;
    }

    public function isProcessed(): bool
    {
        return $this->isProcessed;
    }

    public function getAsMeasurementObject()
    {
        return [
            'name'   => $this->eventName,
            'params' => $this->properties,
        ];
    }

    public function __toString(): string
    {
        $result     = '';
        $properties = array_merge($this->properties, ['event' => $this->eventName]);
        $result     .= 'window.dataLayer.push(' . json_encode($properties) . ');';

        if (Tracking::$debug) {
            $jsonData = json_encode($properties);
            $result   .= "console.log('kreatif.analytics.tracking: $this->eventName; $jsonData');";
        }
        Tracking::debugLog("- processing event '$this->eventName' with delayKey = $this->delayKey");
        $this->isProcessed = true;
        return $result;
    }
}