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

    public static function testSettings(): string
    {
        // todo: add a valid test-case
        $client = DataClient::factory();

        try {
            $client->runReport(
                [
                    'property'   => 'properties/###PROPERTY-ID###',
                    'dateRanges' => [
                        new V1beta\DateRange(
                            [
                                'start_date' => date('Y-m-d', strtotime('-7 days')),
                                'end_date'   => 'today',
                            ]
                        ),
                    ],
                    'metrics'    => [
                        new V1beta\Metric(
                            [
                                'name' => 'activeUsers',
                            ]
                        ),
                    ],
                ]
            );
            return '';
        } catch (ApiException $ex) {
            return $ex->getBasicMessage();
        }
    }
}