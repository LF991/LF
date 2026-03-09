# 🛒 Supermarché en Ligne - Système Complet

Un système e-commerce complet pour supermarché en ligne avec gestion des utilisateurs, commandes, livraisons et administration.

## 📋 Fonctionnalités

### 👤 Pour les Clients
- ✅ Consultation du catalogue de produits
- ✅ Ajout au panier et gestion des quantités
- ✅ Passage de commandes avec adresse de livraison
- ✅ Suivi des commandes en temps réel
- ✅ Historique des commandes
- ✅ Système de notifications
- ✅ Chat AI pour support client

### 👨‍💼 Pour les Administrateurs
- ✅ Gestion complète des produits (CRUD)
- ✅ Gestion des utilisateurs
- ✅ Supervision des commandes
- ✅ Assignation des livreurs
- ✅ Tableaux de bord avec statistiques
- ✅ Gestion des notifications système

### 🚚 Pour les Livreurs
- ✅ Interface dédiée avec commandes assignées
- ✅ Suivi GPS en temps réel
- ✅ Mise à jour du statut des livraisons
- ✅ Historique des livraisons
- ✅ Statistiques de performance

## 🏗️ Architecture

### Backend (PHP REST API)
```
backend/
├── auth/           # Authentification (login, register, logout)
├── products/       # Gestion des produits
├── cart/           # Gestion du panier
├── orders/         # Gestion des commandes
├── delivery/       # Gestion des livraisons
├── notifications/  # Système de notifications
├── chat/           # Chat AI
└── admin/          # Fonctions administrateur
```

### Frontend (HTML/CSS/JS)
```
frontend/
├── index.html          # Page d'accueil
├── css/style.css       # Styles principaux
├── js/
│   ├── api.js          # Utilitaires API
│   ├── auth.js         # Gestion authentification
│   └── main.js         # Fonctions principales
├── client/             # Interface client
├── admin/              # Interface administrateur
└── livreur/            # Interface livreur
```

### Base de Données
- **MySQL** avec support des données géospatiales
- Tables principales : utilisateur, produit, commande, livraison, panier, notifications
- Relations optimisées avec index stratégiques

## 🚀 Installation & Configuration

### Prérequis
- PHP 8.0+
- MySQL 5.7+
- Serveur web (Apache/Nginx)
- Navigateur moderne avec géolocalisation

### Étapes d'installation

1. **Cloner le projet**
   ```bash
   git clone <repository-url>
   cd supermarche_online
   ```

2. **Importer la base de données**
   ```sql
   mysql -u root -p < supermarche_online.sql
   ```

3. **Configurer la base de données**
   - Modifier `config/database.php` avec vos identifiants MySQL
   - Par défaut : localhost, root, (mot de passe vide), supermarche_online

4. **Démarrer le serveur**
   - Placer le projet dans le répertoire web (htdocs/www)
   - Accéder via `http://localhost/supermarche_online`

## 🔐 Comptes de Test

### Administrateur
- Email : admin@supermarche.com
- Mot de passe : admin123

### Livreur
- Email : livreur@supermarche.com
- Mot de passe : livreur123

### Client
- Créer un compte via l'inscription

## 📊 API Endpoints

### Authentification
- `POST /backend/auth/login.php` - Connexion
- `POST /backend/auth/register.php` - Inscription
- `POST /backend/auth/logout.php` - Déconnexion

### Produits
- `GET /backend/products/get.php` - Liste des produits
- `GET /backend/products/get_single.php?id={id}` - Détail produit

### Panier
- `GET /backend/cart/get.php` - Contenu du panier
- `POST /backend/cart/add.php` - Ajouter au panier
- `PUT /backend/cart/update.php` - Modifier quantité
- `DELETE /backend/cart/remove.php` - Supprimer du panier

### Commandes
- `POST /backend/orders/create.php` - Créer commande
- `GET /backend/orders/get.php` - Liste des commandes
- `GET /backend/orders/get_single.php?id={id}` - Détail commande

### Livraisons
- `POST /backend/delivery/assign.php` - Assigner livreur
- `PUT /backend/delivery/update_status.php` - Mettre à jour statut
- `GET /backend/delivery/track.php?id={id}` - Suivi livraison

## 🎨 Technologies Utilisées

- **Backend** : PHP pur (sans framework)
- **Frontend** : HTML5, CSS3, JavaScript ES6+
- **Base de données** : MySQL avec types spatiaux
- **API** : RESTful avec JSON
- **Authentification** : JWT-like tokens
- **UI/UX** : Responsive design, Font Awesome icons

## 🔧 Fonctionnalités Avancées

### Géolocalisation
- Suivi GPS des livreurs en temps réel
- Calcul des distances et optimisation des trajets
- Stockage des positions historiques

### Notifications
- Système multi-canal (in-app, email, push)
- Notifications contextuelles
- Historique des notifications

### Chat AI
- Support client automatisé
- Historique des conversations
- Catégorisation des messages

### Gestion de Stock
- Suivi automatique des stocks
- Alertes de rupture de stock
- Gestion des catégories de produits

## 📈 Métriques & Analyses

- Chiffre d'affaires total
- Nombre de commandes par période
- Performance des livreurs
- Taux de conversion panier
- Satisfaction client (via chat AI)

## 🔮 Évolutions Futures

- Application mobile native
- Système de paiement intégré
- Recommandations personnalisées
- Analytics avancés
- Intégration IoT pour produits frais

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📝 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 📞 Support

Pour toute question ou problème :
- Ouvrir une issue sur GitHub
- Contacter l'équipe de développement

---

Développé avec ❤️ pour révolutionner les courses en ligne ! 🛒🚀
