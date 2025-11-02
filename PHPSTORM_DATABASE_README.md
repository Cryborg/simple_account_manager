# Accès à la base de données SQLite depuis PHPStorm

## Problème

Lorsque tu utilises PHPStorm sous Windows avec un projet dans WSL, SQLite ne peut pas accéder directement aux fichiers via les chemins réseau UNC (`\\wsl.localhost\...`).

**Erreur typique :**
```
[SQLITE_ERROR] SQL error or missing database (invalid uri authority: wsl.localhost)
```

## Solution : Copier la base vers Windows

### Méthode Simple (Snapshot)

Utilise le script fourni pour copier la base de données dans un endroit accessible depuis Windows :

```bash
./copy_db_to_windows.sh
```

Cela copie la base vers `C:\Temp\accounts.db`.

### Configuration PHPStorm

1. **Ouvrir Database tool window** :
   - Menu `View` → `Tool Windows` → `Database`
   - Ou raccourci : Double `Shift` → taper "Database"

2. **Ajouter une source de données** :
   - Cliquer sur `+` (en haut à gauche)
   - Sélectionner `Data Source` → `SQLite`

3. **Configurer la connexion** :
   - **File:** `C:\Temp\accounts.db`
   - **Test Connection** → **OK**
   - **Apply** → **OK**

4. **Naviguer dans la base** :
   - Développer l'arborescence pour voir les tables
   - Double-cliquer sur une table pour voir les données
   - Clic droit → `SQL Scripts` → `New SQL Script` pour des requêtes

## Synchronisation

⚠️ **Important** : La copie dans `C:\Temp\` est un **snapshot** (instantané) !

### Pour voir les changements récents :

```bash
# Depuis le terminal WSL dans le projet
./copy_db_to_windows.sh
```

### Alternative : Synchronisation Auto (Watch Mode)

Si tu veux que la base se synchronise automatiquement à chaque modification, utilise ce script :

```bash
./watch_db.sh
```

(Laisse ce terminal ouvert en arrière-plan)

## Workflow Recommandé

### Option A : Développement WSL uniquement

- Travailler dans WSL (PHP, tests, migrations)
- Copier vers Windows seulement pour l'inspection visuelle
- Relancer `./copy_db_to_windows.sh` quand tu veux voir les changements

### Option B : Développement Mixte

1. Travailler dans PHPStorm Windows (éditeur)
2. Exécuter l'application dans WSL (serveur PHP)
3. Synchroniser la base périodiquement pour inspection

## Chemins Importants

| Environnement | Chemin |
|---------------|--------|
| **WSL** | `/home/cryborg/Projects/arcade-franck/tools/accounts/data/accounts.db` |
| **Windows (UNC)** | `\\wsl.localhost\Ubuntu\home\cryborg\...` ⚠️ Ne fonctionne pas avec SQLite |
| **Windows (copie)** | `C:\Temp\accounts.db` ✅ Fonctionne |

## Scripts Disponibles

| Script | Description | Usage |
|--------|-------------|-------|
| `copy_db_to_windows.sh` | Copie unique vers Windows | `./copy_db_to_windows.sh` |
| `watch_db.sh` | Synchronisation auto en temps réel | `./watch_db.sh` (laisser tourner) |
| `check_db.php` | Vérifier l'état de la base WSL | `php check_db.php` |

## Requêtes SQL Utiles

Une fois connecté dans PHPStorm, voici quelques requêtes utiles :

### Voir tous les utilisateurs
```sql
SELECT id, username, email, is_admin, created_at
FROM users
ORDER BY id;
```

### Transactions récentes
```sql
SELECT
    u.username,
    t.type,
    t.amount,
    t.description,
    t.transaction_date
FROM transactions t
JOIN users u ON t.user_id = u.id
ORDER BY t.transaction_date DESC
LIMIT 20;
```

### Solde par utilisateur
```sql
SELECT
    u.username,
    COUNT(t.id) as nb_transactions,
    SUM(CASE WHEN t.type = 'recette' THEN t.amount ELSE 0 END) as recettes,
    SUM(CASE WHEN t.type = 'depense' THEN t.amount ELSE 0 END) as depenses,
    SUM(CASE WHEN t.type = 'recette' THEN t.amount ELSE -t.amount END) as solde
FROM users u
LEFT JOIN transactions t ON u.id = t.user_id
GROUP BY u.id, u.username;
```

### Structure d'une table
```sql
PRAGMA table_info(users);
```

## Dépannage

### ❌ "Database is locked"

Si PHPStorm affiche cette erreur :
1. Fermer tous les terminaux qui pourraient avoir la DB ouverte
2. Arrêter le serveur PHP : `pkill -f "php -S"`
3. Recopier la base : `./copy_db_to_windows.sh`

### ❌ "File not found: C:\Temp\accounts.db"

Exécuter le script de copie :
```bash
./copy_db_to_windows.sh
```

### ❌ Les changements ne s'affichent pas

1. Recopier la base : `./copy_db_to_windows.sh`
2. Dans PHPStorm : Clic droit sur la connexion → `Refresh`

### ⚠️ Modifications dans PHPStorm

**ATTENTION** : Si tu modifies la base dans PHPStorm (INSERT, UPDATE, DELETE), tu modifies la copie dans `C:\Temp\`, pas la vraie base dans WSL !

Pour appliquer les changements à la vraie base :
```bash
# Copier de Windows vers WSL (sens inverse)
cp /mnt/c/Temp/accounts.db data/accounts.db
```

⚠️ **Recommandation** : Utilise PHPStorm uniquement en **lecture seule** (inspection). Fais toutes les modifications via l'application PHP ou les migrations.

## Alternatives

### Solution 1 : Base de données directement sur Windows

Déplacer tout le projet sur Windows (plus simple mais performances réduites) :

```
C:\Users\cryborg\Projects\arcade-franck\...
```

### Solution 2 : MySQL/PostgreSQL dans Docker

Utiliser une vraie base de données client-serveur accessible depuis WSL et Windows :

```yaml
# docker-compose.yml
services:
  db:
    image: mysql:8
    ports:
      - "3306:3306"
```

## Conclusion

La solution actuelle (copie vers `C:\Temp\`) est **la plus simple** pour ton setup WSL + PHPStorm Windows.

✅ **Avantages :**
- Fonctionne immédiatement
- Pas de changement d'architecture
- Inspection visuelle facile

⚠️ **Inconvénient :**
- Nécessite une copie manuelle pour voir les changements

Pour un workflow optimal : utilise le script de copie avant chaque inspection.
