/**
 * A mixin for a report form
 *
 * @type {Object}
 */
biigle.$component('reports.mixins.reportForm', {
    mixins: [biigle.$require('core.mixins.loader')],
    components: {
        labelTrees: biigle.$require('labelTrees.components.labelTrees'),
    },
    data: {
        allowedOptions: {},
        selectedType: '',
        selectedVariant: '',
        reportTypes: [],
        labelTrees: [],
        hasOnlyLabels: false,
        success: false,
        errors: {},
        options: {
            export_area: false,
            newest_label: false,
            separate_label_trees: false,
            only_labels: [],
            aggregate_child_labels: false,
        },
    },
    computed: {
        flatLabels() {
            let labels = [];
            this.labelTrees.forEach(function (tree) {
                Array.prototype.push.apply(labels, tree.labels);
            });

            return labels;
        },
        selectedLabels() {
            return this.flatLabels.filter(function (label) {
                return label.selected;
            });
        },
        selectedLabelsCount() {
            return this.selectedLabels.length;
        },
        variants() {
            let variants = {};
            this.reportTypes.forEach(function (type) {
                let fragments = type.name.split('\\');
                if (!variants.hasOwnProperty(fragments[0])) {
                    variants[fragments[0]] = [];
                }
                variants[fragments[0]].push(fragments[1]);
            });

            return variants;
        },
        availableReportTypes() {
            let types = {};
            this.reportTypes.forEach(function (type) {
                types[type.name] = type.id;
            });

            return types;
        },
        selectedReportTypeId() {
            return this.availableReportTypes[this.selectedType + '\\' + this.selectedVariant];
        },
        availableVariants() {
            return this.variants[this.selectedType];
        },
        onlyOneAvailableVariant() {
            return this.availableVariants.length === 1;
        },
        selectedOptions() {
            let options = {};
            this.allowedOptions[this.selectedType].forEach(function (allowed) {
                options[allowed] = this.options[allowed];
            }, this);

            options.type_id = this.selectedReportTypeId;

            return options;
        },
    },
    methods: {
        request(id, resource) {
            if (this.loading) return;
            this.success = false;
            this.startLoading();
            resource.save({id: id}, this.selectedOptions)
                .then(this.submitted, this.handleError)
                .finally(this.finishLoading);
        },
        submitted() {
            this.success = true;
            this.errors = {};
        },
        handleError(response) {
            if (response.status === 422) {
                this.errors = response.data;
            } else {
                biigle.$require('messages.store').handleErrorResponse(response);
            }
        },
        selectType(type) {
            this.selectedType = type;
            if (this.availableVariants.indexOf(this.selectedVariant) === -1) {
                this.selectedVariant = this.availableVariants[0];
            }
        },
        wantsType(type) {
            return this.selectedType === type;
        },
        wantsVariant(variant) {
            if (Array.isArray(variant)) {
                return variant.indexOf(this.selectedVariant) !== -1;
            }

            return this.selectedVariant === variant;
        },
        hasError(key) {
            return this.errors.hasOwnProperty(key);
        },
        getError(key) {
            return this.errors[key] ? this.errors[key].join(' ') : '';
        },
        wantsCombination(type, variant) {
            return this.wantsType(type) && this.wantsVariant(variant);
        },
    },
    watch: {
        selectedLabels(labels) {
            this.options.only_labels = labels.map(function (label) {
                return label.id;
            });
        },
        hasOnlyLabels(has) {
            if (!has) {
                this.flatLabels.forEach(function (label) {
                    label.selected = false;
                });
            }
        },
    },
    created() {
        this.reportTypes = biigle.$require('reports.reportTypes');
        this.selectedType = Object.keys(this.variants)[0];
        this.selectedVariant = this.availableVariants[0];
        this.labelTrees = biigle.$require('reports.labelTrees');
    },
});
