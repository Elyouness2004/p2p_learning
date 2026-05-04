<div align="center">

<img src="https://img.shields.io/badge/P2P-Learning-6C63FF?style=for-the-badge&logo=academia&logoColor=white" alt="P2P Learning" height="40"/>

# P2P Learning

### Plateforme d'apprentissage peer-to-peer entre étudiants

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Chart.js](https://img.shields.io/badge/Chart.js-4.x-FF6384?style=flat-square&logo=chartdotjs&logoColor=white)](https://www.chartjs.org/)
[![License: MIT](https://img.shields.io/badge/License-MIT-22C55E?style=flat-square)](LICENSE)

**[Fonctionnalités](#-fonctionnalités) · [Architecture](#-architecture) · [Installation](#-installation) · [Comptes de test](#-comptes-de-test) · [Roadmap](#-roadmap)**

</div>

---

## Concept

**P2P Learning** est une plateforme web full-stack qui connecte les étudiants selon leurs compétences et leurs lacunes, pour qu'ils s'enseignent mutuellement.

Chaque étudiant déclare les modules qu'il **maîtrise** et ceux sur lesquels il a des **lacunes**. Un algorithme de matching identifie automatiquement les partenaires complémentaires — ceux avec qui l'échange est mutuellement bénéfique.

> *"Enseigner, c'est apprendre deux fois."* — Joseph Joubert

### Pourquoi ce projet ?

Ce projet a été développé pour explorer concrètement la conception d'une application web complète en PHP natif : modélisation de base de données relationnelle, architecture MVC-like sans framework, sécurité web (OWASP), gestion de sessions, et interactions AJAX en temps réel.

---

## Fonctionnalités

### Étudiants

| Module | Fonctionnalité |
|---|---|
| **Smart Matching** | Algorithme qui identifie les partenaires complémentaires (match parfait : échange bidirectionnel / match partiel : échange unilatéral) |
| **Sessions** | Proposition, confirmation et suivi de sessions avec workflow d'état : `pending → confirmed → done / cancelled` |
| **Messagerie** | Chat en temps réel avec polling AJAX, statut de lecture, compteur de messages non lus |
| **Notation** | Évaluation post-session sur 3 critères : clarté, ponctualité, engagement (1-5 étoiles) — 1 avis max par session |
| **Profil** | Page personnelle avec 4 onglets : Informations, Modules, Avis reçus, Sécurité — upload avatar |
| **Profil public** | Page consultable par la communauté (`/pages/student/user_profile.php?id={userId}`) |
| **Tableau de bord** | Vue synthétique : sessions à venir, messages récents, top matches, score de complétion du profil |
| **Modules custom** | Proposition de nouveaux modules soumis à validation par un admin |

### Administrateurs

| Module | Fonctionnalité |
|---|---|
| **Gestion utilisateurs** | Liste paginée avec avatar, rôle, statistiques |
| **Fiche utilisateur** | Sessions, modules, avis — vue détaillée complète |
| **Actions admin** | Basculer le rôle étudiant ↔ admin, supprimer un compte (avec confirmation) |
| **Validation modules** | Approuver ou rejeter les propositions de modules des étudiants |

### Sécurité

- **Mots de passe** — hashage bcrypt via `password_hash()` / `password_verify()`
- **Injections SQL** — 100% requêtes préparées (`mysqli` bind_param)
- **XSS** — échappement systématique via `htmlspecialchars()` sur tout affichage
- **En-têtes HTTP** — `Content-Security-Policy`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`
- **Rate-limiting** — verrouillage de compte après 5 tentatives échouées (15 min)
- **Sessions** — timeout automatique (1 heure d'inactivité), regénération d'ID
- **Réinitialisation de mot de passe** — token cryptographique à usage unique avec expiration (1 heure)
- **Contrôle d'accès** — guards `require_student()` / `require_admin()` sur toutes les pages protégées

---

## Architecture

```
p2p/
├── config/
│   ├── config.php              # Constantes globales (BASE_URL, DB, limites...)
│   └── config.example.php      # Template à copier pour la config locale
│
├── includes/
│   ├── models/                 # Couche d'accès aux données (Active Record-like)
│   │   ├── UserModel.php       # Profil, modules, stats, rating
│   │   ├── SessionModel.php    # Workflow sessions pédagogiques
│   │   ├── MatchingModel.php   # Algorithme de matching complémentaire
│   │   ├── MessageModel.php    # Chat + compteur non-lus
│   │   ├── NotificationModel.php
│   │   └── AdminModel.php      # Gestion utilisateurs (admin)
│   ├── functions.php           # Helpers : redirect(), e(), guards auth
│   ├── header.php              # En-têtes HTML + CSP headers
│   ├── sidebar.php             # Navigation latérale + topbar (avatar en session)
│   ├── footer.php
│   └── notifications.php
│
├── pages/
│   ├── auth/                   # login, register, logout, forgot_password
│   ├── student/                # dashboard, profile, user_profile, sessions
│   ├── matching/               # Smart matching UI + filtres
│   ├── messages/               # Chat + send.php + poll.php (AJAX polling)
│   └── admin/                  # Tableau de bord + fiche utilisateur
│
├── css/                        # Feuilles de style par module
├── js/                         # Scripts JS par module
├── images/
│   ├── avatars/                # Avatars uploadés (ignoré par git)
│   └── favicon.svg
├── bootstrap/                  # Assets Bootstrap locaux (via download.sh)
├── index.php                   # Landing page publique
└── projet.sql                  # Schéma complet + données de test
```

### Schéma de base de données

```
┌──────────┐    ┌──────────────┐    ┌─────────┐
│  users   │────│ user_modules │────│ modules │
└────┬─────┘    └──────────────┘    └────┬────┘
     │                                    │
     │          ┌──────────┐              │
     ├──────────│ sessions │──────────────┘
     │          └────┬─────┘
     │               │
     │           ┌───┴─────┐
     │           │ ratings │
     │           └─────────┘
     │
     ├─── messages         (sender ↔ receiver)
     ├─── password_resets  (token + expiry)
     └─── custom_modules   (pending/approved/rejected)
```

**8 tables InnoDB** — `utf8mb4_unicode_ci` — contraintes `ON DELETE CASCADE` sur toutes les clés étrangères.

---

## Installation

### Prérequis

- [XAMPP](https://www.apachefriends.org/) ou [LAMPP](https://www.apachefriends.org/) (Apache + PHP 8.0+ + MySQL 8.0+)
- Git

### Étapes

**1. Cloner le dépôt**

```bash
git clone https://github.com/Elyouness2004/p2p_learning.git
```

**2. Placer le projet dans le répertoire web**

```bash
# Linux (LAMPP)
mv p2p_learning /opt/lampp/htdocs/p2p

# Windows (XAMPP) : déplacer dans C:\xampp\htdocs\p2p
```

**3. Créer le dossier d'upload des avatars**

```bash
mkdir -p /opt/lampp/htdocs/p2p/images/avatars
chmod 755 /opt/lampp/htdocs/p2p/images/avatars
```

**4. Configurer l'application**

```bash
cp config/config.example.php config/config.php
```

Éditer [config/config.php](config/config.php) si nécessaire (host, user, password de la DB).

**5. Importer la base de données**

```bash
mysql -u root p2p_learning < projet.sql
```

Ou via **phpMyAdmin** : importer le fichier `projet.sql` (il crée la base `p2p_learning` automatiquement).

**6. (Optionnel) Télécharger Bootstrap en local**

Le projet fonctionne via CDN par défaut. Pour un usage hors-ligne :

```bash
cd bootstrap && bash download.sh
```

**7. Démarrer le serveur**

```bash
# Linux
sudo /opt/lampp/lampp start

# Windows : démarrer Apache + MySQL via le panneau XAMPP
```

**8. Ouvrir dans le navigateur**

```
http://localhost/p2p
```

---

## Comptes de test

| Email | Rôle | Mot de passe |
|---|---|---|
| `admin@gmail.com` | Admin | `admin123` |
| `younes@gmail.com` | Étudiant | `student123` |
| `ahmed@gmail.com` | Étudiant | `password123` |
| `hajar@gmail.com` | Étudiant | `student123` |
| `youssef@gmail.com` | Étudiant | `student123` |
| `ferdaous@gmail.com` | Étudiant | `student123` |

---

## Stack technique

| Composant | Technologie | Rôle |
|---|---|---|
| Langage serveur | PHP 8.0+ natif | Logique métier, templates, auth |
| Base de données | MySQL 8 / MariaDB | Persistance, relations, contraintes |
| Frontend CSS | Bootstrap 5.3 + Bootstrap Icons 1.11 | UI responsive, composants |
| Frontend JS | Vanilla JS + Chart.js | Interactions, polling AJAX, graphiques |
| Typographie | Inter (Google Fonts) | Police principale |
| Serveur local | XAMPP / LAMPP | Environnement de développement |

**Pas de framework PHP** — ce choix délibéré démontre la maîtrise des fondamentaux : gestion manuelle des sessions, routing, sécurité des headers, et pattern MVC implémenté à la main.

---

## Roadmap

- [ ] **API REST** — Exposer les données pour une future app mobile (React Native / Flutter)
- [ ] **Appels vidéo intégrés** — Sessions en temps réel via WebRTC
- [ ] **Notifications push** — Service Workers + Web Push API
- [ ] **Gamification** — Badges, points XP, classements hebdomadaires
- [ ] **Calendrier** — Vue agenda pour planifier et synchroniser les sessions
- [ ] **Recherche avancée** — Filtres par école, filière, disponibilité, note
- [ ] **Export PDF** — Attestations de sessions réalisées
- [ ] **Mode sombre** — Thème dark natif (CSS custom properties)
- [ ] **Tests automatisés** — PHPUnit (modèles) + tests d'intégration
- [ ] **Déploiement cloud** — Dockerfile + GitHub Actions CI/CD → VPS

---

## Auteur

**Youness El Khalfi**

- GitHub : [@Elyouness2004](https://github.com/Elyouness2004)
- Email : khalfi.naj@gmail.com

---

## Licence

Ce projet est distribué sous licence [MIT](LICENSE). Utilisation, modification et redistribution libres.

---

<div align="center">

Développé avec passion — P2P Learning · 2026

</div>
