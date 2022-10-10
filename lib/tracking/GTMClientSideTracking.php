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

class GTMClientSideTracking extends Tracking
{
    public array $events         = [];
    public array $userProperties = [];
    public array $delayKeys      = ['_overdue', '_default'];

    public final function getScriptTag(): string
    {
        $events  = $this->getEventsToProcess();
        $tagName = \rex_extension::registerPoint(new \rex_extension_point('KGANALYTICS_SCRIPT_TAG', \rex_request::isPJAXRequest() ? 'pjax-script' : 'script'));

        $initEvent = 'window.dataLayer = window.dataLayer || [];';
        $pushs     = implode("\n", $events);


        if ($userId = self::getUserId()) {
            $messId = Settings::getValue('measurement_id');

            $initEvent .= "\nconsole.log('user_id = {$userId}');";
            // NOTE Find better check if analytics is embedded ( via tagmanager or directly over gtag )
            $initEvent .= "if( typeof gtag === 'function'){";
            $initEvent .= "gtag('config', '{$messId}', {'user_id': '{$userId}'});";
            $initEvent .= "gtag('set', 'user_properties', {'" . self::USER_DIMENSION_REDAXO_ID . "': '{$userId}'});";
            $initEvent .= "}";
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
        } elseif (self::$debug) {
            $delayData = [];
            foreach ($this->getDelayedEvents() as $delayKey => $_events) {
                $delayData[$delayKey] = count($_events);
            }

            if (count($delayData)) {
                $initEvent .= "\nconsole.log('Delayed Events:');";
                $initEvent .= "\nconsole.log(" . json_encode($delayData) . ");";
            }
        }

        return "<$tagName>\n" . $initEvent . "\n" . $pushs . "\n</$tagName>";
    }

    public static function addClickGTMParams(array $params): string
    {
        $outputParams = [];
        $prefix       = 'data-gtm';

        foreach ($params as $key => $value) {
            $outputParams[] = $prefix . '-' . $key . '="' . $value . '"';
        }
        return implode(' ', $outputParams);
    }
}