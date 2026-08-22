Structure obligatoire des medias:

- Base de donnees (chemin court):
  - `images/YYYY/MM/...`
  - `videos/YYYY/MM/...`

- URL publique reconstituee cote PHP:
  - `/files/images/YYYY/MM/...`
  - `/files/videos/YYYY/MM/...`

Rappels:

1. Ne jamais stocker d URL `http://` ou `https://` en base.
2. Pour creer des chemins mensuels en PHP:
   - `monthly_asset_folder('images')`
   - `monthly_asset_folder('videos')`
   - `build_monthly_asset_path('images', $filename)`
   - `build_monthly_asset_path('videos', $filename)`
