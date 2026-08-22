const fs = require('fs')
const path = require('path')

const distDir = path.resolve(__dirname, '..', 'dist')
const indexPath = path.join(distDir, 'index.html')

if (!fs.existsSync(indexPath)) {
  console.error('dist/index.html introuvable. Lancez d abord npm run build.')
  process.exit(1)
}

const html = fs.readFileSync(indexPath, 'utf8')
const injection = `<?php
$actorsApiBase = getenv('ACTORS_API_BASE') ?: '/api';
?>
<script>
window.__SERVER_CONFIG__ = Object.assign({}, window.__SERVER_CONFIG__ || {}, {
  ACTORS_API_BASE: <?php echo json_encode($actorsApiBase, JSON_UNESCAPED_SLASHES); ?>
});
</script>`

const patched = html.replace('<div id="root"></div>', `<div id="root"></div>\n${injection}`)

const files = ['createurs.php', 'acteur.php', 'video.php']
for (const file of files) {
  fs.writeFileSync(path.join(distDir, file), patched, 'utf8')
}

console.log('Pages PHP generees:', files.join(', '))
