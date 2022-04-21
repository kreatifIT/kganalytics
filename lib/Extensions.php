<?php

/**
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 23.04.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kreatif\kganalytics;


use Kreatif\Api;
use Kreatif\kganalytics\lib\cron\QueueCron;
use Kreatif\kganalytics\lib\Model\Queue;
use ReportingTest;
use rex;
use rex_addon;
use rex_extension;
use rex_extension_point;
use rex_file;
use rex_request;
use rex_scss_compiler;
use rex_view;


class Extensions
{

    public static function init()
    {
        // register model class
        \rex_yform_manager_dataset::setModelClass(Queue::getDbTable(), Queue::class);
        // register cronjobs
        \KreatifCronjobs::registerCronAction('KGAnalytics.submitQueue', [QueueCron::class, 'cron_submitQueue']);

        if (rex::isFrontend()) {
            rex_extension::register('OUTPUT_FILTER', [self::class, 'submitTrackingEvents'], rex_extension::LATE);

            rex_extension::register(
                'PACKAGES_INCLUDED',
                function () {
                    \rex_login::startSession();
                    rex_view::setJsProperty(
                        'kga',
                        [
                            'debug'    => Settings::getValue('debug'),
                            'clientId' => Tracking::getClientId(),
                            'apiUrls'  => [
                                'setClientId' => Api::getUrl(\rex_api_kga_api::class, 'setClientId'),
                            ],
                        ]
                    );
                }
            );
        } elseif (rex::getUser()) {
            $addon = rex_addon::get('kganalytics');

            if ($addon->getProperty('compile') == 1) {
                $cssFilePath = $addon->getPath('assets/css/backend.css');
                $compiler    = new rex_scss_compiler();
                $compiler->setScssFile($addon->getPath('assets/css/backend.scss'));
                $compiler->setCssFile($cssFilePath);
                $compiler->compile();
                rex_file::copy($cssFilePath, $addon->getAssetsPath('css/backend.css'));
            }
            rex_view::addCssFile($addon->getAssetsUrl('css/backend.css'));

            rex_extension::register('PACKAGES_INCLUDED', [self::class, 'start']);
        }

        rex_extension::register('YCOM_AUTH_LOGIN_SUCCESS', [Tracking::class, 'ext_trackYComLogin']);
    }

    public static function start()
    {
        ReportingTest::start();
    }

    public static function submitTrackingEvents(rex_extension_point $ep): void
    {
        $GA4ServerSideTracking = GA4ServerSideTracking::factory();
        $GTMClientSideTracking = GTMClientSideTracking::factory();

        //do magic for GA4 Server-Side Tracking
        $GA4ServerSideTracking->enqueueEventsForServersidePush();
        $GA4ServerSideTracking->saveDelayedEvents(['caller' => 'enrichOutput']);

        //do magic for GTM Client Side Tracking
        if ($scriptTag = $GTMClientSideTracking->getScriptTag()) {
            if (rex_request::isPJAXRequest()) {
                $output = $ep->getSubject() . $scriptTag;
            } else {
                $output = str_replace('</body>', $scriptTag . '</body>', $ep->getSubject());
            }
            $ep->setSubject($output);
            $GTMClientSideTracking->saveDelayedEvents(['caller' => 'enrichOutput']);
        }

    }
}