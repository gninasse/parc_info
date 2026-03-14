# Guide d'installation - Parc Info

## Prérequis
- Ubuntu avec Apache2
- PHP 8.x
- PostgreSQL
- Composer
- Node.js & NPM
- Git

## 1. Cloner le projet

```bash
cd /home/ibrahim/projets/web
git clone https://github.com/gninasse/parc_info.git
cd parc_info
```

## 2. Configuration de la base de données PostgreSQL

```bash
# Créer l'utilisateur
sudo -u postgres psql -c "CREATE USER parc_info WITH PASSWORD 'parc_info';"

# Créer la base de données
sudo -u postgres psql -c "CREATE DATABASE parc_info OWNER parc_info;"

# Donner les privilèges
sudo -u postgres psql -d parc_info -c "GRANT ALL ON SCHEMA public TO parc_info;"
sudo -u postgres psql -d parc_info -c "GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO parc_info;"
sudo -u postgres psql -d parc_info -c "GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO parc_info;"
sudo -u postgres psql -d parc_info -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO parc_info;"
```

## 3. Configuration du fichier .env

Créer le fichier `.env` à la racine du projet :

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:d/fV2DmhpgWCjHGZmprmwlBBLb3etqVhte7JL3pTCAs=
APP_DEBUG=true
APP_URL=https://parc_info.local

APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_FAKER_LOCALE=fr_FR

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=parc_info
DB_USERNAME=parc_info
DB_PASSWORD=parc_info

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

ACTIVITY_LOGGER_ENABLED=true
ACTIVITY_LOGGER_DB_CONNECTION=pgsql_activitylog
ACTIVITY_LOGGER_DB_DATABASE=parc_info
ACTIVITY_LOGGER_DB_USERNAME=parc_info
ACTIVITY_LOGGER_DB_PASSWORD=parc_info
```

## 4. Configuration Apache Virtual Host

Créer le fichier `/etc/apache2/sites-available/parc_info.conf` :

```apache
<VirtualHost *:80>
    ServerName parc_info.local
    DocumentRoot /home/ibrahim/projets/web/parc_info/public

    <Directory /home/ibrahim/projets/web/parc_info/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/parc_info_error.log
    CustomLog ${APACHE_LOG_DIR}/parc_info_access.log combined
</VirtualHost>
```

Activer le site :

```bash
sudo a2ensite parc_info.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

## 5. Configuration du fichier hosts

```bash
echo "127.0.0.1 parc_info.local" | sudo tee -a /etc/hosts
```

## 6. Installation des dépendances

```bash
# Corriger les permissions
sudo chown -R $USER:www-data .
chmod -R 775 storage bootstrap/cache

# Ajouter l'utilisateur au groupe www-data
sudo usermod -a -G www-data $USER

# Installer les dépendances PHP
composer install

# Installer les dépendances JavaScript
npm install
```

## 7. Lancer les migrations

```bash
php artisan migrate
```

## 8. Compiler les assets

```bash
npm run build
```

## 9. Accéder à l'application

Ouvrir le navigateur : **http://parc_info.local**

## Notes importantes

- **Base de données** : parc_info / parc_info / parc_info
- **URL locale** : http://parc_info.local
- **Dossier projet** : /home/ibrahim/projets/web/parc_info
- **Permissions** : storage et bootstrap/cache doivent être en 775

## Dépannage

### Erreur de permissions
```bash
cd /home/ibrahim/projets/web/parc_info
sudo chown -R $USER:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
sudo systemctl restart apache2
```

### Erreur de connexion base de données
Vérifier que PostgreSQL est démarré et que l'utilisateur a les bons privilèges.

### Page blanche
Vérifier les logs Apache : `tail -f /var/log/apache2/parc_info_error.log`
