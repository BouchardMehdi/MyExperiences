# Deploiement production VPS - MyExperiences

Ce guide deploie MyExperiences sur le domaine :

```text
https://experiences.bouchard-mehdi.fr
```

Architecture cible :

- frontend SvelteKit servi sur `/`
- backend Symfony/API servi sur `/api/`
- Docker Compose lance `frontend`, `backend` et `database`
- Nginx du VPS fait le reverse proxy public
- HTTPS est gere par Certbot sur le VPS

## 1. Prerequis VPS

Le DNS doit pointer vers le VPS :

```text
experiences.bouchard-mehdi.fr A 185.98.138.157
```

Le VPS doit avoir :

```bash
docker --version
docker compose version
nginx -v
certbot --version
```

Si besoin, installer Nginx et Certbot :

```bash
sudo apt update
sudo apt install -y nginx certbot python3-certbot-nginx
```

## 2. Cloner le repo

Le projet doit etre installe dans `/home/projects/MyExperiences`.

```bash
sudo mkdir -p /home/projects
sudo chown -R $USER:$USER /home/projects
cd /home/projects
git clone https://github.com/BouchardMehdi/MyExperiences.git MyExperiences
cd /home/projects/MyExperiences
```

## 3. Creer les variables d'environnement

Creer le fichier `.env` a la racine depuis l'exemple :

```bash
cp .env.example .env
nano .env
```

Exemple de valeurs de production :

```env
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=replace_with_a_long_random_secret

POSTGRES_DB=myexperiences
POSTGRES_USER=myexperiences
POSTGRES_PASSWORD=replace_with_a_strong_database_password

PUBLIC_BASE_PATH=
PUBLIC_API_BASE=/api
```

Generer un secret Symfony robuste :

```bash
openssl rand -hex 32
```

Les vrais fichiers `.env` ne doivent jamais etre versionnes.

Optionnellement, garder les exemples applicatifs disponibles :

```bash
cp back/.env.example back/.env.local
cp front/.env.example front/.env
```

En production Docker, le fichier important est le `.env` a la racine, car `docker compose` l'utilise pour construire et lancer les services.

## 4. Lancer Docker

Depuis `/home/projects/MyExperiences` :

```bash
docker compose up -d --build
```

Services exposes localement sur le VPS :

- frontend : `127.0.0.1:8083`
- backend : `127.0.0.1:3002`
- database : non exposee publiquement

Le backend lance automatiquement les migrations Doctrine au demarrage.

## 5. Verifier les conteneurs et les logs

Verifier l'etat :

```bash
docker compose ps
```

Voir les logs :

```bash
docker compose logs -f frontend
docker compose logs -f backend
docker compose logs -f database
```

Tester en local sur le VPS :

```bash
curl http://127.0.0.1:8083/
curl http://127.0.0.1:3002/api/health
curl http://127.0.0.1:8083/api/health
```

## 6. Configurer Nginx reverse proxy

Creer le fichier :

```bash
sudo nano /etc/nginx/sites-available/experiences.bouchard-mehdi.fr
```

Coller cette configuration :

```nginx
server {
    listen 80;
    server_name experiences.bouchard-mehdi.fr;

    location /api/ {
        proxy_pass http://127.0.0.1:3002/api/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_redirect off;
    }

    location / {
        proxy_pass http://127.0.0.1:8083;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_redirect off;
    }
}
```

Activer le site :

```bash
sudo ln -s /etc/nginx/sites-available/experiences.bouchard-mehdi.fr /etc/nginx/sites-enabled/experiences.bouchard-mehdi.fr
sudo nginx -t
sudo systemctl reload nginx
```

Tester en HTTP :

```bash
curl http://experiences.bouchard-mehdi.fr
curl http://experiences.bouchard-mehdi.fr/api/health
```

## 7. Activer HTTPS avec Certbot

Lancer :

```bash
sudo certbot --nginx -d experiences.bouchard-mehdi.fr
```

Verifier le renouvellement automatique :

```bash
sudo certbot renew --dry-run
```

Tester ensuite :

```bash
curl https://experiences.bouchard-mehdi.fr
curl https://experiences.bouchard-mehdi.fr/api/health
```

## 8. Mettre a jour le projet

Depuis le VPS :

```bash
cd /home/projects/MyExperiences
git pull
docker compose up -d --build
docker compose ps
docker compose logs -f backend
```

Si besoin, relancer uniquement les migrations :

```bash
docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction
```

## 9. Commandes utiles

Redemarrer les services :

```bash
docker compose restart
```

Arreter sans supprimer la base :

```bash
docker compose down
```

Supprimer aussi la base Docker :

```bash
docker compose down -v
```

Attention : `docker compose down -v` supprime le volume PostgreSQL et donc les donnees.

## 10. Comptes de test crees par les migrations

Mot de passe pour tous ces comptes :

```text
password
```

Comptes disponibles :

```text
admin@myexperiences.test
organizer@myexperiences.test
studio@myexperiences.test
user@myexperiences.test
traveler@myexperiences.test
candidate@myexperiences.test
```
