<?php

namespace Biigle\Modules\Export\Support\Reports\Projects\Annotations;

use Biigle\Modules\Export\Support\Reports\Volumes\Annotations\FullReportGenerator as ReportGenerator;

class FullReportGenerator extends AnnotationReportGenerator
{
    /**
     * The class of the volume report to use for this project report.
     *
     * @var string
     */
    protected $volumeReportClass = ReportGenerator::class;

    /**
     * Name of the report for use in text.
     *
     * @var string
     */
    protected $name = 'full annotation report';

    /**
     * Name of the report for use as (part of) a filename.
     *
     * @var string
     */
    protected $filename = 'full_annotation_report';
}