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

use rex_path;
use Google\Analytics\Data\V1beta;


class DataClient
{
    public static function factory()
    {
        require_once rex_path::addon('kganalytics', 'vendor/autoload.php');

        $client = new V1beta\BetaAnalyticsDataClient(
            [
                'credentialsConfig' => [
                    'keyFile' => json_decode(Settings::getValue('credentials_json'), true),
                ],
            ]
        );
        return $client;
    }
}