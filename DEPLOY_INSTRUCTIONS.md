# 🚀 ISTRUZIONI DEPLOY SUGECO IN PRODUZIONE

## 📋 Prerequisiti

- Server con PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js & npm (per assets)
- Apache/Nginx

## 🔧 Setup Iniziale

### 1. Clonare Repository

```bash
git clone https://github.com/YOUR_REPO/SUGECO.git
cd SUGECO
```

### 2. Installare Dipendenze

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### 3. Configurare Environment

```bash
cp .env.production .env
php artisan key:generate
```

**IMPORTANTE**: Modificare `.env` con:
- `APP_DEBUG=false`
- Credenziali database corrette
- `APP_URL` con dominio produzione
- Password sicura per `DB_PASSWORD`

### 4. Preparare Database

```bash
# Importare il dump SQL
mysql -u root -p sugeco_db < backup/sugeco_db_YYYYMMDD_HHMM.sql

# OPPURE eseguire migrations da zero
php artisan migrate --force
php artisan db:seed --force
```

### 5. Ottimizzazione Produzione

```bash
# Cache configurazione
php artisan config:cache

# Cache rotte
php artisan route:cache

# Cache view
php artisan view:cache

# Ottimizza autoload Composer
composer dump-autoload --optimize
```

### 6. Permessi File

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 7. Configurare Web Server

#### Apache (esempio)

```apache
<VirtualHost *:80>
    ServerName sugeco.yourdomain.com
    DocumentRoot /var/www/SUGECO/public

    <Directory /var/www/SUGECO/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/sugeco-error.log
    CustomLog ${APACHE_LOG_DIR}/sugeco-access.log combined
</VirtualHost>
```

#### Nginx (esempio)

```nginx
server {
    listen 80;
    server_name sugeco.yourdomain.com;
    root /var/www/SUGECO/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 8. SSL/HTTPS (Certbot)

```bash
sudo certbot --apache -d sugeco.yourdomain.com
# OPPURE
sudo certbot --nginx -d sugeco.yourdomain.com
```

## 🔒 Checklist Sicurezza Pre-Deploy

- [ ] `APP_DEBUG=false` in `.env`
- [ ] Password database sicura
- [ ] `APP_KEY` generato
- [ ] HTTPS attivo
- [ ] Firewall configurato
- [ ] Backup database schedulato
- [ ] Log monitoring attivo
- [ ] Permessi file corretti (755/644)
- [ ] `.env` NON commitato su Git

## 📊 Monitoraggio Post-Deploy

### Verifica Logs

```bash
tail -f storage/logs/laravel.log
```

### Verifica Performance

```bash
# Query lente
php artisan db:monitor

# Cache status
php artisan cache:table
```

### Backup Automatico Database

Aggiungere a crontab:

```cron
0 2 * * * cd /var/www/SUGECO && /usr/bin/mysqldump -u DB_USER -pDB_PASS sugeco_db > /backup/sugeco_$(date +\%Y\%m\%d).sql
```

## 🔄 Update Applicazione

```bash
# Pull ultimo codice
git pull origin main

# Update dipendenze
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Esegui migrations
php artisan migrate --force

# Pulisci cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl restart apache2
# OPPURE
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

## 🆘 Troubleshooting

### Errore 500

1. Controllare `storage/logs/laravel.log`
2. Verificare permessi `storage/` e `bootstrap/cache/`
3. Verificare `.env` configurato correttamente

### CSS/JS non caricati

1. Verificare `APP_URL` in `.env`
2. Eseguire `npm run build`
3. Controllare permessi `public/build/`

### Errori Database

1. Verificare credenziali in `.env`
2. Verificare connessione: `php artisan migrate:status`
3. Controllare log MySQL

## 📞 Supporto

Per problemi: contattare Michele Di Gennaro

---

**Versione**: 2.0.0  
**Ultimo aggiornamento**: Novembre 2025

