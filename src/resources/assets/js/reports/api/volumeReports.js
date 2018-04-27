/**
 * Resource for requesting reports for volumes
 *
 * var resource = biigle.$require('reports.api.volumeReports');
 *
 * Request a basic annotation report:
 *
 * resource.save({id: 1}, {
 *     type_id: 2,
 *     export_area: 1,
 *     separate_label_trees: 0,
 *     annotation_session_id: 23,
 * }).then(...)
 *
 */
biigle.$declare('reports.api.volumeReports', Vue.resource('/api/v1/volumes{/id}/reports'));