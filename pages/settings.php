<?php

/**
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 01.04.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$action = rex_get('action', 'string');
$form   = rex_config_form::factory('kganalytics');

if ($action == 'test-settings') {
    try {
        \Kreatif\kganalytics\Settings::testSettings();
        echo rex_view::info('Settings are working');
    } catch (\Kreatif\kganalytics\TrackingException $ex) {
        echo rex_view::error($ex->getMessage());
    }
}


$field = $form->addSelectField('debug', null, ["class" => "form-control"]);
$field->setLabel('Debug-Modus');
$select = $field->getSelect();
$select->setSize(1);
$select->addOptions([1 => 'aktiv', 0 => 'inaktiv']);

//$field = $form->addSelectField('push_from_server', null, ["class" => "form-control"]);
//$field->setLabel('Übermittlung über');
//$select = $field->getSelect();
//$select->setSize(1);
//$select->addOptions([1 => 'Server', 0 => 'Client (JS) -> benötigt Tag Manager']);

$field = $form->addTextField('queue_min_wait_time', null, ["class" => "form-control"]);
$field->setLabel('GA4 Server: Queue Min Wait Time');
$field->setNotice( 'Mindest-Dauer in Sekunden, bis die serverseitigen Events gesendet werden. (Standard = 300s)');

$field = $form->addTextField('property_id', null, ["class" => "form-control"]);
$field->setLabel('V4 Property-ID');
$field->setNotice(
    'Find the ID on <a href="https://analytics.google.com/analytics/web/" target="_blank">https://analytics.google.com/analytics/web/</a>'
);


$field = $form->addTextField('measurement_api_secret', null, ["class" => "form-control"]);
$field->setLabel('Measurement API Secret');
$field->setNotice(
    '<p>Create Secret going to: <b>Verwaltung</b> > <b>Datenstreams</b> > [Stream auswählen] > <b>Measurement Protocol - API-Secrets</b> > <b>Erstellen</b></p>
<p>Help: <a href="https://developers.google.com/analytics/devguides/collection/protocol/ga4" target="_blank">https://developers.google.com/analytics/devguides/collection/protocol/ga4</a></p>'
);

$field = $form->addTextField('measurement_id', null, ["class" => "form-control"]);
$field->setLabel('Measurement ID');
$field->setNotice(
    '<p>Get ID: <b>Verwaltung</b> > <b>Datenstreams</b> > [Stream auswählen] > <b>MESS-ID</b></p>'
);

$field = $form->addTextAreaField(
    'credentials_json',
    null,
    ["class" => "form-control rex-js-code", 'data-codemirror-mode' => 'javascript']
);
$field->setLabel('Credentials JSON');
$field->setNotice(
    '<ol>
<li>Open the <a href="https://console.developers.google.com/iam-admin/serviceaccounts" target="_blank">Service accounts page</a>. If prompted, select a project.</li>
<li>Click <strong>Create Service Account</strong>, enter a name and description for the service account. You can use the default service account ID, or choose a different, unique one. When done click Create.</li>
<li>The <strong>Service account permissions</strong> (optional) section that follows is not required. Click <strong>Continue</strong>.</li>
<li>On the <strong>Grant users access to this service account</strong> screen, scroll down to the <strong>Create key</strong> section. Click <strong>Create key</strong>.</li>
<li>In the side panel that appears, select the format for your key: <strong>JSON</strong> is recommended.</li>
<li>Click <strong>Create</strong>. Your new public/private key pair is generated and downloaded to your machine; it serves as the only copy of this key. For information on how to store it securely, see <a href="https://cloud.google.com/iam/docs/understanding-service-accounts#managing_service_account_keys">Managing service account keys</a>.</li>
<li>Click <strong>Close</strong> on the <strong>Private key saved to your computer</strong> dialog, then click <strong>Done</strong> to return to the table of your service accounts.</li>
<li>Add service account to the Google Analytics account: The newly created service account will have an email address that looks similar to <code>quickstart@PROJECT-ID.iam.gserviceaccount.com</code>. Use this email address to add a user to the Google analytics view you want to access via the API</li>
<li>Ensure the <code>Google Analytics Data API</code> is enabled in: <a href="https://console.cloud.google.com/apis/dashboard" target="_blank">https://console.cloud.google.com/apis/dashboard</a></li>
</ol>'
);

$formOutput = $form->get();

$button = [
    'label'      => 'Test settings',
    'url'        => rex_url::currentBackendPage(['action' => 'test-settings']),
    'attributes' => ['class' => ['btn', 'btn-primary']],
];
if (\Kreatif\kganalytics\Settings::getValue('property_id') == '') {
    $button['attributes']['disabled'] = 'disabled';
}

$fragment = new rex_fragment();
$fragment->setVar(
    'buttons',
    [$button],
    false
);
$formOutput .= '<div class="rex-form-panel-footer">' . $fragment->parse('core/buttons/button.php') . '</div>';


$fragment = new rex_fragment();
$fragment->setVar('class', 'edit kga-panel', false);
$fragment->setVar('body', $formOutput, false);
echo $fragment->parse('core/page/section.php');