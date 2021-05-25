<?php

/**
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 23.04.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use \Google\Analytics\Data\V1beta;


class ReportingTest
{

    public static function start()
    {
        if (rex_get('action', 'string') == 'ga-report') {
            $client     = \Kreatif\kganalytics\DataClient::factory();
            $propertyId = \Kreatif\kganalytics\Settings::getValue('property_id');

            $response = $client->runReport(
                [
                    'property'        => 'properties/' . $propertyId,
                    'dateRanges'      => [
                        new V1beta\DateRange(
                            [
                                'start_date' => date('Y-m-d', strtotime('-5 days')),
                                'end_date'   => 'today',
                            ]
                        ),
                    ],
                    'dimensions'      => [
                        new V1beta\Dimension(
                            [
                                'name'                 => 'jobId',
                                'dimension_expression' => new V1beta\DimensionExpression(
                                    [
                                        'concatenate' => new V1beta\DimensionExpression\ConcatenateExpression(
                                            [
                                                'dimension_names' => ['customEvent:job_id', 'eventName'],
                                                'delimiter'       => '|',
                                            ]
                                        ),
                                    ]
                                ),
                            ]
                        ),
                    ],
                    'metrics'         => [
                        new V1beta\Metric(
                            [
                                'name' => 'eventCount',
                            ]
                        ),
                    ],
                    'dimensionFilter' => new V1beta\FilterExpression(
                        [
                            'filter' => new V1beta\Filter(
                                [
                                    'field_name'     => 'customEvent:job_id',
                                    'in_list_filter' => new V1beta\Filter\InListFilter(
                                        [
                                            'values' => [
                                                2,
                                                33,
                                            ],
                                        ]
                                    ),
                                ]
                            ),
                        ]
                    ),
                ]
            );

            // Print results of an API call.

            $dimensionHeaders = $response->getDimensionHeaders();
            $metricHeaders    = $response->getMetricHeaders();

            foreach ($response->getRows() as $row) {
                foreach ($row->getDimensionValues() as $index => $dimension) {
                    $dimensionName = $dimensionHeaders[$index]->getName();

                    foreach ($row->getMetricValues() as $index => $metric) {
                        pr(
                            [
                                $dimensionName                    => $dimension->getValue(),
                                $metricHeaders[$index]->getName() => $metric->getValue(),
                            ]
                        );
                    }
                }
            }
            exit;
        }
    }
}