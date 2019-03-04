<?php

namespace Biigle\Modules\Reports\Http\Controllers\Api\Volumes;

use Biigle\Volume;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Biigle\Modules\Reports\Report;
use Biigle\Modules\Reports\ReportType;
use Biigle\Modules\Reports\Jobs\GenerateReportJob;
use Biigle\Modules\Reports\Http\Controllers\Api\ReportController;

class VolumeReportController extends ReportController
{
    /**
     * Generate a volume report.
     *
     * @api {post} volumes/:id/reports Request a volume report
     * @apiGroup Reports
     * @apiName GenerateVolumeReport
     * @apiDescription Accepts only requests for annotation and image label reports.
     *
     * @apiParam {Number} id The volume ID.
     *
     * @apiParam (Required arguments) {Number} type_id The report type ID.
     *
     * @apiParam (Optional arguments) {Boolean} export_area If `true`, restrict the report to the export area of the volume.
     * @apiParam (Optional arguments) {Boolean} newest_label If `true`, restrict the report to the newest label of each annotation.
     * @apiParam (Optional arguments) {Boolean} separate_label_trees If `true`, separate annotations with labels of different label trees to different sheets of the spreadsheet.
     * @apiParam (Optional arguments) {Number} annotation_session_id ID of an annotation session of the volume. If given, only annotations belonging to the annotation session are included in the report.
     *
     * @apiPermission projectMember
     *
     * @param Request $request
     * @param int $id Volume ID
     */
    public function store(Request $request, $id)
    {
        $volume = Volume::findOrFail($id);
        $this->authorize('access', $volume);
        $this->validate($request, [
            'annotation_session_id' => "nullable|exists:annotation_sessions,id,volume_id,{$volume->id}",
            'type_id' => [
                'required',
                Rule::notIn([ReportType::videoAnnotationsCsvId()]),
                'exists:report_types,id',
            ],
        ]);

        $report = new Report;
        $report->source()->associate($volume);
        $report->type_id = $request->input('type_id');
        $report->user()->associate($request->user());
        $report->options = $this->getOptions($request);
        $report->save();

        GenerateReportJob::dispatch($report)->onQueue('high');
    }

    /**
     * Get the options of the requested report.
     *
     * @param Request $request
     * @return array
     */
    public function getOptions(Request $request)
    {
        $options = parent::getOptions($request);

        return array_merge($options, [
            'annotationSession' => $request->input('annotation_session_id'),
        ]);
    }
}
