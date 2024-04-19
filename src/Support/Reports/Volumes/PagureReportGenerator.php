<?php

namespace Biigle\Modules\Reports\Support\Reports\Volumes;

use Biigle\Annotation;
use Biigle\Image;
use Biigle\Label;
use Biigle\LabelSource;
use Biigle\Shape;
use Biigle\Video;
use DB;
use Exception;

abstract class PagureReportGenerator extends VolumeReportGenerator
{
    /**
     * File extension of the report file.
     *
     * @var string
     */
    public $extension = 'yaml';

    /**
     * Labels that have been used in this volume.
     *
     * @var Illuminate\Support\Collection
     */
    protected $labels;

    /**
     * Users that have been used in this volume.
     *
     * @var Illuminate\Support\Collection
     */
    protected $users;

    /**
     * All labels that should be contained in the iFDO.
     *
     * @var array
     */
    protected $imageAnnotationLabels = [];

    /**
     * All users that should be contained in the iFDO.
     *
     * @var array
     */
    protected $imageAnnotationCreators = [];

    /**
     * iFDO image-annotation arrays for each image of the volume.
     *
     * @var array
     */
    protected $imageSetItems = [];

    /**
     * Label source model for the WoRMS database.
     *
     * @var LabelSource
     */
    protected $wormsLabelSource;

    /**
     * Generate the report.
     *
     * @param string $path Path to the report file that should be generated
     */
    public function generateReport($path)
    {
        $this->wormsLabelSource = LabelSource::where('name', 'worms')->first();
        $this->users = $this->getUsers()->keyBy('id');
        $this->labels = $this->getLabels()->keyBy('id');

        $this->query()->eachById([$this, 'processFile']);

        $ifdo = $this->source->getIfdo();

        if (is_null($ifdo)) {
            throw new Exception("No iFDO file found for the volume.");
        }

        $creators = array_map(function ($user) {
            return [
                'id' => $user->uuid,
                'name' => "{$user->firstname} {$user->lastname}",
            ];
        }, $this->imageAnnotationCreators);

        if (!empty($creators)) {
            $ifdo['image-set-header']['image-annotation-creators'] = array_merge(
                $ifdo['image-set-header']['image-annotation-creators'] ?? [],
                $creators
            );
        }

        $labels = array_map(function ($label) {
            if ($this->shouldConvertWormsId($label)) {
                return [
                    'id' => $this->getWormsUrn($label),
                    'name' => $label->name,
                ];
            }

            return [
                'id' => $label->id,
                'name' => $label->name,
            ];
        }, $this->imageAnnotationLabels);

        if (!empty($labels)) {
            $ifdo['image-set-header']['image-annotation-labels'] = array_merge(
                $ifdo['image-set-header']['image-annotation-labels'] ?? [],
                $labels
            );
        }

        if (!empty($this->imageSetItems)) {
            $keys = array_keys($this->imageSetItems);

            $ifdo['image-set-items'] = $ifdo['image-set-items'] ?? [];

            foreach ($keys as $key) {
                $this->mergeImageSetItem($key, $ifdo['image-set-items']);
            }
        }

        $this->writeYaml($ifdo, $path);
    }

    /**
     * Assemble a new DB query for the volume of this report.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    abstract protected function query();

    /**
     * Get all users who annotated in the volume.
     *
     * @return Illuminate\Support\Collection
     */
    abstract protected function getUsers();

    /**
     * Get all labels that were used in the volume.
     *
     * @return Illuminate\Support\Collection
     */
    abstract protected function getLabels();

    /**
     * Create the image-set-item entry for an image or video.
     *
     * @param Image|Video $file
     *
     */
    abstract public function processFile(Image|Video $file);

    /**
     * Write the report YAML file.
     *
     * @param array $content
     * @param string $path
     */
    protected function writeYaml(array $content, string $path)
    {
        yaml_emit_file($path, $content);
    }

    /**
     * Determine if the label ID should be converted to a WoRMS URN.
     *
     * @param Label $label
     *
     * @return bool
     */
    protected function shouldConvertWormsId(Label $label)
    {
        return $this->wormsLabelSource && $label->label_source_id === $this->wormsLabelSource->id;
    }

    /**
     * Get the WoRMS URN for a label (if it has one).
     *
     * @param Label $label
     *
     * @return string
     */
    protected function getWormsUrn($label)
    {
        return "urn:lsid:marinespecies.org:taxname:{$label->source_id}";
    }

    /**
     * Determine if an iFDO item is a single object or an array of objects.
     * Both are allowed for images. Only the latter should be the case for videos.
     *
     * @param array $item
     *
     * @return boolean
     */
    protected function isArrayItem($item)
    {
        return !empty($item) && array_reduce(array_keys($item), function ($carry, $key) {
            return $carry && is_numeric($key);
        }, true);
    }

    /**
     * Merge an image-set-items item of the original iFDO with the item generated by this
     * report.
     *
     * @param string $key Filename key of the item (guaranteed to be in
     * $this->imageSetItems).
     * @param array $ifdoItems image-set-items of the original iFDO
     */
    protected function mergeImageSetItem($key, &$ifdoItems)
    {
        if (array_key_exists($key, $ifdoItems)) {
            if ($this->isArrayItem($ifdoItems[$key])) {
                if ($this->isArrayItem($this->imageSetItems[$key])) {
                    $ifdoItems[$key][0] = array_merge_recursive(
                        $ifdoItems[$key][0],
                        $this->imageSetItems[$key][0]
                    );
                } else {
                    $ifdoItems[$key][0] = array_merge_recursive(
                        $ifdoItems[$key][0],
                        $this->imageSetItems[$key]
                    );
                }
            } else {
                $ifdoItems[$key] = array_merge_recursive(
                    $ifdoItems[$key],
                    $this->imageSetItems[$key]
                );
            }
        } else {
            $ifdoItems[$key] = $this->imageSetItems[$key];
        }
    }

    /**
     * Get an iFDO geometry name string for an annotation.
     *
     * @param Annotation $annotation
     *
     * @return string
     */
    protected function getGeometryName(Annotation $annotation)
    {
        if ($annotation->shape_id === Shape::pointId()) {
            return 'single-pixel';
        } elseif ($annotation->shape_id === Shape::lineId()) {
            return 'polyline';
        } elseif ($annotation->shape_id === Shape::circleId()) {
            return 'circle';
        } elseif ($annotation->shape_id === Shape::rectangleId()) {
            return 'rectangle';
        } elseif ($annotation->shape_id === Shape::ellipseId()) {
            return 'ellipse';
        } elseif ($annotation->shape_id === Shape::wholeFrameId()) {
            return 'whole-image';
        } else {
            return 'polygon';
        }
    }
}
