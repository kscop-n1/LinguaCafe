const fs = require('fs');
const path = require('path');

const ROOT_DIR = path.join(__dirname, '..');
const SCAN_DIRS = ['resources', 'app'];
const EXCLUDE_DIRS = ['node_modules', 'vendor', 'storage', 'public/build', '.git', 'resources/js/PrimeVueAura', 'resources/js/stores'];
const EXCLUDE_FILES = ['PatchNotes.vue'];
const FILE_EXTENSIONS = ['.vue', '.blade.php'];
const MAX_PER_CHECK = 40;

const CHECKS = [
  {
    id: 'custom-v-model-value-prop',
    desc: 'props named value; review custom v-model contract for modelValue/update:modelValue',
    regex: /props\s*:\s*(?:\[[\s\S]{0,400}["']value["']|{[\s\S]{0,700}\bvalue\s*:)/g,
  },
  {
    id: 'options-value-field',
    desc: 'value: fields; classify business data vs Vue 2 v-model or Vuetify 2 header residue',
    regex: /\bvalue\s*:/g,
  },
  {
    id: 'headers-text-value',
    desc: 'headers/text/value; Vuetify 3 data-table headers use title/key',
    regex: /headers\s*[:=]|(?:\btext\s*:[\s\S]{0,180}\bvalue\s*:)|(?:\bvalue\s*:[\s\S]{0,180}\btext\s*:)/g,
  },
  {
    id: 'legacy-input-listener',
    desc: '@input listeners; review Vuetify 3 update:modelValue/v-model migration',
    regex: /(?:@|v-on:)input\s*=/g,
  },
  {
    id: 'legacy-change-listener',
    desc: '@change listeners; review Vuetify 3 update events',
    regex: /(?:@|v-on:)change\s*=/g,
  },
  {
    id: 'v-data-table',
    desc: 'v-data-table usage; review props, headers, item slots, and events against Vuetify 3',
    regex: /<v-data-table\b/g,
  },
  {
    id: 'v-pagination',
    desc: 'v-pagination usage; review v-model/update:modelValue contract',
    regex: /<v-pagination\b/g,
  },
  {
    id: 'vuetify-selection-controls',
    desc: 'v-select/v-autocomplete/v-combobox usage; review item-title/item-value/modelValue contracts',
    regex: /<v-(?:select|autocomplete|combobox)\b/g,
  },
  {
    id: 'list-item-direct-prepend-content',
    desc: 'v-list-item with direct icon/image/flag-like content; review Vuetify 3 #prepend slot',
    regex: /<v-list-item(?=[\s>])[\s\S]{0,500}(?:<v-icon\b|<v-img\b|<img\b|flag)/g,
  },
  {
    id: 'old-visual-props',
    desc: 'old Vuetify visual props; review dense/outlined/text/small/large/dark/light replacements',
    regex: /<v-[^>\n]*\s(?:dense|outlined|text|small|large|dark|light)(?=\s|>|=)/g,
  },
  {
    id: 'item-value',
    desc: 'item-value usage; valid in Vuetify 3, but review changed item contract',
    regex: /\b:?item-value\s*=/g,
  },
  {
    id: 'named-v-model',
    desc: 'named v-model usage; verify child emits update:<prop>',
    regex: /\bv-model:[a-zA-Z0-9_-]+/g,
  },
  {
    id: 'table-row-events',
    desc: 'data-table row/pagination events; verify Vuetify 3 signatures',
    regex: /(?:@|v-on:)(?:click:row|pagination)\s*=/g,
  },
];

const findings = new Map(CHECKS.map(check => [check.id, []]));

function getLineNumber(content, index) {
  return content.slice(0, index).split('\n').length;
}

function addFinding(check, relPath, content, index) {
  const bucket = findings.get(check.id);
  if (bucket.length >= MAX_PER_CHECK) {
    return;
  }

  const lineNumber = getLineNumber(content, index);
  const line = (content.split('\n')[lineNumber - 1] || '').trim();
  bucket.push({ relPath, lineNumber, line });
}

function walkDir(dir, callback) {
  const files = fs.readdirSync(dir);
  for (const file of files) {
    const fullPath = path.join(dir, file);
    const relPath = path.relative(ROOT_DIR, fullPath).replace(/\\/g, '/');

    if (EXCLUDE_DIRS.some(ex => relPath === ex || relPath.startsWith(`${ex}/`))) {
      continue;
    }

    if (EXCLUDE_FILES.includes(path.basename(fullPath))) {
      continue;
    }

    const stat = fs.statSync(fullPath);
    if (stat.isDirectory()) {
      walkDir(fullPath, callback);
      continue;
    }

    if (FILE_EXTENSIONS.includes(path.extname(fullPath).toLowerCase())) {
      callback(fullPath, relPath);
    }
  }
}

for (const scanDir of SCAN_DIRS) {
  const fullScanPath = path.join(ROOT_DIR, scanDir);
  if (!fs.existsSync(fullScanPath)) {
    continue;
  }

  walkDir(fullScanPath, (filePath, relPath) => {
    const content = fs.readFileSync(filePath, 'utf8');

    for (const check of CHECKS) {
      check.regex.lastIndex = 0;
      let match;
      while ((match = check.regex.exec(content)) !== null) {
        addFinding(check, relPath, content, match.index);
      }
    }
  });
}

let total = 0;
console.log('Migration review audit report');
console.log('These findings are warnings for manual classification; this command exits 0.');
console.log('');

for (const check of CHECKS) {
  const bucket = findings.get(check.id);
  total += bucket.length;
  if (bucket.length === 0) {
    continue;
  }

  const capped = bucket.length === MAX_PER_CHECK ? ` (showing first ${MAX_PER_CHECK})` : '';
  console.log(`[${check.id}] ${check.desc}${capped}`);
  for (const finding of bucket) {
    console.log(`  ${finding.relPath}:${finding.lineNumber}: ${finding.line}`);
  }
  console.log('');
}

if (total === 0) {
  console.log('No migration review candidates found.');
} else {
  console.log(`Review audit completed with ${total} printed candidates.`);
}
