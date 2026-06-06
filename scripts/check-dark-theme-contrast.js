#!/usr/bin/env node
const fs = require('fs');
const vm = require('vm');
const path = require('path');

const root = path.resolve(__dirname, '..');
const themesPath = path.join(root, 'resources/js/themes.js');
const darkModePath = path.join(root, 'resources/sass/DarkMode.scss');

function loadThemes() {
    const source = fs.readFileSync(themesPath, 'utf8').replace(/export\s+default/, 'module.exports =');
    const sandbox = { module: { exports: {} }, exports: {} };
    vm.runInNewContext(source, sandbox, { filename: themesPath });
    return sandbox.module.exports;
}

function hexToRgb(hex) {
    const clean = hex.replace('#', '').trim();
    if (!/^[0-9a-f]{6}$/i.test(clean)) {
        throw new Error(`Unsupported color value: ${hex}`);
    }
    return [0, 2, 4].map((offset) => parseInt(clean.slice(offset, offset + 2), 16) / 255);
}

function channelToLinear(value) {
    return value <= 0.03928 ? value / 12.92 : Math.pow((value + 0.055) / 1.055, 2.4);
}

function luminance(hex) {
    const [r, g, b] = hexToRgb(hex).map(channelToLinear);
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function contrast(foreground, background) {
    const a = luminance(foreground);
    const b = luminance(background);
    const lighter = Math.max(a, b);
    const darker = Math.min(a, b);
    return (lighter + 0.05) / (darker + 0.05);
}

function assertContrast(theme, foregroundKey, backgroundKey, minimum, failures) {
    const ratio = contrast(theme[foregroundKey], theme[backgroundKey]);
    if (ratio < minimum) {
        failures.push(`${foregroundKey} on ${backgroundKey} is ${ratio.toFixed(2)}:1; expected >= ${minimum}:1`);
    }
}

const themes = loadThemes();
const dark = themes.dark;
const css = fs.readFileSync(darkModePath, 'utf8');
const failures = [];

for (const token of [
    'background',
    'foreground',
    'surface',
    'surfaceElevated',
    'inputSurface',
    'border',
    'customBorder',
    'divider',
    'hoverSurface',
    'selectedSurface',
    'focusIndicator',
    'primary',
    'on-primary',
    'text',
    'textSecondary',
    'textMuted',
    'textDisabled',
    'icon',
    'iconDisabled',
]) {
    if (!dark[token]) {
        failures.push(`Missing dark theme token: ${token}`);
    }
}

if (failures.length === 0) {
    assertContrast(dark, 'text', 'background', 4.5, failures);
    assertContrast(dark, 'text', 'foreground', 4.5, failures);
    assertContrast(dark, 'text', 'surface', 4.5, failures);
    assertContrast(dark, 'text', 'surfaceElevated', 4.5, failures);
    assertContrast(dark, 'text', 'inputSurface', 4.5, failures);
    assertContrast(dark, 'textSecondary', 'foreground', 4.5, failures);
    assertContrast(dark, 'textMuted', 'foreground', 3, failures);
    assertContrast(dark, 'textDisabled', 'foreground', 3, failures);
    assertContrast(dark, 'icon', 'foreground', 3, failures);
    assertContrast(dark, 'iconDisabled', 'foreground', 3, failures);
    assertContrast(dark, 'border', 'foreground', 3, failures);
    assertContrast(dark, 'customBorder', 'foreground', 3, failures);
    assertContrast(dark, 'focusIndicator', 'foreground', 3, failures);
    assertContrast(dark, 'on-primary', 'primary', 4.5, failures);
}

const requiredSelectors = [
    '#app .v-application.dark {',
    '.subheader',
    '.v-table .v-table__wrapper',
    '.v-data-table-footer',
    '.v-pagination .v-btn',
    '.v-tab.v-tab--selected',
    '.v-field',
    '.v-btn--icon .v-icon',
    '.v-btn--disabled',
    '#navigation-drawer',
    '.v-dialog .v-card',
    '.v-tooltip > .v-overlay__content',
    '#reader-box #toolbar button.toolbar-button.v-btn',
];

for (const selector of requiredSelectors) {
    if (!css.includes(selector)) {
        failures.push(`DarkMode.scss is missing expected shared selector: ${selector}`);
    }
}

const chipBlock = css.match(/#app \.v-application\.dark \.v-theme--dark\.v-chip,[\s\S]*?\n}\n/);
if (chipBlock && chipBlock[0].includes('textDark')) {
    failures.push('Dark chips must not use textDark on dark surfaces.');
}

if (failures.length > 0) {
    console.error('Dark theme contrast check failed:');
    for (const failure of failures) {
        console.error(`- ${failure}`);
    }
    process.exit(1);
}

console.log('Dark theme contrast tokens and shared component selectors look valid.');
