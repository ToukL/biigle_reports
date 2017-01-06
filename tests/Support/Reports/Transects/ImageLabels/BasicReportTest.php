<?php

namespace Dias\Tests\Modules\Export\Support\Reports\Transects\ImageLabels;

use App;
use File;
use Mockery;
use TestCase;
use Dias\Tests\LabelTest;
use Dias\Tests\ImageTest;
use Dias\Tests\TransectTest;
use Dias\Tests\ImageLabelTest;
use Dias\Modules\Export\Support\Exec;
use Dias\Modules\Export\Support\CsvFile;
use Dias\Modules\Export\Support\Reports\Transects\ImageLabels\BasicReport;

class BasicReportTest extends TestCase
{
    private $columns = ['image_id', 'image_filename', 'label_hierarchies'];

    public function testProperties()
    {
        $report = new BasicReport(TransectTest::make());
        $this->assertEquals('basic image label report', $report->getName());
        $this->assertEquals('basic_image_label_report', $report->getFilename());
        $this->assertEquals('xlsx', $report->getExtension());
    }

    public function testGenerateReport()
    {
        $transect = TransectTest::create();

        $il = ImageLabelTest::create([
            'image_id' => ImageTest::create([
                'transect_id' => $transect->id,
                'filename' => 'foo.jpg',
            ])->id
        ]);

        $il2 = ImageLabelTest::create([
            'image_id' => $il->image_id,
        ]);

        $root = LabelTest::create();
        $child = LabelTest::create([
            'parent_id' => $root->id,
            'label_tree_id' => $root->label_tree_id,
        ]);

        $il3 = ImageLabelTest::create([
            'image_id' => ImageTest::create([
                'transect_id' => $transect->id,
                'filename' => 'bar.jpg',
            ])->id,
            'label_id' => $child->id,
        ]);

        // for the AvailableReport
        File::shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $mock = Mockery::mock();

        $mock->shouldReceive('getPath')
            ->once()
            ->andReturn('abc');

        $mock->shouldReceive('put')
            ->once()
            ->with([$transect->name]);

        $mock->shouldReceive('put')
            ->once()
            ->with($this->columns);

        $mock->shouldReceive('put')
            ->once()
            ->with([$il->image->id, $il->image->filename, "{$il->label->name}, {$il2->label->name}"]);

        $mock->shouldReceive('put')
            ->once()
            ->with([$il3->image->id, $il3->image->filename, "{$root->name} > {$child->name}"]);

        $mock->shouldReceive('close')
            ->once();

        App::singleton(CsvFile::class, function () use ($mock) {
            return $mock;
        });

        $mock = Mockery::mock();
        $mock->code = 0;
        App::singleton(Exec::class, function () use ($mock) {
            return $mock;
        });

        with(new BasicReport($transect))->generateReport();
    }

    public function testGenerateReportSeparateLabelTrees()
    {
        $label1 = LabelTest::create();
        $label2 = LabelTest::create();

        $image = ImageTest::create();

        ImageLabelTest::create([
            'image_id' => $image->id,
            'label_id' => $label1->id,
        ]);
        ImageLabelTest::create([
            'image_id' => $image->id,
            'label_id' => $label2->id,
        ]);

        File::shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $mock = Mockery::mock();
        $mock->shouldReceive('getPath')
            ->twice()
            ->andReturn('abc', 'def');

        $mock->shouldReceive('put')
            ->once()
            ->with([$label1->tree->name]);

        $mock->shouldReceive('put')
            ->once()
            ->with([$label2->tree->name]);

        $mock->shouldReceive('put')
            ->twice()
            ->with($this->columns);

        $mock->shouldReceive('put')
            ->once()
            ->with([$image->id, $image->filename, $label1->name]);

        $mock->shouldReceive('put')
            ->once()
            ->with([$image->id, $image->filename, $label2->name]);;

        $mock->shouldReceive('close')
            ->twice();

        App::singleton(CsvFile::class, function () use ($mock) {
            return $mock;
        });

        $mock = Mockery::mock();
        $mock->code = 0;
        App::singleton(Exec::class, function () use ($mock) {
            return $mock;
        });

        $report = new BasicReport($image->transect, ['separateLabelTrees' => true]);
        $report->generateReport();
    }
}