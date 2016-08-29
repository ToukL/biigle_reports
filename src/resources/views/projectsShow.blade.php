<div class="panel panel-default" data-ng-controller="ExportController">
    <div class="panel-heading clearfix">
        <a href="{{route('manual-tutorials-export', 'reports-schema')}}" class="pull-right" title="Show more information on reports" target="_blank"><span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span></a>
        Reports
    </div>
    <div class="panel-body" data-ng-switch="isRequested()">
        <div class="ng-cloak alert alert-success" data-ng-switch-when="true">
            The <strong data-ng-bind="getSelectedName()"></strong> report will be prepared for you. You will get notified by email when it is ready.
        </div>
        <form class="form-inline" data-ng-switch-default="">
            <div class="form-group">
                <select id="type" name="type" data-ng-model="selected.option" class="form-control">
                    <option value="0" selected="">annotations (basic)</option>
                    <option value="1">annotations (extended)</option>
                    <option value="2">annotations (full)</option>
                    <option value="3">annotations (csv)</option>
                    <option value="4">image labels</option>
                </select>
            </div>
            <div class="checkbox ng-cloak" data-ng-if="canBeRestricted()">
                <label>
                    <input type="checkbox" data-ng-model="selected.restrict" title="Discard annotations outside of the annotation area"> Restrict to export area
                </label>
            </div>
            <div class="form-group">
                <input class="btn btn-default" type="submit" name="request" value="Request report" data-ng-click="requestReport()" title="Request a new @{{selected.type}} report">
            </div>
            <div data-ng-switch="selected.index">
                <div class="help-block" data-ng-switch-when="0">
                    The basic annotation report contains graphical plots of abundances of the different annotation labels for each transect of this project (as PDF).
                </div>
                <div class="help-block ng-cloak" data-ng-switch-when="1">
                    The extended annotation report lists the abundances of annotation labels for each image in all transects of this project (as XLSX).
                </div>
                <div class="help-block ng-cloak" data-ng-switch-when="2">
                    The full annotation report lists the labels, shape and coordinates of all annotations in all transects of this project (as XLSX).
                </div>
                <div class="help-block ng-cloak" data-ng-switch-when="3">
                    The CSV annotation report is intended for subsequent processing and lists the annotation labels of all transects of this project at the highest possible resolution (as CSV files in a ZIP archive).
                </div>
                <div class="help-block ng-cloak" data-ng-switch-when="4">
                    The image label report lists the image labels of all images of all transects of this project (as XLSX).
                </div>
            </div>
            <div class="ng-cloak help-block" data-ng-if="selected.restrict && canBeRestricted()">
                Annotations that are outside of the export area will be discarded for this report.
            </div>
        </form>
    </div>
</div>
