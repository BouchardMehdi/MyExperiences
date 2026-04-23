# Deploiement VPS - MyExperiences

## Emplacement recommande

Cloner le projet dans :

```bash
/home/projects/MyExperiences
```

Exemple :

```bash
mkdir -p /home/projects
cd /home/projects
git clone <URL_DU_REPO> MyExperiences
cd MyExperiences
```

## Configuration des variables

### Backend Symfony

Copier et adapter :

```bash
cp back/.env.example back/.env.local
```

Exemple de valeurs :

```env
APP_ENV=prod
APP_SECRET=change_me
DATABASE_URL="postgresql://myexperiences:myexperiences@database:5432/myexperiences?serverVersion=16&charset=utf8"
```

### Variables Docker Compose

Les variables suivantes peuvent etre surchargees via l'environnement du shell ou un fichier `.env` a la racine si besoin :

```env
POSTGRES_DB=myexperiences
POSTGRES_USER=myexperiences
POSTGRES_PASSWORD=myexperiences
APP_SECRET=change_me
```

## Lancement

Commande unique :

```bash
docker compose up -d --build
```

## Ports internes

- frontend : `127.0.0.1:4100`
- backend : `127.0.0.1:4200`

La base PostgreSQL reste interne au reseau Docker.

## Commandes utiles

Voir les logs :

```bash
docker compose logs -f frontend
docker compose logs -f backend
docker compose logs -f database
```

Executer une migration :

```bash
docker compose exec backend php bin/console doctrine:migrations:migrate
```

Creer la base si necessaire :

```bash
docker compose exec backend php bin/console doctrine:database:create --if-not-exists
```

Tester les endpoints :

```bash
curl http://127.0.0.1:4200/api/health
curl http://127.0.0.1:4200/api/hello
```

## Configuration Nginx du VPS

Exemple complet de reverse proxy pour exposer l'application sous `/MyExperiences/` et l'API sous `/MyExperiences/api/` :

```nginx
server {
    listen 80;
    server_name example.com;

    location = /MyExperiences {
        return 301 /MyExperiences/;
    }

    location /MyExperiences/api/ {
        proxy_pass http://127.0.0.1:4200/api/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /MyExperiences/ {
        proxy_pass http://127.0.0.1:4100/MyExperiences/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

## Notes de production

- garder `docker compose up -d --build` comme commande de mise a jour simple
- ne pas exposer PostgreSQL publiquement
- laisser le reverse proxy principal du VPS gerer TLS
- conserver les ports applicatifs limites a `127.0.0.1`
