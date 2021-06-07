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


class ReportRequestChunk
{

    protected $requests = [];


    public function appendRequest(ReportRequest $request)
    {
        $this->requests[] = $request;
    }

    public function getRequests(): array
    {
        $collection = [];
        foreach ($this->requests as $request) {
            $collection[] = $request->getRequest();
        }
        return $collection;
    }

    public function getResponseForIndex(int $index): ReportResponse
    {
        $request   = $this->requests[$index];
        $className = $request->getResponseClass();
        return new $className($request->getName(), $request->getGroupDimensionName());
    }
}