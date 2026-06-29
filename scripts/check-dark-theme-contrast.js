#!/usr/bin/env node
const fs = require('fs');
const vm = require('vm');
const path = require('path');

const root = path.resolve(__dirname, '..');
const themesPath = path.join(root, 'resources/js/themes.js');
const darkModePath = path.join(root, 'resources/sass/DarkMode.scss');
const appStylesPath = path.join(root, 'resources/sass/app.scss');
const homeStylesPath = path.join(root, 'resources/sass/Home/Home.scss');
const reviewStylesPath = path.join(root, 'resources/sass/Review/Review.scss');
const interactiveTextStylesPath = path.join(root, 'resources/sass/Text/InteractiveTextStyling.scss');

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
const appCss = fs.readFileSync(appStylesPath, 'utf8');
const homeCss = fs.readFileSync(homeStylesPath, 'utf8');
const reviewCss = fs.readFileSync(reviewStylesPath, 'utf8');
const interactiveTextCss = fs.readFileSync(interactiveTextStylesPath, 'utf8');
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
    'on-error',
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
    assertContrast(dark, 'focusIndicator', 'inputSurface', 3, failures);
    assertContrast(dark, 'text', 'hoverSurface', 4.5, failures);
    assertContrast(dark, 'text', 'selectedSurface', 4.5, failures);
    assertContrast(dark, 'on-primary', 'primary', 4.5, failures);
    assertContrast(dark, 'on-error', 'error', 4.5, failures);
}

const requiredSelectors = [
    '#app .v-application.dark,',
    '.v-overlay-container .v-theme--dark {',
    '.subheader',
    '.v-table .v-table__wrapper',
    '.v-data-table-footer',
    '.v-pagination .v-btn',
    '.v-pagination .v-btn[aria-current="true"]',
    '.table-action-button.bg-error .v-icon',
    '.v-tab.v-tab--selected',
    '.v-field',
    '.v-field--focused .v-field__outline',
    '.v-input--disabled .v-field',
    '.v-selection-control--disabled .v-switch__track',
    '.v-alert a',
    '.v-overlay__content .v-list-item--active',
    '.v-overlay__content .v-list-item:not(.v-list-item--active):hover',
    '.v-btn--icon .v-icon',
    '.v-btn--disabled',
    '#navigation-drawer',
    '.admin-settings-tabs .v-tab.v-tab--selected',
    '.v-dialog .v-card',
    '.v-tooltip > .v-overlay__content',
    '#reader-box #toolbar button.toolbar-button.v-btn',
    '#review-box {',
    '.v-theme--dark.v-date-picker',
    '.v-date-picker-month__day--selected .v-date-picker-month__day-btn',
    '.v-date-picker-month__day--today:not(.v-date-picker-month__day--selected)',
    '.v-date-picker-month__day:not(.v-date-picker-month__day--selected) .v-date-picker-month__day-btn:hover',
];

for (const selector of requiredSelectors) {
    if (!css.includes(selector)) {
        failures.push(`DarkMode.scss is missing expected shared selector: ${selector}`);
    }
}

for (const semanticActiveState of [
    '#navigation-drawer .navigation-button.v-list-item--active',
    'rgb(var(--v-theme-on-primary))',
    '.v-bottom-navigation .v-btn--active {',
    'box-shadow: inset 0 3px 0 rgb(var(--v-theme-on-primary));',
    '.v-bottom-navigation .v-btn--active .v-btn__overlay',
    '.v-btn--active:not(.v-bottom-navigation .v-btn) .v-btn__overlay',
]) {
    if (!appCss.includes(semanticActiveState)) {
        failures.push(`app.scss is missing semantic navigation state contract: ${semanticActiveState}`);
    }
}

for (const hardcodedActiveState of [
    /#navigation-drawer \.navigation-button\.v-list-item--active\s*\{[^}]*color:\s*(?:white|#fff(?:fff)?)/i,
    /\.v-bottom-navigation\s*\{[^}]*color:\s*(?:white|#fff(?:fff)?)/i,
    /\.admin-settings-tabs \.v-tab\.v-tab--selected[\s\S]{0,180}color:\s*(?:white|#fff(?:fff)?)/i,
]) {
    if (hardcodedActiveState.test(appCss)) {
        failures.push(`app.scss retains a hardcoded tab/navigation active foreground: ${hardcodedActiveState}`);
    }
}

for (const obsoleteSelector of [
    '.v-select__selections',
    '.v-theme--dark.v-card > .v-card__text',
    '.v-overlay__content .v-theme--dark.v-list-item',
    '.v-theme--dark.v-picker__body',
    '.v-date-picker-title',
]) {
    if (css.includes(obsoleteSelector)) {
        failures.push(`DarkMode.scss retains obsolete Vuetify 2 selector: ${obsoleteSelector}`);
    }
}

for (const obsoleteSelector of [
    '.v-picker.v-card.v-picker--date',
    '.v-theme--light.v-picker__body',
    '.v-date-picker-table--month',
]) {
    if (appCss.includes(obsoleteSelector)) {
        failures.push(`app.scss retains obsolete Vuetify 2 date-picker selector: ${obsoleteSelector}`);
    }
}

for (const semanticCalendarRule of [
    'color: rgb(var(--v-theme-on-primary));',
    'border: 1px solid rgb(var(--v-theme-border));',
    '#calendar .calendar-day:focus-visible',
]) {
    if (!homeCss.includes(semanticCalendarRule)) {
        failures.push(`Home calendar styles are missing semantic popup rule: ${semanticCalendarRule}`);
    }
}

if (/#calendar-popup-date\s*\{[^}]*color:\s*white;/s.test(homeCss)) {
    failures.push('Home calendar popup header must not hardcode white on the primary surface.');
}

if (css.includes('border-color: #404040')) {
    failures.push('Dark custom calendar day borders must use the semantic border token.');
}

for (const reviewAnimationRule of [
    '&.back-to-deck-animation #review-card-content',
    '&.into-the-correct-deck-animation #review-card-content',
    '&.draw-new-card-animation #review-card-content',
    'color: rgb(var(--v-theme-text));',
]) {
    if (!reviewCss.includes(reviewAnimationRule)) {
        failures.push(`Review animation styles are missing semantic card text rule: ${reviewAnimationRule}`);
    }
}

if (/#review-card[\s\S]{0,1200}color:\s*white;/i.test(reviewCss)) {
    failures.push('Review card animations must not hardcode white foreground text.');
}

if (/#review-box[\s\S]{0,500}#review-card[\s\S]{0,500}textDark/i.test(css)) {
    failures.push('Dark Review card animation overrides must not use textDark on dark card faces.');
}

if (!css.includes('#vocabulary-bottom-sheet-stage-buttons .v-btn.v-btn--active') || !css.includes('color: rgb(var(--v-theme-on-primary)) !important;')) {
    failures.push('Dark Reader vocabulary stage buttons must use on-primary on the primary active surface.');
}

if (/#vocab(?:ulary)?[\s\S]{0,220}stage-buttons[\s\S]{0,300}color:\s*white;/i.test(appCss)) {
    failures.push('Shared Reader vocabulary stage buttons must not hardcode white active text.');
}

if (!/&\.highlighted\s*\{[^}]*outline:\s*2px solid rgb\(var\(--v-theme-highlightedWordText\)\);[^}]*outline-offset:\s*1px;/s.test(interactiveTextCss)) {
    failures.push('Reader selected word state must keep a semantic outline so it is distinguishable from hover.');
}

const chipBlock = css.match(/#app \.v-application\.dark \.v-theme--dark\.v-chip,[\s\S]*?\n}\n/);
if (chipBlock && chipBlock[0].includes('textDark')) {
    failures.push('Dark chips must not use textDark on dark surfaces.');
}

const primaryChipBlock = css.match(/#app \.v-application\.dark \.v-chip\.bg-primary,[\s\S]*?\n}\n/);
if (!primaryChipBlock || !primaryChipBlock[0].includes('--v-theme-on-primary')) {
    failures.push('Dark primary chips must use the on-primary text token.');
}

const tableCellBlock = css.match(/\.v-data-table \.v-table__wrapper > table > tbody > tr > td\s*\{[\s\S]*?\n    }/);
if (!tableCellBlock || !tableCellBlock[0].includes('--v-theme-border')) {
    failures.push('Dark data-table row boundaries must use the shared border token.');
}

if (failures.length > 0) {
    console.error('Dark theme contrast check failed:');
    for (const failure of failures) {
        console.error(`- ${failure}`);
    }
    process.exit(1);
}

console.log('Dark theme contrast tokens and shared component selectors look valid.');
