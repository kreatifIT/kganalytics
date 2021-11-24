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
        <th>Date</th>
        <th>Message</th>
    </tr>
    </thead>
    <tbody>

    <?php foreach ($logFile as $entry): ?>
        <?php
        /** @var rex_log_entry $entry */
        $data  = $entry->getData();
        ?>
        <tr class="<?= 'ERROR' == trim($data[0]) ? 'rex-state-error' : 'rex-state-success' ?>">
            <td class="rex-table-icon"><i class="rex-icon rex-icon-cronjob"></i></td>
            <td>
                <?= $entry->getTimestamp('%d.%m.%Y %H:%M:%S') ?>
            </td>
            <td>
                <?= $data[0] ?>
            </td>
        </tr>
    <?php endforeach; ?>

    </tbody>
</table>
