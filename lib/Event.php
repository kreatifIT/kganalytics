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
    private $eventName;
    private $collectionKey;
    private $isProcessed = false;
    private $delayKey    = '_default';
    private $properties  = [];


    public function __construct(string $eventName, string $collectionKey)
    {
        $this->eventName     = $eventName;
        $this->collectionKey = $collectionKey;
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

    public function getCollectionKey(): string
    {
        return $this->collectionKey;
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

    public function __toString(): string
    {
        $result     = '';
        $properties = array_merge($this->properties, ['event' => $this->eventName]);
        $result     .= 'window.dataLayer.push(' . json_encode($properties) . ');';

        if (Tracking::$debug) {
            $jsonData = json_encode($properties);
            $result   .= "console.log('kreatif.analytics.tracking: {$this->eventName}; " . $jsonData . "');";
        }
        file_put_contents(\rex_path::base('tracking.log'), "process\n", FILE_APPEND);
        $this->isProcessed = true;
        return $result;
    }
}