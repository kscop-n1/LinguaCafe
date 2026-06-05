# CSS Warning Audit - 2026-06-05

Scope: classify Firefox CSS parse warnings reported against the built `public/build/assets/app-HS_Gkqv1.css` bundle without editing generated CSS and without broad CSS rewrites.

Build inspected:

- `npm run production -- --sourcemap` completed successfully.
- Vite produced `public/build/assets/app-HS_Gkqv1.css` and a JS sourcemap, but no CSS sourcemap. CSS mapping below therefore uses generated-CSS value extraction plus source searches under `resources/`.
- `vite.config.ts` does not enable sourcemaps by default. `vite.config.mjs` is the active production config for this checkout and only sets `chunkSizeWarningLimit` under `build`.

## Summary

Firefox console CSS warnings fall into four buckets:

- browser/vendor compatibility noise from Vuetify, MDI, normalize-style reset CSS, and local scrollbar/autofill selectors;
- third-party CSS noise from Vuetify and MDI icon CSS;
- real app CSS invalid values in a small number of source files;
- values that need manual UI verification because they are valid only if runtime CSS variables resolve to valid CSS values.

No generated `public/build` CSS was edited in this pass.

## Real App CSS Invalid Values

These are source-level issues in `resources/` that map to Firefox parse-warning families and should be fixed in a small follow-up patch.

| Warning family | Source | Property | Current value | Risk | Recommended fix |
| --- | --- | --- | --- | --- | --- |
| margin-left / margin-right | `resources/sass/Text/InteractiveTextStyling.scss:180-181` | `margin-left`, `margin-right` | `none !important` | Invalid CSS. Browser ignores the declarations in plain-text spaceless-language mode, leaving previous margins in effect until later nested selectors override some states. | Replace with `0 !important`. |
| padding-left / padding-right | `resources/sass/Text/InteractiveTextStyling.scss:182-183` | `padding-left`, `padding-right` | `none !important` | Invalid CSS. Browser ignores the declarations in plain-text spaceless-language mode, leaving prior padding in effect until later nested selectors override some states. | Replace with `0 !important`. |
| width | `resources/sass/Vocabulary/Vocabulary.scss:25` | `width` | `100` | Invalid CSS because length unit or `%` is missing. The vocabulary search field may not occupy the intended full width. This matches a known vocabulary/mobile surface and should be treated as real app CSS debt. | Replace with `100%`. |
| display | `resources/sass/Text/VocabularySearchBox.scss:26` | `display` | `inine-block` | Typo. Browser ignores declaration; search result title falls back to normal block behavior from surrounding rules. | Replace with `inline-block`. |
| cursor | `resources/sass/app.scss:59` | `cursor` | `normal` | Invalid cursor value. Browser ignores it on the logo text. Low visual risk. | Replace with `default` or remove the declaration. |
| cursor | `resources/sass/Library/JellyfinSubtitleList.scss:31` | `cursor` | `normal` | Invalid cursor value. Browser ignores it on the no-subtitle label. Low visual risk. | Replace with `default` or remove the declaration. |

Generated CSS evidence from `app-HS_Gkqv1.css` included `none!important` for margin/padding, `display: inine-block`, and two `cursor: normal` values. Those values map directly to the source files above.

## Needs Manual UI Verification

These values are not proven invalid statically. They depend on runtime settings or browser support.

| Warning family | Source | Property/value | Classification | Verification |
| --- | --- | --- | --- | --- |
| padding-left / padding-right and border widths | `resources/sass/Text/InteractiveTextStyling.scss`, runtime values from `resources/js/services/TextStylingService.js` | `var(--interactive-text-...-padding-left/right)`, border width variables | Needs manual UI verification. The service currently appends `px` or uses `0px`, which is valid when settings are numeric. | Verify text styling settings with default and customized values; check browser console after loading reader text and user settings text sample. |
| opacity-like transparency | `resources/sass/Text/InteractiveTextStyling.scss:25,70`, runtime values from `TextStylingService.js:101` | `color-mix(... transparent var(--...-background-transparency))` where service writes percentages | Needs manual UI verification, not an opacity parse bug. Percentage is valid in `color-mix()` but would be invalid for the `opacity` property. | Verify custom background transparency values at 0, 50, and 100 in reader/sample UI. |
| max-height | `node_modules/vuetify/dist/vuetify.css` through generated bundle | `max-height: calc-size(max-content,size)` | Third-party/browser support warning. This is Vuetify CSS for time picker internals, not app CSS. | No app fix. Only revisit if a time picker screen is broken in Firefox. |

## Harmless Browser / Vendor Compatibility Noise

These warnings should not be treated as app bugs and should not trigger prefix removal:

- `::-webkit-scrollbar`, `::-webkit-scrollbar-thumb`, and related selectors in `resources/sass/app.scss` and `resources/sass/Review/Review.scss`. Firefox may warn or ignore WebKit scrollbar selectors. They are browser-specific enhancements.
- `-webkit-text-fill-color` and `-webkit-box-shadow` in `resources/sass/app.scss` and `resources/sass/DarkMode.scss`. These target WebKit autofill/text rendering behavior and are harmless in Firefox.
- Vuetify reset/normalization selectors such as `button::-moz-focus-inner`, `button:-moz-focusring`, `select::-ms-expand`, `::-webkit-file-upload-button`, and `::-ms-clear`. These are third-party compatibility rules.
- MDI icon CSS prefixes such as `-webkit-transform`, `-ms-transform`, `-ms-filter`, and `@-webkit-keyframes`. These are third-party icon-font compatibility rules.
- `display: -webkit-box` and `-webkit-line-clamp` in Vuetify. These are browser-specific line clamp rules and can be ignored unless the related component visually fails.
- Firefox Fingerprinting Protection console warnings. These are browser privacy/runtime warnings, not CSS source issues.

## Warning Family Classification

### gap / row-gap / column-gap

Classification: mostly harmless framework utility noise plus valid app CSS.

Evidence from generated CSS:

- Vuetify utility classes generate `gap`, `row-gap`, and `column-gap` values from `0px` through `64px`, including `auto!important`.
- App source uses valid explicit gaps such as `gap: 8px`, `gap: 4px`, `gap: 16px`, `column-gap: 24px`, and `row-gap: 2px`.

Risk: low for app code. If Firefox reports `gap: auto` as invalid, that is generated Vuetify utility CSS and should be classified as third-party CSS noise unless an app element explicitly applies such a class and breaks layout.

Recommended action: no source change now. If a specific layout breaks, inspect the rendered element class list before changing app CSS.

### opacity

Classification: mostly valid app/framework CSS plus generated third-party/icon-font parser artifacts.

Evidence:

- App source uses valid opacity values such as `0`, `.08`, `.45`, `.55`, `.14`, and `1`.
- Vuetify generated CSS uses CSS variables and `calc()` expressions for theme overlay opacity; these are expected framework CSS.
- The source search found no `opacity: 50%` style app declarations.
- Generated extraction saw a few false matches caused by icon-font content inside the minified MDI CSS, not real `opacity` declarations.

Risk: low. Manual verification only for text/background transparency because the app uses percentage values inside `color-mix()`, not in `opacity`.

Recommended action: no source change now.

### max-height

Classification: third-party/browser support noise plus valid app CSS.

Evidence:

- App source uses valid `max-height: 440px`, `max-height: calc(...)`, `max-height: 100%`, `max-width: none`, etc.
- Generated CSS includes Vuetify `max-height: calc-size(max-content,size)`, which can produce Firefox parse warnings depending on support level.

Risk: low unless a Vuetify time picker or related component visually fails.

Recommended action: no app source change now.

### margin-left / margin-right and padding-left / padding-right

Classification: real app CSS invalid value for the `none` declarations; other values are valid.

Evidence:

- `resources/sass/Text/InteractiveTextStyling.scss:180-183` uses `none !important` for margin and padding properties.
- Generated CSS contains corresponding `none!important` declarations.
- Other margin/padding source values are valid lengths, percentages, `auto`, or CSS variables with numeric `px` values supplied by `TextStylingService`.

Risk: medium in plain-text mode for spaceless languages because intended margin/padding reset can be ignored.

Recommended action: small targeted fix from `none` to `0`.

### display

Classification: real app CSS invalid value for one typo; browser/vendor compatibility noise for `-webkit-box`.

Evidence:

- `resources/sass/Text/VocabularySearchBox.scss:26` uses `display: inine-block`.
- Generated CSS contains `display: inine-block`.
- Generated `display: -webkit-box` comes from Vuetify line-clamp CSS and is compatibility noise, not app CSS.

Risk: low to medium for vocabulary search result title layout.

Recommended action: small targeted fix to `inline-block`.

### width

Classification: real app CSS invalid value for one missing unit; other suspicious generated values are framework CSS or valid CSS.

Evidence:

- `resources/sass/Vocabulary/Vocabulary.scss:25` uses `width: 100`.
- Generated CSS contains many valid widths plus framework values such as `thin`, `min-content`, and CSS variables.
- `width: none` in generated extraction is from third-party/framework utility CSS, not found in app source.

Risk: medium because this is on the Vocabulary search field and can contribute to mobile layout inconsistencies.

Recommended action: small targeted fix to `width: 100%`.

### cursor

Classification: real app CSS invalid value for `cursor: normal`; other cursor values are valid.

Evidence:

- `resources/sass/app.scss:59` uses `cursor: normal` on `#logo span`.
- `resources/sass/Library/JellyfinSubtitleList.scss:31` uses `cursor: normal` on `#no-subtitle-found-label`.
- Generated CSS contains two `cursor: normal` values.

Risk: low. Browser ignores invalid cursor declaration and uses inherited/default cursor behavior.

Recommended action: replace with `cursor: default` or remove the declarations.

## Searches Run

Key source searches:

```sh
rg -n "margin-left:\\s*none|margin-right:\\s*none|padding-left:\\s*none|padding-right:\\s*none|width:\\s*100\\s*;|opacity:\\s*[0-9.]+%|max-height:\\s*(none|auto)\\s*;|display:\\s*(hidden|default|block;)\\s*;|cursor:\\s*hand|gap:\\s*none|row-gap:\\s*none|column-gap:\\s*none" resources/sass resources/js --glob '*.scss' --glob '*.vue' --glob '*.css'
rg -n "display:\\s*inine-block|cursor:\\s*normal|width:\\s*none|width:\\s*100\\s*;|margin-left:\\s*none|padding-left:\\s*none|margin-right:\\s*none|padding-right:\\s*none" resources/sass resources/js --glob '*.scss' --glob '*.vue' --glob '*.css'
```

Generated CSS property extraction was run against `public/build/assets/app-HS_Gkqv1.css` to summarize unique values for `gap`, `row-gap`, `column-gap`, `opacity`, `max-height`, `margin-left`, `margin-right`, `padding-left`, `padding-right`, `display`, `width`, and `cursor`.

## Recommended Next Patch

Keep the fix small and source-only:

- `resources/sass/Text/InteractiveTextStyling.scss`: replace four `none !important` margin/padding values with `0 !important`.
- `resources/sass/Vocabulary/Vocabulary.scss`: replace `width: 100;` with `width: 100%;`.
- `resources/sass/Text/VocabularySearchBox.scss`: replace `display: inine-block;` with `display: inline-block;`.
- `resources/sass/app.scss` and `resources/sass/Library/JellyfinSubtitleList.scss`: replace `cursor: normal;` with `cursor: default;` or remove the declarations.

After that patch, run:

```sh
npm run production
npm run check:migration
git diff --check
```

## Fix Status - 2026-06-05

Fixed in the source CSS only; generated `public/build` CSS was not edited directly.

- `resources/sass/Text/InteractiveTextStyling.scss`: replaced plain-text spaceless-language `margin-left`, `margin-right`, `padding-left`, and `padding-right` values from invalid `none !important` to `0 !important`.
- `resources/sass/Vocabulary/Vocabulary.scss`: replaced invalid `width: 100;` with `width: 100%;` for `#vocabulary-search-field`.
- `resources/sass/Text/VocabularySearchBox.scss`: fixed `display: inine-block;` typo to `display: inline-block;`.
- `resources/sass/app.scss`: replaced invalid `cursor: normal;` with `cursor: default;` on `#logo span`.
- `resources/sass/Library/JellyfinSubtitleList.scss`: replaced invalid `cursor: normal;` with `cursor: default;` on `#no-subtitle-found-label`.

Not changed:

- third-party Vuetify/MDI CSS warnings;
- browser/vendor compatibility selectors and prefixes;
- runtime CSS variable warnings that require manual UI verification;
- generated files under `public/build` by hand.
