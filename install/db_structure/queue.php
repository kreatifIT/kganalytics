<?php

/**
 * This file is part of the Kreatif\Project package.
 *
 * @author Kreatif GmbH
 * @author p.parth@kreatif.it
 * Date: 15.09.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Kreatif\kganalytics\lib\Model\Queue;
use yform\usability\Usability;


$prio  = 0;
$langs = array_values(\rex_clang::getAll());
$table = Queue::getDbTable();


Usability::ensureValueField(
    $table,
    'session_id',
    'text',
    [
        'prio' => $prio++,
    ],
    [
        'list_hidden' => 0,
        'search'      => 1,
        'label'       => 'Session-ID',
        'db_type'     => 'varchar(191)',
        'attributes'  => json_encode(['readonly' => 'readonly']),
    ]
);

Usability::ensureValueField(
    $table,
    'client_id',
    'text',
    [
        'prio' => $prio++,
    ],
    [
        'list_hidden' => 0,
        'search'      => 1,
        'label'       => 'Client-ID',
        'db_type'     => 'varchar(191)',
        'attributes'  => json_encode(['readonly' => 'readonly']),
    ]
);

Usability::ensureValueField(
    $table,
    'user_id',
    'text',
    [
        'prio' => $prio++,
    ],
    [
        'list_hidden' => 0,
        'search'      => 1,
        'label'       => 'User-ID',
        'db_type'     => 'varchar(191)',
        'attributes'  => json_encode(['readonly' => 'readonly']),
    ]
);

Usability::ensureValueField(
    $table,
    'events',
    'data_dump',
    [
        'prio' => $prio++,
    ],
    [
        'list_hidden' => 1,
        'search'      => 0,
        'label'       => 'Events',
        'db_type'     => 'text',
    ]
);

Usability::ensureValueField(
    $table,
    'user_properties',
    'data_dump',
    [
        'prio' => $prio++,
    ],
    [
        'list_hidden' => 1,
        'search'      => 0,
        'label'       => 'User properties',
        'db_type'     => 'text',
    ]
);


Usability::ensureDateFields($table, $prio);

$yTable = \rex_yform_manager_table::get($table);
\rex_yform_manager_table_api::generateTableAndFields($yTable);