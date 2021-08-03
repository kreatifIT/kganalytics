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
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Kreatif\Helpers\Log;
use rex;


class Report
{
    const MAX_REQUESTS_PER_REPORT = 5;
    const MAX_REQUESTS_PER_MINUTE = 600;


    protected BetaAnalyticsDataClient $client;
    protected DateRange               $dateRange;
    protected string                  $propertyId;
    protected int                     $requestStartTS;
    protected int                     $doneRequestCount = 0;
    protected int                     $maxExecSeconds   = 15;
    protected bool                    $debug            = false;
    protected array                   $chunks           = [];
    protected array                   $requests         = [];
    protected array                   $results          = [];


    public static function create(DateRange $dateRange)
    {
        $caller            = get_called_class();
        $_this             = new $caller();
        $_this->dateRange  = $dateRange;
        $_this->client     = DataClient::factory();
        $_this->propertyId = Settings::getValue('property_id');

        Log::catchThrowables();
        rex::setProperty('kreatif.analytics.report.result', null);
        return $_this;
    }

    public function appendRequest(ReportRequest $request): void
    {
        $className = get_class($request);
        if (!isset($this->requests[$className])) {
            $this->requests[$className] = $request;
        }
    }

    public function batchRunReports(): Result
    {
        ini_set('max_execution_time', $this->maxExecSeconds);

        $this->requestStartTS = microtime();
        $this->prepareChunks();

        foreach ($this->chunks as $chunk) {
            $this->processChunk($chunk);
        }
        return Result::getReportResult();
    }

    private function processChunk(ReportRequestChunk $chunk)
    {
        $this->doneRequestCount += $chunk->getCount();

        $response = $this->client->batchRunReports(
            [
                'property' => 'properties/' . $this->propertyId,
                'requests' => $chunk->getRequests(),
            ]
        );

        $this->parseAnalyticsResponse($response, $chunk);
        $this->handleQuotaTiming();
    }

    private function prepareChunks()
    {
        $chunk          = new ReportRequestChunk();
        $this->chunks[] = $chunk;
        $counter        = 0;

        foreach ($this->requests as $request) {
            // split requests into chunks to 5 entries
            if (self::MAX_REQUESTS_PER_REPORT == $counter) {
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

    private function handleQuotaTiming()
    {
        $elapsedTime = microtime() - $this->requestStartTS;

        if ($elapsedTime > 50000 || $this->doneRequestCount > (self::MAX_REQUESTS_PER_MINUTE - self::MAX_REQUESTS_PER_REPORT)) {
            // if we are getting to limit the per-minute-quota lets sleep for a minute to bypass this limit
            sleep(60);
            $this->doneRequestCount = 0;
            $this->requestStartTS   = microtime();
        }
    }

    /**
     * @param BatchRunReportsResponse $reportResponse
     * @param ReportRequestChunk      $chunk
     */
    protected function parseAnalyticsResponse(BatchRunReportsResponse $reportResponse, ReportRequestChunk $chunk): void
    {
        $offsetChunk = new ReportRequestChunk();

        foreach ($reportResponse->getReports() as $reqIndex => $report) {
            /**  @var RunReportResponse $report */
            $request  = $chunk->getRequest($reqIndex);
            $response = $chunk->getResponseForIndex($reqIndex);
            $offset   = $request->getReportOffset() + ReportRequest::REPORT_ROW_LIMIT;
            $response->processReport($report);

            if ($report->getRowCount() > $offset) {
                $request->setReportOffset($offset);
                $offsetChunk->appendRequest($request);
            }
        }

        if ($offsetChunk->getCount()) {
            $this->processChunk($offsetChunk);
        }
    }
}