<?php

/**
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 01.04.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$form = rex_config_form::factory('kganalytics');

$field = $form->addTextField('view_id', null, ["class" => "form-control"]);
$field->setLabel('V3 Property-ID');

$field = $form->addTextAreaField('credentials_json', null, ["class" => "form-control rex-js-code", 'data-codemirror-mode' => 'javascript']);
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
</ol>'
);

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit kga-panel', false);
$fragment->setVar('body', $form->get(), false);
echo $fragment->parse('core/page/section.php');