<?php

/**
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 10.12.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kreatif\kganalytics\lib\Model;


use yform\usability\Model;


class Queue extends Model
{
    const TABLE         = '{PREFIX}kga_event_queue';
    const MIN_WAIT_TIME = 300; // 5 min


    public static function getByCurrentSessionId(): ?self
    {
        $query = parent::query();
        $query->where('session_id', session_id());
        return $query->findOne();
    }
}