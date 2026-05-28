const fs = require('fs');
const path = require('path');

const packageJsonPath = path.join(__dirname, '..', 'package.json');
const pkg = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));

const allDeps = {
  ...(pkg.dependencies || {}),
  ...(pkg.devDependencies || {}),
};

const forbidden = [
  'vue-template-compiler',
  'laravel-mix',
  'bootstrap',
  'jquery',
  'popper.js',
  'vue2-circle-progress',
];

const forbiddenVersionRules = [
  ['vue', /^(\^|~)?2\./],
  ['vue-router', /^(\^|~)?3\./],
  ['vuex', /^(\^|~)?3\./],
  ['vuetify', /^(\^|~)?2\./],
  ['vue-loader', /^(\^|~)?15\./],
];

let failed = false;

for (const name of forbidden) {
  if (allDeps[name]) {
    console.error(`❌ Forbidden dependency found: ${name}@${allDeps[name]}`);
    failed = true;
  }
}

for (const [name, rule] of forbiddenVersionRules) {
  if (allDeps[name] && rule.test(allDeps[name])) {
    console.error(`❌ Forbidden legacy version found: ${name}@${allDeps[name]}`);
    failed = true;
  }
}

if (failed) {
  process.exit(1);
}

console.log('✅ Dependency legacy check passed.');
