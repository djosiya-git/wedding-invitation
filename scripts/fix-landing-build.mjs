import { existsSync, mkdirSync, renameSync, rmSync } from 'node:fs';
import { dirname, join } from 'node:path';

const builtHtml = join('landing-dist', 'landing-build-index.html');
const finalHtml = join('landing-dist', 'index.html');

if (!existsSync(builtHtml)) {
  throw new Error(`Missing built landing HTML at ${builtHtml}`);
}

mkdirSync(dirname(finalHtml), { recursive: true });
renameSync(builtHtml, finalHtml);
rmSync(join('landing-dist', 'landing-react'), { recursive: true, force: true });
