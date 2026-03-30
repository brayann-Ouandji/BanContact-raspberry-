#!/bin/bash

# Vérification des droits root
if [ "$EUID" -ne 0 ]; then
  echo "Veuillez lancer ce script en tant que root (sudo ./install.sh)"
  exit 1
fi

echo "=== 1. Mise à jour du système et installation des paquets ==="
apt-get update
apt-get install -y apache2 php libapache2-mod-php mariadb-server php-mysql git

echo "=== 2. Configuration de la base de données ==="
# Création de la base, de l'utilisateur (issu de ton config.php) et attribution des droits
mysql -u root -e "CREATE DATABASE IF NOT EXISTS rfid_payment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE USER IF NOT EXISTS 'rfid_user'@'localhost' IDENTIFIED BY 'rfid1234';"
mysql -u root -e "GRANT ALL PRIVILEGES ON rfid_payment.* TO 'rfid_user'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

# Importation de tes tables et données de démo
mysql -u root rfid_payment < database.sql

echo "=== 3. Déploiement des fichiers Web ==="
# On nettoie le dossier par défaut d'Apache et on copie tout le projet
rm -rf /var/www/html/*
# Copie de tous les fichiers PHP/CSS (sauf install.sh, database.sql, script/)
rsync -av --exclude='install.sh' --exclude='database.sql' --exclude='script/' ./ /var/www/html/

# On donne les bons droits à Apache
chown -R www-data:www-data /var/www/html/
chmod -R 755 /var/www/html/

echo "=== 4. Installation du script RFID ==="
# Création du dossier attendu par ton config.php
mkdir -p /home/pi/script
cp script/tag_detect.sh /home/pi/script/
chmod +x /home/pi/script/tag_detect.sh

echo "=== 5. Configuration de Sudoers pour PHP ==="
# Permet à PHP (www-data) d'exécuter tag_detect.sh en root sans demander de mot de passe
echo "www-data ALL=(ALL) NOPASSWD: /home/pi/script/tag_detect.sh" > /etc/sudoers.d/rfid_nopasswd
chmod 0440 /etc/sudoers.d/rfid_nopasswd

echo "=== Installation terminée avec succès ! ==="
echo "Accédez à l'application via : http://10.3.183.21 