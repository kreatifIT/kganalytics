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


class Client
{
    public static function factory()
    {
        $addon = Settings::getAddon();
        require_once $addon->getPath('vendor/autoload.php');

        $client = new \Google_Client();
        $client->setApplicationName('aaa');
        $client->setAuthConfig(json_decode(Settings::getValue('credentials_json'), true));
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        return $client;
    }
}