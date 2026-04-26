# MyExperiences

MyExperiences est une application full-stack de reservation d'experiences locales. Le projet a ete construit comme un projet portfolio complet : catalogue public, authentification, reservations, paiement mock, avis, espace organisateur, administration, donnees de demonstration et deploiement Docker-ready.

Le site est prevu pour etre deploye sur :

```text
https://experiences.bouchard-mehdi.fr
```

## Sommaire

- [Apercu du projet](#apercu-du-projet)
- [Fonctionnalites](#fonctionnalites)
- [Stack technique](#stack-technique)
- [Architecture](#architecture)
- [Structure du repository](#structure-du-repository)
- [Donnees portfolio](#donnees-portfolio)
- [Comptes de demonstration](#comptes-de-demonstration)
- [Lancement avec Docker](#lancement-avec-docker)
- [Variables d'environnement](#variables-denvironnement)
- [Commandes utiles](#commandes-utiles)
- [API principale](#api-principale)
- [Authentification](#authentification)
- [Deploiement VPS](#deploiement-vps)
- [Notes de production](#notes-de-production)

## Apercu du projet

MyExperiences permet a un utilisateur de parcourir des experiences, filtrer le catalogue, consulter une carte, reserver un creneau, effectuer un paiement mock et laisser un avis apres participation.

Le projet inclut aussi des parcours de gestion :

- un organisateur peut gerer ses experiences, ses creneaux et les reservations recues ;
- un utilisateur peut demander a devenir organisateur ;
- un administrateur peut valider les demandes, moderer les avis, gerer les utilisateurs et les experiences.

Le projet est volontairement dimensionne comme une base produit portfolio : assez complet pour montrer une architecture reelle, mais sans dependance a des services payants.

## Fonctionnalites

- Catalogue public d'experiences.
- Recherche et filtres par lieu, prix et date.
- Carte interactive avec geolocalisation utilisateur optionnelle.
- Detail d'une experience avec creneaux disponibles.
- Creation de compte et connexion.
- Authentification Bearer token stockee en base.
- Espace personnel avec reservations, statuts et historique.
- Reservation avec controle des places restantes.
- Paiement mock avec animation succes/echec.
- Annulation de reservation.
- Avis utilisateur apres participation.
- Demande pour devenir organisateur.
- Pre-tri automatique des demandes organisateur.
- Espace organisateur avec CRUD experiences et slots.
- Tableau de bord admin.
- Donnees de demonstration volumineuses pour recruteurs.
- Docker Compose production-ready.
- Documentation de deploiement VPS avec Nginx reverse proxy et Certbot.

## Stack technique

Frontend :

- SvelteKit
- Vite
- JavaScript
- CSS natif
- Leaflet pour la carte
- Nginx pour servir le build statique

Backend :

- Symfony
- Doctrine ORM / Migrations
- PostgreSQL
- PHP-FPM
- Nginx interne au conteneur backend
- Authentification Bearer token custom

Infrastructure :

- Docker
- Docker Compose
- PostgreSQL 16 Alpine
- Reverse proxy Nginx sur VPS
- HTTPS via Certbot

## Architecture

En production, l'application est exposee comme suit :

```text
Navigateur
  |
  | https://experiences.bouchard-mehdi.fr
  v
Nginx VPS + Certbot HTTPS
  |
  | /      -> http://127.0.0.1:8083
  | /api/ -> http://127.0.0.1:3002/api/
  v
Docker Compose
  |
  |-- frontend : SvelteKit statique servi par Nginx
  |-- backend  : Symfony API servi par PHP-FPM + Nginx
  |-- database : PostgreSQL interne
```

Le frontend appelle l'API via `/api`. Aucun `localhost` n'est hardcode cote production.

## Structure du repository

```text
MyExperiences/
|-- front/
|   |-- src/
|   |-- static/
|   |-- docker/nginx/default.conf
|   |-- Dockerfile
|   |-- svelte.config.js
|   `-- vite.config.js
|-- back/
|   |-- src/
|   |-- migrations/
|   |-- docker/nginx/default.conf
|   |-- docker/php/app.ini
|   |-- Dockerfile
|   `-- composer.json
|-- docker-compose.yml
|-- .env.example
|-- README.md
`-- deploy.md
```

## Donnees portfolio

Les migrations creent automatiquement un gros jeu de donnees de demonstration. L'objectif est qu'un recruteur puisse ouvrir le site et voir immediatement une plateforme vivante, sans devoir creer manuellement des contenus.

Le seed portfolio ajoute notamment :

- plus de 120 comptes ;
- plus de 25 organisateurs ;
- plusieurs centaines d'experiences ;
- plus de 1000 creneaux ;
- plusieurs centaines de reservations ;
- des paiements mock ;
- des avis ;
- des demandes organisateur.

Pour repartir d'une base Docker neuve et rejouer toutes les migrations :

```bash
docker compose down -v
docker compose up -d --build
```

Attention : `docker compose down -v` supprime le volume PostgreSQL et donc toutes les donnees locales.

## Comptes de demonstration

Comptes principaux a utiliser pour presenter le projet :

```text
Admin
Email : portfolio.admin@myexperiences.test
Mot de passe : Mxp-Admin!2026-Portfolio

Organisateur
Email : portfolio.organizer@myexperiences.test
Mot de passe : Mxp-Organizer!2026-Portfolio
```

Anciens comptes de test encore disponibles :

```text
Mot de passe : password

admin@myexperiences.test
organizer@myexperiences.test
studio@myexperiences.test
user@myexperiences.test
traveler@myexperiences.test
candidate@myexperiences.test
```

## Lancement avec Docker

Depuis la racine du projet :

```bash
cp .env.example .env
docker compose up -d --build
```

URLs locales Docker :

```text
Frontend : http://127.0.0.1:8083
Backend  : http://127.0.0.1:3002/api/health
API via frontend : http://127.0.0.1:8083/api/health
```

Services Docker :

- `frontend` expose `127.0.0.1:8083`
- `backend` expose `127.0.0.1:3002`
- `database` reste interne au reseau Docker

## Variables d'environnement

Le fichier `.env` racine est utilise par Docker Compose.

Exemple :

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

Des exemples sont fournis :

- `.env.example`
- `back/.env.example`
- `front/.env.example`

Les vrais fichiers `.env` ne doivent jamais etre versionnes.

## Commandes utiles

Voir l'etat des conteneurs :

```bash
docker compose ps
```

Voir les logs :

```bash
docker compose logs -f frontend
docker compose logs -f backend
docker compose logs -f database
```

Relancer les migrations :

```bash
docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction
```

Verifier le backend :

```bash
curl http://127.0.0.1:3002/api/health
curl http://127.0.0.1:8083/api/health
```

Arreter les conteneurs :

```bash
docker compose down
```

Reconstruire apres modification :

```bash
docker compose up -d --build
```

## API principale

Routes publiques :

- `GET /api/health`
- `GET /api/hello`
- `GET /api/experiences`
- `GET /api/experiences/{id}`
- `GET /api/experiences/{id}/reviews`
- `POST /api/auth/register`
- `POST /api/auth/login`

Routes utilisateur authentifie :

- `GET /api/me`
- `POST /api/auth/logout`
- `GET /api/bookings`
- `GET /api/bookings/{id}`
- `POST /api/bookings`
- `POST /api/bookings/{id}/cancel`
- `POST /api/bookings/{id}/pay`
- `POST /api/experiences/{id}/reviews`
- `POST /api/organizer-requests`

Routes organisateur :

- `GET /api/organizer/dashboard`
- `GET /api/organizer/experiences`
- `POST /api/organizer/experiences`
- `PATCH /api/organizer/experiences/{id}`
- `DELETE /api/organizer/experiences/{id}`
- `POST /api/organizer/experiences/{id}/slots`
- `PATCH /api/organizer/slots/{id}`
- `DELETE /api/organizer/slots/{id}`
- `GET /api/organizer/bookings`

Routes admin :

- `GET /api/admin/dashboard`
- `PATCH /api/admin/users/{id}`
- `PATCH /api/admin/experiences/{id}`
- `DELETE /api/admin/experiences/{id}`
- `DELETE /api/admin/reviews/{id}`
- `POST /api/admin/organizer-requests/{id}/approve`
- `POST /api/admin/organizer-requests/{id}/reject`

## Authentification

Le projet n'utilise pas JWT. L'authentification repose sur des tokens API stockes en base.

Flux simplifie :

1. L'utilisateur se connecte via `POST /api/auth/login`.
2. Symfony cree un token Bearer du type `myexp_...`.
3. Le token est stocke dans la table `api_token`.
4. Le frontend conserve le token dans `localStorage`.
5. Les routes protegees utilisent l'en-tete HTTP suivant :

```http
Authorization: Bearer myexp_xxxxxxxxx
```

Ce choix simplifie le projet portfolio : pas de cles JWT, pas de secret JWT supplementaire, et possibilite de revoquer les tokens cote base.

## Deploiement VPS

Le deploiement complet est documente dans [deploy.md](deploy.md).

Resume :

```bash
sudo mkdir -p /home/projects
sudo chown -R $USER:$USER /home/projects
cd /home/projects
git clone https://github.com/BouchardMehdi/MyExperiences.git MyExperiences
cd /home/projects/MyExperiences

cp .env.example .env
nano .env

docker compose up -d --build
```

Ports attendus sur le VPS :

- frontend : `127.0.0.1:8083`
- backend : `127.0.0.1:3002`

Nginx VPS redirige :

- `/` vers `127.0.0.1:8083`
- `/api/` vers `127.0.0.1:3002/api/`

HTTPS :

```bash
sudo certbot --nginx -d experiences.bouchard-mehdi.fr
```

## Notes de production

- Ne pas exposer PostgreSQL publiquement.
- Garder les ports Docker limites a `127.0.0.1`.
- Modifier `APP_SECRET` et `POSTGRES_PASSWORD` en production.
- Ne jamais versionner les vrais fichiers `.env`.
- Les migrations sont lancees automatiquement au demarrage du backend Docker.
- Le projet est concu comme projet portfolio, avec donnees seed pour faciliter la demonstration.
