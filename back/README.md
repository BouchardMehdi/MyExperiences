# Backend MyExperiences

Le backend `back/` est maintenant fige sur une base API-first.

## Surface active

- routage actif limite a `config/routes.yaml`
- seuls les controleurs dans `src/Controller/Api/` sont exposes
- endpoints actuels :
  - `GET /api/health`
  - `GET /api/hello`
  - `GET /api/experiences`
  - `GET /api/experiences/{id}`
  - `POST /api/auth/register`
  - `POST /api/auth/login`
  - `POST /api/auth/logout`
  - `GET /api/me`

## Briques conservees pour la suite

- entites Doctrine : `User`, `Experience`, `Slot`, `Booking`, `Payment`, `Review`
- enums metier dans `src/Enum/`
- repositories Doctrine dans `src/Repository/`
- services metier reutilisables dans `src/Service/`
- voters de securite dans `src/Security/`
- migrations Doctrine dans `migrations/`

## Nettoyage effectue

- suppression des anciens controleurs web Twig
- suppression des formulaires Symfony de rendu serveur
- suppression des templates Twig et assets CSS associes
- retrait des configs Twig / CSRF / traduction dediees a l'ancienne couche web
- simplification de la config `framework` et `security` pour preparer une authentification API plus propre

## Convention pour la suite

- toute nouvelle exposition backend passe par `src/Controller/Api/`
- les reponses doivent rester en JSON
- les futurs besoins d'authentification seront branches sur le firewall `main`
- l'API experiences publique accepte les filtres `location`, `maxPrice` et `date`
- l'authentification API utilise des jetons Bearer persistants stockes dans `api_token`
