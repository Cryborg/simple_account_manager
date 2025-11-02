#!/usr/bin/env python3
import sqlite3
import sys
import hashlib

# Connexion à la base de données
db_path = '/home/cryborg/Projects/arcade-franck/tools/accounts/data/accounts.db'
conn = sqlite3.connect(db_path)
cursor = conn.cursor()

print("=== Gestion des utilisateurs ===\n")

# Lister tous les utilisateurs
print("Liste des utilisateurs :")
cursor.execute("SELECT id, username FROM users")
users = cursor.fetchall()

for user in users:
    print(f"  - ID: {user[0]}, Username: {user[1]}")

print("\n")

# Fonction pour réinitialiser le mot de passe (avec bcrypt en PHP)
if len(sys.argv) > 2:
    username = sys.argv[1]
    new_password = sys.argv[2]

    print(f"⚠️  ATTENTION : Ce script ne peut pas créer de hash bcrypt PHP compatible.")
    print(f"Pour réinitialiser le mot de passe de '{username}', vous devez :")
    print(f"1. Soit utiliser PHP directement sur le serveur de production")
    print(f"2. Soit créer un nouveau compte depuis https://devplayground.ovh/tools/accounts/register.php")
    print(f"3. Soit me demander de créer un script PHP à uploader sur le serveur")
else:
    print("Pour réinitialiser un mot de passe, utilisez :")
    print("python3 reset_password.py <username> <nouveau_mot_de_passe>")

conn.close()
