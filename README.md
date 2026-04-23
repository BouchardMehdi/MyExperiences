# MyExperiences

Base reusable full-stack pour un projet deploye sous sous-chemin avec :

- `front/` : SvelteKit statique servi par Nginx
- `back/` : Symfony API + Doctrine + PostgreSQL
- `docker-compose.yml` : orchestration production-ready pour VPS

Le projet est prevu pour etre expose sous :

- frontend : `/MyExperiences/`
- backend : `/MyExperiences/api/`

## Structure

```text
MyExperiences/
├── front/
├── back/
├── docker-compose.yml
├── .gitignore
├── README.md
└── deploy.md
```

## Frontend

Le frontend utilise SvelteKit avec un build statique et :

- `paths.base = "/MyExperiences"`
- une fonction API centralisee dans `front/src/lib/api/client.js`
- aucun `localhost` en dur dans le code applicatif
- un conteneur Nginx qui sert le build et peut proxyfier `/MyExperiences/api/` vers le backend

## Backend

Le backend Symfony est organise dans `back/` et expose actuellement :

- `GET /api/health`
- `GET /api/hello`

La base Doctrine/PostgreSQL est prete pour evoluer avec migrations et logique metier. L'ancien socle Symfony a ete conserve comme base de travail, mais le routage actif est maintenant cible sur l'API.

## Lancement local avec Docker

Depuis la racine du projet :

```bash
docker compose up -d --build
```

Services exposes localement :

- frontend : `127.0.0.1:4100`
- backend : `127.0.0.1:4200`
- database : non exposee publiquement

Exemples utiles :

- `http://127.0.0.1:4100/MyExperiences/`
- `http://127.0.0.1:4200/api/health`

## Variables d'environnement

Le backend lit principalement :

- `APP_ENV`
- `APP_SECRET`
- `DATABASE_URL`

Le service PostgreSQL utilise :

- `POSTGRES_DB`
- `POSTGRES_USER`
- `POSTGRES_PASSWORD`

Un exemple backend est fourni dans `back/.env.example`.

## Commandes utiles

Backend :

```bash
docker compose exec backend php bin/console doctrine:migrations:migrate
docker compose exec backend php bin/console doctrine:database:create --if-not-exists
docker compose exec backend php bin/console cache:clear
```

Frontend :

```bash
docker compose exec frontend nginx -t
```

## Deploiement

Le detail du deploiement VPS, la configuration Nginx reverse proxy et les chemins sous `/MyExperiences/` sont documentes dans [deploy.md](deploy.md).
