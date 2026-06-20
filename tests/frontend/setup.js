import { config } from '@vue/test-utils';

const passthrough = {
    template: '<div><slot /></div>',
};

const menu = {
    template: '<div><slot name="activator" :props="{}" /><slot /></div>',
};

config.global.stubs = {
    'v-btn': passthrough,
    'v-card': passthrough,
    'v-chip': passthrough,
    'v-container': passthrough,
    'v-icon': passthrough,
    'v-list': passthrough,
    'v-list-item': passthrough,
    'v-menu': menu,
    'v-pagination': passthrough,
    'v-row': passthrough,
    'v-spacer': passthrough,
    'v-table': passthrough,
    'v-text-field': passthrough,
    'vocabulary-edit-dialog': passthrough,
    'vocabulary-export-dialog': passthrough,
    'vocabulary-import-dialog': passthrough,
};
