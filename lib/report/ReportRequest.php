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


use Google\Analytics\Data\V1beta\RunReportRequest;


abstract class ReportRequest
{
    protected RunReportRequest $request;
    protected string           $name;
    protected string           $groupDimensionName;
    protected string           $responseClass = ReportResponse::class;

    public function __construct(string $name, string $groupDimensionName)
    {
        $this->name               = $name;
        $this->groupDimensionName = $groupDimensionName;
        $this->request            = new RunReportRequest();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return RunReportRequest
     */
    public function getRequest(): RunReportRequest
    {
        return $this->request;
    }

    /**
     * @return string
     */
    public function getGroupDimensionName(): string
    {
        return $this->groupDimensionName;
    }

    /**
     * @return string
     */
    public function getResponseClass(): string
    {
        return $this->responseClass;
    }

    /**
     * @param string $responseClass
     */
    public function setResponseClass(string $responseClass): void
    {
        $this->responseClass = $responseClass;
    }
}