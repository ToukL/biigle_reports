<?php

namespace Biigle\Modules\Reports;

use Illuminate\Database\Eloquent\Model;
use Biigle\Traits\HasConstantInstances;

class ReportType extends Model
{
    use HasConstantInstances;

    /**
     * The constant instances of this model.
     *
     * @var array
     */
    const INSTANCES = [
        'annotationsArea' => 'Annotations\Area',
        'annotationsBasic' => 'Annotations\Basic',
        'annotationsCsv' => 'Annotations\Csv',
        'annotationsExtended' => 'Annotations\Extended',
        'annotationsFull' => 'Annotations\Full',
        'imageLabelsBasic' => 'ImageLabels\Basic',
        'imageLabelsCsv' => 'ImageLabels\Csv',
        'videoAnnotationsCsv' => 'VideoAnnotations\Csv',
    ];

    /**
     * Don't maintain timestamps for this model.
     *
     * @var bool
     */
    public $timestamps = false;
}
