<?php

/**
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 24.11.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
$func = rex_request('func', 'string');
$page = rex_request('page', 'string');

if ('system/log/kga_debug' == $page) {
    $fragmentFile = 'debug_log.php';
    $logFile      = rex_path::log(\Kreatif\kganalytics\Tracking::DEBUG_LOG_FILENAME);
} elseif ('system/log/kga_events' == $page) {
    $fragmentFile = 'event_log.php';
    $logFile      = rex_path::log(\Kreatif\kganalytics\Tracking::EVENT_LOG_FILENAME);
}


if ('cronjob_delLog' == $func) {
    if (rex_log_file::delete($logFile)) {
        rex_view::success('syslog_deleted');
    }
}


$fragment = new rex_fragment();
$fragment->setVar('logFile', new rex_log_file($logFile));
$content = $fragment->parse("kganalytics/backend/{$fragmentFile}");


$n              = [];
$formElements   = [];
$n['field']     = '<button class="btn btn-delete" type="submit" name="del_btn" data-confirm="' . rex_i18n::msg(
        'cronjob_delete_log_msg'
    ) . '?">' . rex_i18n::msg('syslog_delete') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');

$fragment = new rex_fragment();
$fragment->setVar('content', $content, false);
$fragment->setVar('buttons', $buttons, false);
$content = $fragment->parse('core/page/section.php');

echo '
    <form action="' . rex_url::currentBackendPage() . '" method="post">
        <input type="hidden" name="func" value="cronjob_delLog" />
        ' . $content . '
    </form>';