<?php

namespace Biigle\Modules\Export\Support\Reports;

use File;
use Exception;
use Biigle\Label;
use ReflectionClass;
use Biigle\Modules\Export\ReportType;

class ReportGenerator
{
    /**
     * Options for this report.
     *
     * @var \Illuminate\Support\Collection
     */
    public $options;

    /**
     * Source this report belongs to (e.g. a volume)
     *
     * @var mixed
     */
    protected $source;

    /**
     * Name of the report for use in text.
     *
     * @var string
     */
    protected $name;

    /**
     * Name of the report for use as (part of) a filename.
     *
     * @var string
     */
    protected $filename;

    /**
     * File extension of the report file.
     *
     * @var string
     */
    protected $extension;

    /**
     * Temporary files that are created when generating a report.
     *
     * @var array
     */
    protected $tmpFiles;

    /**
     * Cache for labels of all label trees that are used for this report.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $labels;

    /**
     * Get the report generator for the given type.
     *
     * @param string $sourceClass Class name of the source model
     * @param ReportType $type Type of the report to generate
     * @param array $options Options for the report generator
     *
     * @return ReportGenerator
     */
    public static function get($sourceClass, ReportType $type, $options = [])
    {
        if (class_exists($sourceClass)) {
            $reflect = new ReflectionClass($sourceClass);
            $sourceClass = str_plural($reflect->getShortName());
            $fullClass = __NAMESPACE__.'\\'.$sourceClass.'\\'.$type->name.'ReportGenerator';

            if (class_exists($fullClass)) {
                return new $fullClass($options);
            }
        }

        return null;
    }

    /**
     * Create a report generator instance.
     *
     * @param array $options Options for the report
     */
    public function __construct($options = [])
    {
        $this->options = collect($options);
        $this->tmpFiles = [];
    }

    /**
     * Generate the report.
     *
     * @param mixed $source Source to generate the report for (e.g. a volume)
     * @param string $path Path to write the report file to.
     */
    public function generate($source, $path)
    {
        $this->setSource($source);

        if (is_null($this->source)) {
            throw new Exception('Cannot generate report because the source does not exist.');
        }

        $directory = File::dirname($path);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        try {
            $this->generateReport($path);
        } catch (Exception $e) {
            if (File::exists($path)) {
                File::delete($path);
            }
            throw $e;
        } finally {
            array_walk($this->tmpFiles, function ($file) {
                $file->delete();
            });
        }
    }

    /**
     * Internal function to generate the report.
     *
     * (public for better testability)
     *
     * @param string $path Path to write the report file to.
     */
    public function generateReport($path)
    {
        //
    }

    /**
     * Set the source.
     *
     * @param mixed $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * Get the report name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the report filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Get the filename with extension.
     *
     * @return string
     */
    public function getFullFilename()
    {
        return "{$this->getFilename()}.{$this->extension}";
    }

    /**
     * Constructs a label name from the names of all parent labels and the label itself.
     *
     * Example: `Animalia > Annelida > Polychaeta > Buskiella sp`
     *
     * @param int  $id  Label ID
     * @return string
     */
    public function expandLabelName($id)
    {
        if (is_null($this->labels)) {
            $this->labels = collect();
        }

        if (!$this->labels->has($id)) {
            // Fetch the whole label tree for each label that wasn't already loaded.
            $labels = $this->getSiblingLabels($id);
            $this->labels = $this->labels->merge($labels)->keyBy('id');
        }

        $label = $this->labels[$id];
        $name = $label->name;

        while (!is_null($label->parent_id)) {
            // We can assume that all parents belong to the same label tree so they
            // should already be cached here.
            $label = $this->labels[$label->parent_id];
            $name = "{$label->name} > {$name}";
        }

        return $name;
    }

    /**
     * Get all labels that belong to the label tree of the given label.
     *
     * @param int $id Label ID
     * @return \Illuminate\Support\Collection
     */
    protected function getSiblingLabels($id)
    {
        return Label::select('id', 'name', 'parent_id')
            ->whereIn('label_tree_id', function ($query) use ($id) {
                $query->select('label_tree_id')
                    ->from('labels')
                    ->where('id', $id);
            })
            ->get();
    }

    /**
     * Should this report separate the output files for different label trees?
     *
     * @return bool
     */
    protected function shouldSeparateLabelTrees()
    {
        return $this->options->get('separateLabelTrees', false);
    }
}