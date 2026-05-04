#!/bin/bash
# bootstrap/download.sh
# Télécharge Bootstrap 5.3.3 et Bootstrap Icons 1.11.3 en local.
# Exécuter UNE SEULE FOIS depuis la racine du projet :
#
#   cd /opt/lampp/htdocs/p2p/bootstrap
#   bash download.sh
#
# Prérequis : curl installé (présent par défaut sur Ubuntu/Debian/macOS)

set -e
DIR="$(cd "$(dirname "$0")" && pwd)"

echo "📦 Téléchargement de Bootstrap 5.3.3..."
curl -L "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" \
     -o "$DIR/bootstrap.min.css"

curl -L "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" \
     -o "$DIR/bootstrap.bundle.min.js"

echo "🎨 Téléchargement de Bootstrap Icons 1.11.3..."
curl -L "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" \
     -o "$DIR/bootstrap-icons.min.css"

mkdir -p "$DIR/fonts"
curl -L "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2" \
     -o "$DIR/fonts/bootstrap-icons.woff2"
curl -L "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff" \
     -o "$DIR/fonts/bootstrap-icons.woff"

# Corriger les chemins de polices (CDN → relatif local)
sed -i 's|../fonts/bootstrap-icons|./fonts/bootstrap-icons|g' "$DIR/bootstrap-icons.min.css"

echo ""
echo "✅ Bootstrap téléchargé avec succès !"
echo "   bootstrap.min.css        : $(du -sh "$DIR/bootstrap.min.css" | cut -f1)"
echo "   bootstrap.bundle.min.js  : $(du -sh "$DIR/bootstrap.bundle.min.js" | cut -f1)"
echo "   bootstrap-icons.min.css  : $(du -sh "$DIR/bootstrap-icons.min.css" | cut -f1)"
echo "   fonts/                   : $(du -sh "$DIR/fonts/" | cut -f1)"
echo ""
echo "👉 Ouvre maintenant http://localhost/p2p/ pour vérifier."
