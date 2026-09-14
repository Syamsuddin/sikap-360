"""Package source plus compiled assets. Exclude secrets, Git identity, and dependencies."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
import shutil

root = Path(__file__).resolve().parent.parent
output = root / 'downloads' / 'SIKAP-360-PHP-MySQL.zip'
output.parent.mkdir(exist_ok=True)
roots = ['app', 'config', 'database', 'docs', 'public', 'resources', 'scripts', 'tests', 'dist']
files = [root / name for name in ['README.md', 'THIRD_PARTY_NOTICES.md', 'Dockerfile', 'compose.yaml', 'package.json', 'package-lock.json', '.env.example', '.gitignore', '.dockerignore']]
for folder in roots:
    files.extend(p for p in (root / folder).rglob('*') if p.is_file() and 'downloads' not in p.relative_to(root).parts and p.suffix not in ['.pyc', '.zip'])
with ZipFile(output, 'w', ZIP_DEFLATED, compresslevel=9) as archive:
    for path in sorted(set(files)):
        archive.write(path, 'SIKAP-360/' + path.relative_to(root).as_posix())
with ZipFile(output) as archive:
    assert archive.testzip() is None
    assert 'SIKAP-360/public/api.php' in archive.namelist()
    assert 'SIKAP-360/database/schema.sql' in archive.namelist()
    assert not any('/.env' in name and not name.endswith('.env.example') for name in archive.namelist())
destination = root / 'dist' / 'downloads'
destination.mkdir(exist_ok=True)
shutil.copy2(output, destination / output.name)
print(f'{output.name}: {len(files)} files, {output.stat().st_size} bytes')
