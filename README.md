# TikCapture Pro

Application web moderne et performante d'extraction, de visualisation et de gestion de flux vidéo et diffusions en direct multi-plateformes (TikTok, YouTube, Twitch, Kick, Facebook, Bigo Live, DLive).

---

## 1. Architecture Globale du Projet

Le projet repose sur une architecture découplée respectant les principes MVC (Modèle-Vue-Contrôleur) et SOLID :

* **Frontend (React + TypeScript + Vite)** : Interface utilisateur réactive, conçue selon une approche mobile-first, optimisée pour les écrans larges d'ordinateurs de bureau, utilisant Tailwind CSS pour le style et les icônes de la bibliothèque Lucide React.
* **Backend (PHP CLI & API Web)** : Moteur de scraping, contrôleurs d'API REST, gestion des téléchargements de médias en local et sécurisation des points d'accès par clé d'API.
* **Persistance & Médias Locaux** : Les flux, vidéos et miniatures sont systématiquement téléchargés et hébergés localement sur le serveur. Aucune URL externe n'est conservée directement dans la base de données.

---

## 2. Structure des Répertoires

```
├── demarrer_local.bat      # Script batch de démarrage automatisé (Frontend + Backend)
├── package.json            # Dépendances et scripts de construction Node.js
├── vite.config.ts          # Configuration du serveur de développement et de build Vite
├── SCRAPPER/               # Modules et scripts PHP de scraping par plateforme
│   ├── TikTok/             # Extraction des flux et données TikTok
│   ├── Twitch/             # Gestion des flux Twitch
│   ├── Youtube/            # Extraction des vidéos et directs YouTube
│   ├── Kick/               # Scraper Kick
│   └── Bigo/               # Scraper Bigo Live
├── public/                 # Racine web du serveur PHP & points d'accès API
│   ├── api/                # Contrôleurs API REST
│   ├── config/             # Configuration des clés et de la base de données
│   ├── donnees/            # Fichiers de données et métadonnées locales
│   ├── uploads/            # Stockage des médias et miniatures téléchargés
│   ├── tiktok_live.php     # Endpoint pour l'analyse en direct
│   └── api_proxy.php       # Passerelle sécurisée d'accès aux scrapers
└── src/                    # Code source du Frontend React
    ├── components/         # Composants d'interface modulaires
    ├── controleurs/        # Logique de contrôle côté client
    ├── hooks/              # Hooks React personnalisés
    ├── lib/                # Fonctions utilitaires et constantes
    ├── modeles/            # Types et structures de données TypeScript
    └── vues/               # Pages et vues principales de l'application
```

---

## 3. Prérequis Système

Pour exécuter et développer sur ce projet en local, les outils suivants sont requis :

1. **Node.js** : Version 18.x ou supérieure (avec npm).
2. **PHP** : Version 8.1 ou supérieure avec les extensions activées :
   * `curl` (indispensable pour les requêtes de scraping)
   * `json` (traitement des flux de données)
   * `fileinfo` (détection des types MIME des médias)
   * `openssl` (sécurité des transferts HTTPS)
3. **Navigateur Web moderne** : Chrome, Firefox, Edge ou Safari.

---

## 4. Démarrage Rapide

### Option A : Démarrage Automatisé (Windows)

Exécutez le script batch situé à la racine du projet :

```cmd
demarrer_local.bat
```

Ce script effectue automatiquement :
* La vérification de Node.js, npm et PHP CLI.
* L'installation des dépendances (`npm install`) si nécessaire.
* Le lancement du serveur Backend PHP sur `http://127.0.0.1:8000`.
* Le lancement du serveur Frontend Vite sur `http://localhost:5173`.
* L'ouverture automatique de l'application dans votre navigateur.

---

### Option B : Démarrage Manuel

#### 1. Démarrer le Backend PHP
Dans un premier terminal :
```bash
php -S 127.0.0.1:8000 -t public
```

#### 2. Démarrer le Frontend React
Dans un second terminal :
```bash
npm install
npm run dev
```

L'interface sera accessible sur `http://localhost:5173` ou `http://localhost:3000`.

---

## 5. Règles Architecturales et Bonnes Pratiques

* **Gestion des Médias & RGPD / Confidentialité** : Tous les contenus multimédias (miniatures, avatars, flux) sont téléchargés sur le serveur dans le répertoire `public/uploads/` avant d'être référencés.
* **Sécurité des API** : Les communications entre le frontend et le backend sont protégées par des en-têtes d'autorisation `X-API-Key`.
* **Conception Visuelle** : 
  * Palette claire et épurée avec boutons d'action rouges et textes noirs à haut contraste.
  * Approche Mobile-First avec pleine exploitation de la largeur d'écran sur ordinateur.
  * Absence totale d'icônes textuelles ou emojis au profit d'icônes vectorielles SVG (Lucide React).
* **Modularité du Code** : Décomposition stricte des fichiers dépassant 250 lignes en sous-modules réutilisables.

---

## 6. Scripts Disponibles

* `npm run dev` : Lance le serveur de développement Vite.
* `npm run build` : Compile l'application React pour la production dans le dossier `dist/`.
* `npm run lint` : Analyse la qualité du code TypeScript et React.
* `npm run preview` : Prévisualise la version de production compilée.

---

## 7. Licence

Ce projet est la propriété de ses contributeurs. Tous droits réservés.
