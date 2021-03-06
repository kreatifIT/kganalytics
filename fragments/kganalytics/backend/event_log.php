<?php

/**
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 24.11.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$limit   = $this->getVar('entryCount', 30);
$logFile = $this->getVar('logFile');
$logFile = new LimitIterator($logFile, 0, $limit);

?>
<table class="table table-hover">
    <thead>
    <tr>
        <th class="rex-table-icon"></th>
        <th>Log Time</th>
        <th>Event Time</th>
        <th>Client-ID</th>
        <th>User-ID</th>
        <th>Events</th>
        <th>Event-Details</th>
    </tr>
    </thead>
    <tbody>

    <?php foreach ($logFile as $entry): ?>
        <?php
        /** @var rex_log_entry $entry */
        $data    = $entry->getData();
        $logTime = $entry->getTimestamp('%d.%m.%Y %H:%M:%S');
        ?>
        <tr class="<?= 'ERROR' == trim($data[0]) ? 'rex-state-error' : 'rex-state-success' ?>">
            <td class="rex-table-icon"><i class="rex-icon rex-icon-cronjob"></i></td>
            <td>
                <?= $logTime ?>
            </td>
            <td>
                <?= $data[4] ? date('d.m.Y H:i:s', $data[4] / 1000000) : $logTime ?>
            </td>
            <td>
                <?= $data[0] ?>
            </td>
            <td>
                <?= $data[1] ?>
            </td>
            <td>
                <?= $data[2] ?>
            </td>
            <td>
                <?php dump(json_decode($data[3], 'true')) ?>
            </td>
        </tr>
    <?php endforeach; ?>

    </tbody>
</table>
