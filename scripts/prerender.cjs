const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');
const http = require('http');
const handler = require('serve-handler');

const routes = [
  '/',
  '/comment-enregistrer',
  '/comment-telecharger',
  '/tiktok-video',
  '/blog',
  '/faq',
  '/tutoriels-video',
  '/contact',
  '/suggestion',
  '/createurs',
  '/connexion',
  '/inscription',
  '/verifier-compte',
  '/mot-de-passe-oublie',
  '/reset-password',
  '/dashboard',
  '/cgu',
  '/cgv',
  '/mentions-legales',
];

async function prerender() {
  const distPath = path.join(__dirname, '../dist');
  
  // 1. Démarrer un serveur temporaire pour servir le build
  const server = http.createServer((request, response) => {
    return handler(request, response, {
      public: distPath,
      rewrites: [{ source: '/**', destination: '/index.html' }]
    });
  });

  const port = 4000;
  server.listen(port, async () => {
    console.log(`Serveur de pré-rendu démarré sur le port ${port}`);

    let browser;
    try {
      browser = await puppeteer.launch({
        headless: "new",
        args: ['--no-sandbox', '--disable-setuid-sandbox']
      });

      const page = await browser.newPage();

      for (const route of routes) {
        console.log(`Pré-rendu de la route : ${route}`);
        try {
          await page.goto(`http://localhost:${port}${route}`, {
            waitUntil: 'networkidle0',
            timeout: 30000
          });

          // Attente supplémentaire de 3s pour être sûr que le JS a fini de peupler les données
          await new Promise(resolve => setTimeout(resolve, 3000));

          let html = await page.content();
          
          // --- FIX : Forcer le mode Standard (éviter le Quirks Mode) ---
          // On s'assure que le DOCTYPE est bien là et en majuscules au début
          if (!html.toLowerCase().startsWith('<!doctype html')) {
            html = '<!DOCTYPE html>\n' + html;
          }

          // --- FIX : Forcer les chemins absolus pour éviter la page blanche au rafraîchissement ---
          if (!html.includes('<base href="/')) {
            html = html.replace('<head>', '<head>\n    <base href="/">');
          }
          
          // Création de la structure de dossier
          const routePath = route === '/' ? '' : route;
          const targetDir = path.join(distPath, routePath);
          const targetFile = path.join(targetDir, 'index.html');

          if (!fs.existsSync(targetDir)) {
            fs.mkdirSync(targetDir, { recursive: true });
          }

          fs.writeFileSync(targetFile, html);
          console.log(`✅ Succès : ${targetFile}`);

          // --- LOGIQUE SEO DYNAMIQUE POUR LA HOME PAGE UNIQUEMENT ---
          if (route === '/') {
            console.log('Spécial : Conversion de la Home Page en PHP dynamique pour le SEO...');
            const phpCode = `<?php 
require_once __DIR__ . '/segment_page/api/recordings_ssr.php';
$baseUrl = 'https://tikcapture.live';
$dynamicGrid = getRecentRecordingsHTML($baseUrl);
?>`;
            
            // On injecte le code PHP SANS saut de ligne avant le doctype pour éviter le mode Quirks
            let finalHtml = phpCode + html;

            // Optimisation Performance : Injection des Preconnect
            const preconnectTags = `
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://mc.yandex.ru" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
`;
            finalHtml = finalHtml.replace('</title>', '</title>' + preconnectTags);
            
            // On remplace le contenu de la zone par l'appel PHP
            const regexArea = /<div id="recent-recordings-ssr-area"[^>]*>([\s\S]*?)<\/div>/;
            const matchArea = finalHtml.match(regexArea);
            
            if (matchArea) {
               console.log('✅ Zone ID trouvée ! Injection du PHP dynamique...');
               const content = matchArea[1];
               const h2Regex = /<div class="flex items-center justify-between px-2">[\s\S]*?<\/div>/;
               const h2Match = content.match(h2Regex);
               const h2Content = h2Match ? h2Match[0] : '';
               
               const replacement = `<div id="recent-recordings-ssr-area" class="mt-12 space-y-6 animate-fade-in px-4 sm:px-6 lg:px-8">\n${h2Content}\n<?php echo $dynamicGrid; ?>\n</div>`;
               finalHtml = finalHtml.replace(matchArea[0], replacement);
            }

            const phpFilePath = path.join(distPath, 'index.php');
            fs.writeFileSync(phpFilePath, finalHtml);
            console.log(`🚀 Home Page convertie : ${phpFilePath}`);
          }
        } catch (err) {
          console.error(`❌ Erreur sur ${route}:`, err.message);
        }
      }
    } catch (err) {
      console.error('Erreur critique lors du pré-rendu:', err);
      console.log('\nCONSEIL : Si vous avez une erreur de Chromium manquant, lancez :');
      console.log('npx puppeteer browsers install chrome');
    } finally {
      if (browser) await browser.close();
      server.close();
      console.log('Pré-rendu terminé.');
      process.exit(0);
    }
  });
}

prerender();
