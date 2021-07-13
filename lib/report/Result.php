<?php

/**
 * This file is part of the Kreatif\Project package.
 *
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 04.06.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kreatif\kganalytics;


class Result
{
    protected $data = [];


    public static function getReportResult(): self
    {
        $inst = \rex::getProperty('kreatif.analytics.report.result');

        if (null === $inst) {
            $inst = new self();
            \rex::setProperty('kreatif.analytics.report.result', $inst);
        }
        return $inst;
    }


    public function appendData(string $name, string $groupKey, string $label, $value)
    {
        $this->data[$groupKey][$name][$label] = $value;
    }

    public function appendArrayData(string $name, string $groupKey, string $label, $value)
    {
        $this->data[$groupKey][$name][$label][] = $value;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}