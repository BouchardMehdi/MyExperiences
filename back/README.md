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
  - `POST /api/experiences/{id}/reviews`
  - `POST /api/auth/register`
  - `POST /api/auth/login`
  - `POST /api/auth/logout`
  - `GET /api/me`
  - `GET /api/bookings`
  - `POST /api/bookings`
  - `POST /api/bookings/{id}/cancel`
  - `POST /api/bookings/{id}/pay`
  - `GET /api/organizer/dashboard`
  - `GET /api/organizer/experiences`
  - `POST /api/organizer/experiences`
  - `PATCH /api/organizer/experiences/{id}`
  - `DELETE /api/organizer/experiences/{id}`
  - `POST /api/organizer/experiences/{id}/slots`
  - `PATCH /api/organizer/slots/{id}`
  - `DELETE /api/organizer/slots/{id}`
  - `GET /api/organizer/bookings`

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
- les reservations sont protegees par l'auth Bearer et gerees en transaction Doctrine
- le paiement mock est expose sur les reservations avec simulation `success` ou `failure`
- les avis sont limites a un par utilisateur et ne sont autorises qu'apres participation payee
- l'espace organisateur permet de gerer experiences, creneaux et reservations depuis `/api/organizer/*`
