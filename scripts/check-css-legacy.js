const fs = require('fs');
const path = require('path');

const ROOT_DIR = path.join(__dirname, '..');
const SCAN_DIRS = ['resources']; // CSS/Sass styling is here
const EXCLUDE_DIRS = ['node_modules', 'vendor', 'storage', 'public/build', '.git'];
const EXCLUDE_FILES = ['PatchNotes.vue'];
const FILE_EXTENSIONS = ['.css', '.scss', '.sass', '.vue', '.blade.php'];

const PATTERNS = [
  { regex: /@import\s+/g, desc: "Sass @import usage; consider @use/@forward or plain CSS import strategy", type: 'warning' },
  { regex: /bootstrap/gi, desc: "Bootstrap CSS/Sass usage", type: 'error' },
  { regex: /jquery/gi, desc: "jQuery-coupled styling or scripts", type: 'error' },
  { regex: /\/deep\//g, desc: "Old deep selector (/deep/)", type: 'error' },
  { regex: />>>/g, desc: "Old deep selector (>>>)", type: 'error' },
  { regex: /!important/g, desc: "Potential CSS override debt (!important); review manually", type: 'warning' },
];

let errorCount = 0;
let warningCount = 0;

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

console.log("Checking for legacy CSS/Sass patterns...");

for (const scanDir of SCAN_DIRS) {
  const fullScanPath = path.join(ROOT_DIR, scanDir);
  if (!fs.existsSync(fullScanPath)) continue;

  walkDir(fullScanPath, (filePath, relPath) => {
    // Avoid scanning resources/js/bootstrap.js or check scripts themselves for bootstrap text
    if (relPath === 'resources/js/bootstrap.js' || relPath.startsWith('scripts/')) {
      return;
    }

    const content = fs.readFileSync(filePath, 'utf8');
    const lines = content.split('\n');

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i];
      for (const pattern of PATTERNS) {
        pattern.regex.lastIndex = 0; // Reset regex
        if (pattern.regex.test(line)) {
          // Double check: if checking bootstrap in resources/js/app.js, make sure it's not the local bootstrap.js import
          if (pattern.regex.source.includes('bootstrap') && relPath === 'resources/js/app.js' && line.includes("import './bootstrap'")) {
            continue;
          }

          if (pattern.type === 'error') {
            console.log(`❌ ERROR: Found legacy CSS/Sass pattern: ${pattern.desc}`);
            errorCount++;
          } else {
            console.log(`⚠️ WARNING: Found legacy CSS/Sass pattern: ${pattern.desc}`);
            warningCount++;
          }
          console.log(`  File: ${relPath}:${i + 1}`);
          console.log(`  Line: ${line.trim()}`);
          console.log("");
        }
      }
    }
  });
}

console.log(`Audit Summary: ${errorCount} Errors, ${warningCount} Warnings.`);

if (errorCount > 0) {
  console.log("CSS legacy check failed due to critical errors.");
  process.exit(1);
}

console.log("✅ Critical CSS legacy check passed.");
