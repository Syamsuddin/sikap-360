import {mkdir,copyFile,readFile,writeFile} from 'node:fs/promises';
await mkdir('dist/assets',{recursive:true});
await copyFile('node_modules/lucide/dist/umd/lucide.min.js','public/assets/lucide.min.js');
for(const name of ['app.css','app.js','theme.js','lucide.min.js','logo-hss.png','logo-hss-icon.png']) await copyFile(`public/assets/${name}`,`dist/assets/${name}`);
await copyFile('resources/demo.js','dist/assets/demo.js');
await writeFile('dist/index.html',(await readFile('public/index.html','utf8')).replace('data-mode="live"','data-mode="demo"'));
console.log('Pratinjau demo dibuat. Backend PHP tetap menggunakan public/.');
