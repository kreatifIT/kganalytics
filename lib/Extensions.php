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


class Extensions
{

    public static function init()
    {
        if (\rex::isFrontend()) {
            \rex_extension::register('PACKAGES_INCLUDED', [self::class, 'start']);
        } else {
            $addon = \rex_addon::get('kganalytics');

            if ($addon->getProperty('compile') == 1) {
                $cssFilePath = $addon->getPath('assets/css/backend.css');
                $compiler    = new \rex_scss_compiler();
                $compiler->setScssFile($addon->getPath('assets/css/backend.scss'));
                $compiler->setCssFile($cssFilePath);
                $compiler->compile();
                \rex_file::copy($cssFilePath, $addon->getAssetsPath('css/backend.css'));
            }
            \rex_view::addCssFile($addon->getAssetsUrl('css/backend.css'));
        }
    }

    public static function start()
    {
        \ReportingTest::start();
    }
}