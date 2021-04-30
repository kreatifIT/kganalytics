<?php

/**
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 23.04.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


class ReportingTest
{

    public static function start()
    {
        if (rex_get('action', 'string') == 'report') {
            $client = \Kreatif\kganalytics\Client::factory();
            $analytics = new Google_Service_AnalyticsReporting($client);
            $response  = getReport($analytics);
            printResults($response);
            exit;
        }
    }
}


/**
 * Queries the Analytics Reporting API V4.
 *
 * @param service An authorized Analytics Reporting API V4 service object.
 *
 * @return The Analytics Reporting API V4 response.
 */
function getReport($analytics)
{
    // Create the DateRange object.
    $dateRange = new Google_Service_AnalyticsReporting_DateRange();
    $dateRange->setStartDate("7daysAgo");
    $dateRange->setEndDate("today");

    // Create the Metrics object.
    $metric1 = new Google_Service_AnalyticsReporting_Metric();
    $metric1->setExpression("ga:sessions");
    $metric1->setAlias("sessions");

    // Create the Metrics object.
    $metric2 = new Google_Service_AnalyticsReporting_Metric();
    $metric2->setExpression("ga:itemRevenue");

    // Create the ReportRequest object.
    $request = new Google_Service_AnalyticsReporting_ReportRequest();
    $request->setViewId(\Kreatif\kganalytics\Settings::getValue('view_id'));
    $request->setDateRanges($dateRange);
    $request->setMetrics([$metric1]);
    // $request->setMetrics([$metric1, $metric2]);

    $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
    $body->setReportRequests([$request]);
    return $analytics->reports->batchGet($body);
}


/**
 * Parses and prints the Analytics Reporting API V4 response.
 *
 * @param An Analytics Reporting API V4 response.
 */
function printResults($reports)
{
    for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
        $report           = $reports[$reportIndex];
        $header           = $report->getColumnHeader();
        $dimensionHeaders = $header->getDimensions();
        $metricHeaders    = $header->getMetricHeader()->getMetricHeaderEntries();
        $rows             = $report->getData()->getRows();

        for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
            $row        = $rows[$rowIndex];
            $dimensions = $row->getDimensions();
            $metrics    = $row->getMetrics();
            for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                pr($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
            }

            for ($j = 0; $j < count($metrics); $j++) {
                $values = $metrics[$j]->getValues();
                for ($k = 0; $k < count($values); $k++) {
                    $entry = $metricHeaders[$k];
                    pr($entry->getName() . ": " . $values[$k] . "\n");
                }
            }
        }
    }
}