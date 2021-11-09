<?php

/**
 * This file is part of the Kreatif/geo package.
 *
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class rex_api_kga_api extends \Kreatif\Api
{

    protected function __setClientId()
    {
        \Kreatif\kganalytics\Tracking::setClientId($this->request['clientId']);
    }
}
