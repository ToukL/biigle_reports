import Form from './mixins/reportForm';
import ProjectsApi from './api/projectReports';

/**
 * The form for requesting a project report
 */
export default {
    mixins: [Form],
    data: {
        projectId: null,
        allowedOptions: {
            'Annotations': [
                'export_area',
                'newest_label',
                'separate_label_trees',
                'only_labels',
                'aggregate_child_labels',
            ],
            'ImageLabels': [
                'separate_label_trees',
                'only_labels',
            ],
            'VideoAnnotations': [
                'separate_label_trees',
                'only_labels',
            ],
        },
    },
    methods: {
        submit() {
            this.request(this.projectId, ProjectsApi);
        }
    },
    created() {
        this.projectId = biigle.$require('reports.projectId');
    },
};
