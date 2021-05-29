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
        if (rex::isFrontend()) {
            rex_extension::register('OUTPUT_FILTER', [self::class, 'enrichOutput'], rex_extension::LATE);
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
    }

    public static function start()
    {
        \ReportingTest::start();
    }

    public static function enrichOutput(rex_extension_point $ep): void
    {
        $doOutput = 'navigate' == rex_server('HTTP_SEC_FETCH_MODE', 'string');
        $doOutput = $doOutput || rex_request::isPJAXRequest();
        $tracking = Tracking::factory();

        if ($doOutput && $scriptTag = $tracking->getScriptTag()) {
            if (rex_request::isPJAXRequest()) {
                $output = $ep->getSubject() . $scriptTag;
            } else {
                $output = str_replace('</body>', $scriptTag . '</body>', $ep->getSubject());
            }
            Tracking::debugLog('---> SCRIPT TAG OUTPUT <---');
            $ep->setSubject($output);
        }
        $tracking->saveDelayedEvents(['caller' => 'enrichOutput']);
    }
}