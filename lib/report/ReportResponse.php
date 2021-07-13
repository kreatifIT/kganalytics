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


use Google\Analytics\Data\V1beta\DimensionValue;
use Google\Analytics\Data\V1beta\MetricValue;
use Google\Analytics\Data\V1beta\Row;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\Protobuf\Internal\RepeatedField;


class ReportResponse
{
    protected               $name;
    protected               $groupDimensionName;
    protected RepeatedField $metricHeaders;
    protected RepeatedField $dimensionHeaders;


    public function __construct(string $name, string $groupDimensionName)
    {
        $this->name               = $name;
        $this->groupDimensionName = $groupDimensionName;
    }

    public function processReport(RunReportResponse $report): void
    {
        $this->dimensionHeaders = $report->getDimensionHeaders();
        $this->metricHeaders    = $report->getMetricHeaders();

        foreach ($report->getRows() as $row) {
            $this->processRow($row);
        }
    }

    protected function processRow(Row $row)
    {
        $result        = Result::getReportResult();
        $groupKeyValue = null;

        foreach ($row->getDimensionValues() as $index => $dimension) {
            // find the user_id to set it as key
            if ($this->dimensionHeaders[$index]->getName() == $this->groupDimensionName) {
                $groupKeyValue = $dimension->getValue();
                break;
            }
        }
        foreach ($row->getDimensionValues() as $index => $dimension) {
            $this->processDimension($result, $index, $groupKeyValue, $dimension);
        }
        foreach ($row->getMetricValues() as $index => $metric) {
            $this->processMetric($result, $index, $groupKeyValue, $metric);
        }
    }

    protected function processDimension(Result $result, int $index, $groupKeyValue, DimensionValue $dimension)
    {
        $dimensionName = $this->dimensionHeaders[$index]->getName();
        $result->appendData($this->name, $groupKeyValue, $dimensionName, $dimension->getValue());
    }

    protected function processMetric(Result $result, int $index, $groupKeyValue, MetricValue $metric)
    {
        $headerName = $this->metricHeaders[$index]->getName();
        $result->appendData($this->name, $groupKeyValue, $headerName, $metric->getValue());
    }
}