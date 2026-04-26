# MyExperiences

MyExperiences est une base full-stack pour un petit produit de reservation d'experiences :

- `front/` : SvelteKit/Vite, build statique servi par Nginx
- `back/` : Symfony API + Doctrine + PostgreSQL
- `docker-compose.yml` : orchestration Docker pour production VPS

En production, l'application est prevue pour :

- frontend : `/`
- API backend : `/api/`
- domaine : `https://experiences.bouchard-mehdi.fr`

## Structure

```text
MyExperiences/
├── front/
├── back/
├── docker-compose.yml
├── .env.example
├── README.md
└── deploy.md
```

## Lancement avec Docker

Depuis la racine :

```bash
cp .env.example .env
docker compose up -d --build
```

Services exposes localement :

- frontend : `http://127.0.0.1:8083`
- backend : `http://127.0.0.1:3002/api/health`
- database : interne au reseau Docker

Le frontend appelle l'API via `/api`, sans `localhost` hardcode en production.

## Variables principales

Le fichier `.env` racine est utilise par Docker Compose :

```env
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=change_me_replace_with_a_long_random_secret

POSTGRES_DB=myexperiences
POSTGRES_USER=myexperiences
POSTGRES_PASSWORD=change_me_replace_with_a_strong_database_password

PUBLIC_BASE_PATH=
PUBLIC_API_BASE=/api
```

Les vrais fichiers `.env` ne doivent pas etre versionnes. Des exemples sont fournis :

- `.env.example`
- `back/.env.example`
- `front/.env.example`

## Endpoints API principaux

- `GET /api/health`
- `GET /api/hello`
- `GET /api/experiences`
- `GET /api/experiences/{id}`
- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/me`
- `GET /api/bookings`
- `POST /api/bookings`
- `POST /api/bookings/{id}/pay`
- `GET /api/organizer/dashboard`
- `GET /api/admin/dashboard`

## Commandes utiles

Voir les logs :

```bash
docker compose logs -f frontend
docker compose logs -f backend
docker compose logs -f database
```

Lancer les migrations manuellement :

```bash
docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction
```

Verifier l'etat :

```bash
docker compose ps
```

Arreter :

```bash
docker compose down
```

## Deploiement VPS

Le deploiement complet avec Docker, Nginx reverse proxy et Certbot est documente dans [deploy.md](deploy.md).
