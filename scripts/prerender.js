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

          const html = await page.content();
          
          // Création de la structure de dossier
          const routePath = route === '/' ? '' : route;
          const targetDir = path.join(distPath, routePath);
          const targetFile = path.join(targetDir, 'index.html');

          if (!fs.existsSync(targetDir)) {
            fs.mkdirSync(targetDir, { recursive: true });
          }

          fs.writeFileSync(targetFile, html);
          console.log(`✅ Succès : ${targetFile}`);
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
