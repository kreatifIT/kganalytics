<?php

/**
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 26.04.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kreatif\kganalytics;

use Google\Analytics\Data\V1beta;
use Google\ApiCore\ApiException;
use rex_addon;


class Settings
{

    public static function getAddon()
    {
        return rex_addon::get('kganalytics');
    }

    public static function getValue($key)
    {
        $addon = self::getAddon();
        return $addon->getConfig($key);
    }

    public static function testSettings(): void
    {
        $event  = new Event(Tracking::CUSTOM_EVENT_VISIT);
        $_event = $event->getAsMeasurementObject();

        Tracking::$debug = true;
        $tracking        = Tracking::factory();
        $tracking::sendEventsViaMeasurementProtocol([$_event], null);
    }
}