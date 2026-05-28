const fs = require('fs');
const path = require('path');

const ROOT_DIR = path.join(__dirname, '..');
const SCAN_DIRS = ['resources', 'app']; // Directories to scan
const EXCLUDE_DIRS = ['node_modules', 'vendor', 'storage', 'public/build', '.git'];
const EXCLUDE_FILES = ['PatchNotes.vue'];
const FILE_EXTENSIONS = ['.vue', '.js', '.ts', '.blade.php'];

const PATTERNS = [
  { regex: /new\s+Vue\s*\(/g, desc: "Vue 2 app initialization (new Vue)" },
  { regex: /Vue\.use\s*\(/g, desc: "Vue 2 plugin installation (Vue.use)" },
  { regex: /Vue\.extend\s*\(/g, desc: "Vue.extend API" },
  { regex: /Vue\.component\s*\(/g, desc: "Global Vue.component usage" },
  { regex: /Vue\.directive\s*\(/g, desc: "Global Vue.directive usage" },
  { regex: /Vue\.filter\s*\(/g, desc: "Vue filters (Vue.filter)" },
  { regex: /Vue\.set\s*\(/g, desc: "Vue.set" },
  { regex: /Vue\.delete\s*\(/g, desc: "Vue.delete" },
  { regex: /this\.\$set\s*\(/g, desc: "this.$set" },
  { regex: /this\.\$delete\s*\(/g, desc: "this.$delete" },
  { regex: /this\.\$listeners\b/g, desc: "this.$listeners" },
  { regex: /this\.\$children\b/g, desc: "this.$children" },
  { regex: /this\.\$scopedSlots\b/g, desc: "this.$scopedSlots" },
  { regex: /\$destroy\s*\(/g, desc: "Vue 2 $destroy" },
  { regex: /\bbeforeDestroy\b/g, desc: "beforeDestroy lifecycle hook" },
  { regex: /\bdestroyed\b/g, desc: "destroyed lifecycle hook" },
  { regex: /\.native\b/g, desc: "v-on.native modifier" },
  { regex: /\.sync\b/g, desc: ".sync modifier" },
  { regex: /slot-scope=/g, desc: "Old slot-scope syntax" },
  { regex: /\s+slot=["']/g, desc: "Old named slot syntax (slot=\"...\")" },
  { regex: /<template\s+functional/g, desc: "Vue 2 functional SFC" },
  { regex: /\bfunctional\s*:\s*true\b/g, desc: "Vue 2 functional component option" },
  { regex: /^\s{2,8}filters\s*:/g, desc: "Vue 2 filters option" },
  { regex: /\{\{[^}]*\|[^}]*\}\}/g, desc: "Vue 2 template filter pipe ({{ val | filter }})" },
  { regex: /\/deep\//g, desc: "Old deep selector (/deep/)" },
  { regex: />>>/g, desc: "Old deep selector (>>>)" },
  { regex: /::v-deep\b/g, desc: "Deprecated ::v-deep selector (use :deep() instead)" },
  { regex: /vuetify\/lib/g, desc: "Vuetify 2 import path (vuetify/lib)" },
  { regex: /vuetify\/es5/g, desc: "Vuetify 2 import path (vuetify/es5)" },
  { regex: /<v-list-item-content/g, desc: "Vuetify 2 component <v-list-item-content>" },
  { regex: /<v-list-item-group/g, desc: "Vuetify 2 component <v-list-item-group>" },
  { regex: /this\.\$(on|off|once)\b/g, desc: "Vue 2 event emitter API (this.$on/$off/$once)" },
  { regex: /new\s+VueRouter\b/g, desc: "Vue Router v3 instantiation (new VueRouter)" },
  { regex: /new\s+Vuex\.Store\b/g, desc: "Vuex v3 instantiation (new Vuex.Store)" },
  { regex: /Vue\.config\.(keyCodes|productionTip)\b/g, desc: "Removed Vue 2 config property" },
  { regex: /<v-content\b/g, desc: "Deprecated Vuetify 2 <v-content> (use <v-main>)" },
  { regex: /<v-simple-table\b/g, desc: "Deprecated Vuetify 2 <v-simple-table> (use <v-table>)" },
];

let matchCount = 0;

function walkDir(dir, callback) {
  const files = fs.readdirSync(dir);
  for (const file of files) {
    const fullPath = path.join(dir, file);
    const relPath = path.relative(ROOT_DIR, fullPath).replace(/\\/g, '/');

    // Skip excluded directories
    if (EXCLUDE_DIRS.some(ex => relPath === ex || relPath.startsWith(ex + '/'))) {
      continue;
    }

    // Skip excluded files
    const baseName = path.basename(fullPath);
    if (EXCLUDE_FILES.includes(baseName)) {
      continue;
    }

    const stat = fs.statSync(fullPath);
    if (stat.isDirectory()) {
      walkDir(fullPath, callback);
    } else {
      const ext = path.extname(fullPath).toLowerCase();
      if (FILE_EXTENSIONS.includes(ext)) {
        callback(fullPath, relPath);
      }
    }
  }
}

console.log("Checking for legacy Vue 2 / Vuetify 2 / Laravel Mix source patterns...");

for (const scanDir of SCAN_DIRS) {
  const fullScanPath = path.join(ROOT_DIR, scanDir);
  if (!fs.existsSync(fullScanPath)) continue;

  walkDir(fullScanPath, (filePath, relPath) => {
    const content = fs.readFileSync(filePath, 'utf8');
    const lines = content.split('\n');

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i];
      for (const pattern of PATTERNS) {
        pattern.regex.lastIndex = 0; // Reset regex
        if (pattern.regex.test(line)) {
          console.log(`❌ Found legacy pattern: ${pattern.desc}`);
          console.log(`  File: ${relPath}:${i + 1}`);
          console.log(`  Line: ${line.trim()}`);
          console.log("");
          matchCount++;
        }
      }
    }
  });
}

if (matchCount > 0) {
  console.log(`Legacy check failed. Found ${matchCount} matches.`);
  process.exit(1);
}

console.log("✅ No known legacy patterns found.");
