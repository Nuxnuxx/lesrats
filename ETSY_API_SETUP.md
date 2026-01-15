# Configuration de l'API Etsy

## 📋 Guide de configuration de l'application Etsy

Pour connecter vos boutiques Etsy à l'application, vous devez créer une application Etsy et obtenir vos clés API.

---

## 🔑 Étape 1 : Créer une application Etsy

### 1. Accédez au portail développeur Etsy

👉 https://www.etsy.com/developers/register

### 2. Connectez-vous avec votre compte Etsy

Si vous n'avez pas de compte Etsy, créez-en un d'abord.

### 3. Créez une nouvelle application

- Cliquez sur **"Create a New App"** ou **"Créer une nouvelle application"**
- Remplissez le formulaire :

#### **Informations de l'application:**

| Champ | Valeur |
|-------|--------|
| **App Name** | LA MAGIE - Dropshipping Manager |
| **App Description** | Application de gestion automatisée pour dropshipping Etsy/AliExpress |
| **App URL** | http://localhost:8000 (ou votre domaine) |
| **Callback URL** | http://localhost:8000/etsy/callback |

#### **Permissions requises (scopes):**

Sélectionnez les permissions suivantes :

- ✅ **listings_r** - Lire les listings
- ✅ **listings_w** - Écrire les listings
- ✅ **listings_d** - Supprimer les listings
- ✅ **shops_r** - Lire les informations boutique
- ✅ **shops_w** - Écrire les informations boutique
- ✅ **transactions_r** - Lire les transactions (commandes)
- ✅ **transactions_w** - Écrire les transactions
- ✅ **email_r** - Lire l'email

### 4. Acceptez les conditions d'utilisation

Lisez et acceptez les **Etsy API Terms of Use**.

### 5. Cliquez sur **"Create App"**

---

## 🔐 Étape 2 : Récupérer vos clés API

Une fois l'application créée, vous verrez deux informations importantes :

### **Keystring (Client ID)**
```
abc123xyz456def789ghi012jkl345mno678pqr901stu234
```

### **Shared Secret (Client Secret)**
```
xyz789abc012def345ghi678jkl901mno234pqr567stu890
```

⚠️ **IMPORTANT :** Gardez votre **Shared Secret** confidentiel ! Ne le partagez jamais publiquement.

---

## ⚙️ Étape 3 : Configurer votre application Laravel

### 1. Ouvrez le fichier `.env`

Localisé à la racine du projet : `c:\Users\Laylay\Documents\LA_MAGIE1\.env`

### 2. Ajoutez vos clés API Etsy

Remplacez les valeurs vides par vos clés :

```env
ETSY_CLIENT_ID=votre_keystring_ici
ETSY_CLIENT_SECRET=votre_shared_secret_ici
ETSY_REDIRECT_URI=http://localhost:8000/etsy/callback
```

### Exemple avec vraies valeurs :

```env
ETSY_CLIENT_ID=abc123xyz456def789ghi012jkl345mno678pqr901stu234
ETSY_CLIENT_SECRET=xyz789abc012def345ghi678jkl901mno234pqr567stu890
ETSY_REDIRECT_URI=http://localhost:8000/etsy/callback
```

### 3. Sauvegardez le fichier `.env`

### 4. Redémarrez le serveur Laravel (si nécessaire)

```bash
# Arrêtez le serveur (Ctrl+C dans le terminal)
# Puis relancez-le
php artisan serve
```

---

## ✅ Étape 4 : Tester la connexion

### 1. Créez une boutique dans l'application

- Allez sur http://localhost:8000/shops
- Cliquez sur **"Nouvelle Boutique"**
- Remplissez le formulaire (nom + devise)
- Créez la boutique

### 2. Connectez la boutique à Etsy

- Ouvrez la boutique (cliquez sur **"Voir"**)
- Cliquez sur le bouton **"Connecter à Etsy"** (orange)
- Vous serez redirigé vers Etsy
- Autorisez l'application à accéder à votre boutique
- Vous serez redirigé vers l'application

### 3. Vérifiez la connexion

Si tout fonctionne :
- ✅ Vous verrez **"Boutique connectée à Etsy avec succès !"**
- ✅ L'**ID Etsy** s'affichera dans les détails de la boutique
- ✅ Le bouton devient **"Déconnecter d'Etsy"** (rouge)

---

## 🔄 Étape 5 : Environnement de production (plus tard)

### Quand vous déployez en production :

1. **Mettez à jour l'application Etsy**
   - Retournez sur https://www.etsy.com/developers/your-apps
   - Sélectionnez votre app
   - Modifiez :
     - **App URL** : https://votredomaine.com
     - **Callback URL** : https://votredomaine.com/etsy/callback

2. **Mettez à jour le .env de production**
   ```env
   ETSY_REDIRECT_URI=https://votredomaine.com/etsy/callback
   APP_URL=https://votredomaine.com
   ```

---

## 🐛 Dépannage

### Erreur : "Invalid redirect_uri"

**Cause :** L'URL de callback ne correspond pas à celle configurée dans l'app Etsy.

**Solution :**
1. Vérifiez que `ETSY_REDIRECT_URI` dans `.env` = Callback URL dans l'app Etsy
2. Les deux doivent être **exactement identiques** (http vs https, trailing slash, etc.)

### Erreur : "Invalid client_id"

**Cause :** Le client_id (Keystring) est incorrect.

**Solution :**
1. Copiez à nouveau le **Keystring** depuis le portail développeur Etsy
2. Collez-le dans `.env` sans espaces avant/après

### Erreur : "Access denied"

**Cause :** Vous n'avez pas autorisé toutes les permissions requises.

**Solution :**
1. Retournez sur le portail développeur
2. Vérifiez que toutes les permissions (scopes) sont cochées
3. Réessayez la connexion

### La boutique ne se connecte pas

**Solution :**
1. Vérifiez les logs Laravel : `storage/logs/laravel.log`
2. Vérifiez que le serveur est bien lancé sur http://localhost:8000
3. Effacez le cache : `php artisan config:clear`

---

## 📚 Ressources

- **Documentation officielle Etsy API v3 :** https://developer.etsy.com/documentation
- **Portail développeur Etsy :** https://www.etsy.com/developers
- **Guide OAuth 2.0 Etsy :** https://developer.etsy.com/documentation/essentials/authentication

---

## 🔒 Sécurité

### ⚠️ Bonnes pratiques :

1. **Ne commitez JAMAIS le fichier `.env` sur Git**
   - Le `.gitignore` le bloque déjà, mais vérifiez

2. **Ne partagez JAMAIS votre `ETSY_CLIENT_SECRET`**
   - C'est comme un mot de passe

3. **Utilisez HTTPS en production**
   - Les tokens OAuth transitent dans l'URL

4. **Régénérez vos clés si elles sont exposées**
   - Allez sur le portail Etsy → Votre app → Regenerate

---

## ✅ Checklist de configuration

- [ ] Compte Etsy créé
- [ ] Application Etsy créée sur le portail développeur
- [ ] Permissions (scopes) configurées
- [ ] Callback URL configurée : `http://localhost:8000/etsy/callback`
- [ ] `ETSY_CLIENT_ID` ajouté dans `.env`
- [ ] `ETSY_CLIENT_SECRET` ajouté dans `.env`
- [ ] `ETSY_REDIRECT_URI` configurée dans `.env`
- [ ] Serveur Laravel redémarré
- [ ] Test de connexion réussi

---

**Développé avec ❤️ par TheLayns**
**Dernière mise à jour : 15 janvier 2026**
