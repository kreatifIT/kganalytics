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


use Google\Analytics\Data\V1beta\BatchRunReportsResponse;
use Google\Analytics\Data\V1beta\DateRange;


class Report
{
    protected $client;
    protected $propertyId;
    protected $dateRange;
    protected $debug    = false;
    protected $chunks   = [];
    protected $requests = [];
    protected $results  = [];

    public static function create(DateRange $dateRange)
    {
        $caller            = get_called_class();
        $_this             = new $caller();
        $_this->dateRange  = $dateRange;
        $_this->client     = DataClient::factory();
        $_this->propertyId = Settings::getValue('property_id');
        return $_this;
    }

    public function appendRequest(ReportRequest $request): void
    {
        $this->requests[] = $request;
    }

    public function batchRunReports(): Result
    {
        $this->prepareChunks();

        foreach ($this->chunks as $chunk) {
            $response = $this->client->batchRunReports(
                [
                    'property' => 'properties/' . $this->propertyId,
                    'requests' => $chunk->getRequests(),
                ]
            );

            // todo: paging handlen

            $this->parseAnalyticsResponse($response, $chunk);
        }
        return Result::factory();
    }

    private function prepareChunks()
    {
        $chunk          = new ReportRequestChunk();
        $this->chunks[] = $chunk;
        $counter        = 0;

        foreach ($this->requests as $index => $request) {
            // split requests into chunks to 5 entries
            if (5 == $counter) {
                $counter        = 0;
                $chunk          = new ReportRequestChunk();
                $this->chunks[] = $chunk;
            }
            ////////////////////////////////////////////////////////
            $runRequest = $request->getRequest();

            if (0 == $runRequest->getDateRanges()->count()) {
                $runRequest->setDateRanges([$this->dateRange]);
            }
            $chunk->appendRequest($request);
            $counter++;
        }
    }

    protected function parseAnalyticsResponse(BatchRunReportsResponse $reportResponse, ReportRequestChunk $chunk): void
    {
        foreach ($reportResponse->getReports() as $reqIndex => $report) {
            pr(
                "RequestIndex #$reqIndex -> Row count = " . $report->getRowCount(),
                'blue'
            );
            $response = $chunk->getResponseForIndex($reqIndex);
            $response->processReport($report);
        }
    }
}