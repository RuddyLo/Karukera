# qualiblue.com — Renouvellement SSL (VPS LWS)

> Document de suivi, mis à jour à chaque étape. Sujet indépendant du projet Karukera (juste stocké dans ce repo à la demande de l'utilisateur).

## Contexte

- Hébergement : VPS LWS, IP `192.162.70.65`, hostname `vps32330`
- OS : **Debian GNU/Linux 9 (stretch)** — ⚠️ EOL, dépôts déplacés vers `archive.debian.org`
- Serveur web : Apache2 (actif), + Passenger, PHP-CGI 7.0
- Panel de gestion : **ISPConfig** (`000-ispconfig.vhost`, `000-ispconfig.conf` présents)
- Problème initial : certificat SSL expiré sur `qualiblue.com` → site down
- Site tourne directement sur le serveur (pas de Docker pour ce site — Docker est présent sur la machine mais pour autre chose, `docker.list` dans les sources apt)

## Décision d'architecture

- **On gère le SSL de `qualiblue.com` en certbot manuel, indépendamment du panel ISPConfig.**
- Le client final (non technique) ne touche jamais au panel ISPConfig → aucun risque qu'une sauvegarde de site dans ISPConfig écrase la config Apache manuelle.
- Seul risque identifié : si **nous-mêmes** retournons éditer ce site précis dans ISPConfig (onglet SSL/domaine) plus tard → ISPConfig régénère son vhost et écraserait les ajouts manuels.
- Emails (Postfix/Dovecot, `mail.qualiblue.com`) gérés séparément via le module "Email" d'ISPConfig — **non affectés** par ce changement, car on ne touche que le vhost Apache du site web.

## Accès

- Connexion SSH initiale : `www-data` (pas de sudo — "n'apparaît pas dans le fichier sudoers")
- Accès root confirmé disponible via `su root`

## Diagnostic effectué

| Vérification | Résultat |
|---|---|
| DNS `qualiblue.com` | `192.162.70.65` — correspond bien à l'IP du VPS (`hostname -I` / `ifconfig.me`) ✅ prêt pour challenge HTTP-01 |
| Mail | `qualiblue.com` MX → `mail.qualiblue.com` (priorité 10) — cert mail séparé, hors scope |
| `apt update` | **Échec** — dépôts `ftp.debian.org/debian stretch` et `security.debian.org stretch/updates` en 404 (Debian 9 stretch EOL) |
| vhosts Apache actifs | `000-apps.vhost`, `000-ispconfig.vhost`, `000-ispconfig.conf`, `100-qualiblue.com.vhost`, `100-genienomad.com.vhost`, `100-qualiblueoutillage.store.vhost`, `999-acme.conf`, `qualiblue.conf` |

### `/etc/apt/sources.list` actuel

```
deb http://ftp.debian.org/debian stretch main contrib

deb http://ftp.debian.org/debian stretch-updates main contrib

deb http://security.debian.org stretch/updates main contrib

deb [arch=amd64] https://download.docker.com/linux/debian stretch stable
```

## Plan d'action

1. [x] Corriger `sources.list` → pointer vers `archive.debian.org` (stretch EOL) — fait, `sources.list.bak` conservé en backup, `apt update` fonctionne
2. [x] Certbot déjà installé (`0.28.0-1~deb9u3`) — pas besoin d'installer, `snapd` écarté (version candidate 2.21 trop ancienne)
3. [x] Inspecter les vhosts existants — voir "Diagnostic vhost" ci-dessous, situation plus complexe qu'un simple cert expiré
4. [x] Vérifier le mécanisme PHP — `mod_php` global, aucune config spécifique nécessaire
5. [x] Créer un nouveau vhost dédié `qualiblue.com`/`www.qualiblue.com` (:80 + :443) pointant vers le **vrai** docroot — `050-qualiblue.com-manual.vhost`
6. [x] Générer un **nouveau** certificat Let's Encrypt (HTTP-01, webroot = vrai docroot) — lignée `www.qualiblue.com`, expire 2026-10-23
7. [x] Reload Apache, tester `https://qualiblue.com` — **200 OK, cert valide, PHP fonctionne**
8. [ ] Vérifier que `certbot.timer` (déjà actif) prend bien en charge le renouvellement du nouveau cert — `certbot renew --dry-run --cert-name www.qualiblue.com` lancé, en attente du résultat
9. [ ] Tester `https://www.qualiblue.com/` — en attente du résultat
10. [ ] Vérifier qu'aucun impact sur le mail (`mail.qualiblue.com`) — non vérifié explicitement, aucune config mail touchée durant l'intervention

## Nettoyage optionnel (non urgent, à faire un jour)

- Fichier de test `.well-known/acme-challenge/testfile` déjà supprimé
- `sites-available/` contient ~15 fichiers `qualiblue.conf.save.*` / `.old69` / `.https` / `.8000` / `.8443` obsolètes — non touchés, pourraient être archivés/supprimés après validation que rien n'en dépend
- Vhost `backup.qualiblue.com` (dans `qualiblue.conf`) confirmé inutilisé par l'utilisateur — pourrait être supprimé
- OS Debian 9 (stretch) en fin de vie depuis 2022 — dépôts fonctionnent via `archive.debian.org` mais plus aucune mise à jour de sécurité officielle ; migration vers un OS supporté à envisager à moyen terme

### Diagnostic vhost (important)

- `apachectl -S` : pour le port 80, `qualiblue.com` est servi par `100-qualiblue.com.vhost` (généré ISPConfig), **docroot vide** (`/var/www/clients/client0/web1/web` — juste `error/` et `stats/`, pas de site)
- **Le vrai site** est dans `/var/www/qualiblue.com/www/current/web/public` (index.php, CGV, blog, uploads, daté oct. 2025) — utilisé aujourd'hui uniquement par le vhost `:443` `backup.qualiblue.com` (dans `qualiblue.conf`, géré manuellement, hors ISPConfig)
- Aucun vhost `:443` n'existe pour `qualiblue.com` lui-même
- Confirmé empiriquement : `curl http://qualiblue.com/` → **403 Forbidden** (sert bien le dossier vide)
- `sites-available/` contient une dizaine de versions historiques (`qualiblue.conf.save.1` à `.8`, `.old69`, `.https`, `.8000`, `.8443`...) — traces de bidouille manuelle sur plusieurs années, non démêlées, laissées telles quelles
- **Décision** : créer un nouveau vhost propre et dédié plutôt que de modifier les fichiers existants (ISPConfig ou legacy), pointant vers le vrai docroot
- Confirmé par l'utilisateur : `/var/www/qualiblue.com/www/current/web/public` est bien le vrai site actuel (`backup.qualiblue.com` n'est pas utilisé, config legacy oubliée)
- PHP : `php7_module` (mod_php classique) chargé globalement, pas besoin de config FastCGI/php-fpm spécifique dans le nouveau vhost
- `sites-enabled/` inclus sans restriction d'extension (`IncludeOptional sites-enabled/`) — le nouveau fichier peut avoir n'importe quel nom

### Nouveau vhost créé

- `/etc/apache2/sites-available/050-qualiblue.com-manual.vhost` (symlink dans `sites-enabled/`), nommé pour se charger **avant** `100-qualiblue.com.vhost` (résout le conflit de nom en gagnant le tie-break alphabétique)
- Contient pour l'instant uniquement le bloc `:80` (docroot réel + passthrough `/.well-known/acme-challenge/` + redirect https) — `apachectl configtest` : OK
- Bloc `:443` sera ajouté après génération du certificat (évite qu'Apache échoue à charger un cert inexistant)
- Activé (`systemctl reload apache2`) — confirmé : `curl http://qualiblue.com/` → `301` (notre vhost gagne, redirige vers https comme prévu, cert pas encore généré)
- Confirmé par l'utilisateur : `/var/www/qualiblue.com/www/current/web/public` est bien le vrai contenu du site (`backup.qualiblue.com` = config oubliée, jamais utilisée)

### Piège rencontré : premier `certbot certonly --webroot` échoué (404)

- `999-acme.conf` (sites-enabled) définit un `Alias /.well-known/acme-challenge` **global** (server-wide) vers `/usr/local/ispconfig/interface/acme/.well-known/acme-challenge` — géré par ISPConfig pour son propre usage (panel/hostname), s'applique par défaut à tous les vhosts et masquait notre docroot
- **Fix** : ajout d'un `Alias /.well-known/acme-challenge/` local dans `050-qualiblue.com-manual.vhost`, qui prend priorité sur le global pour ce vhost précis — confirmé fonctionnel via fichier de test (`curl` → `test-ok`)
- ⚠️ À retenir si un autre site sur ce serveur a besoin de certbot manuel un jour : même piège à prévoir, même fix (Alias local dans le vhost)

### Certificat généré

- `certbot certonly --webroot` réussi (après le fix Alias) — lignée `www.qualiblue.com` étendue pour couvrir `qualiblue.com` + `www.qualiblue.com`
- `/etc/letsencrypt/live/www.qualiblue.com/fullchain.pem` + `privkey.pem`
- Expire le **2026-10-23**

### Bloc `:443` ajouté

- `050-qualiblue.com-manual.vhost` complété avec `<VirtualHost *:443>` (même docroot, cert Let's Encrypt) — `apachectl configtest` : OK
- Reload + test : **`https://qualiblue.com` → 200 OK**, cert Let's Encrypt valide (CN=qualiblue.com, expire 23/10/2026), cookie PHPSESSID présent (PHP fonctionne)
- Reste : tester `www.qualiblue.com`, vérifier `certbot renew --dry-run`, confirmer visuellement dans un navigateur, vérifier le mail non impacté

### Diagnostic certificats existants (`certbot certificates`)

- Confiance TLS OK : `curl -sI https://acme-v02.api.letsencrypt.org/directory` → `HTTP/2 200` (pas de souci de root CA sur ce vieux Debian)
- `certbot.timer` actif et fonctionnel (dernier passage il y a 16h), mais ne renouvelle avec succès que `vps32330.serveur-vps.net` (valide, 43j restants — cert hostname/panel)
- Certs du site **morts depuis longtemps**, renouvellement auto silencieusement en échec dessus :
  - `qualiblue.com` : expiré depuis 2020-08-09
  - `www.qualiblue.com` : expiré depuis 2020-08-09
  - `qualiblue.com-0001` (wildcard `*.qualiblue.com`) : expiré depuis 2023-10-08 (probable validation DNS-01 cassée)
- Décision : repartir sur un certificat neuf (non-wildcard) plutôt que de réparer l'historique DNS-01 cassé

## Historique des échanges / décisions

- Confirmation : VPS root complet, Apache — pas de mutualisé
- `www-data` sans sudo → accès root via `su root` confirmé par l'utilisateur
- Choix assumé : gestion SSL manuelle hors ISPConfig pour ce site, client non technique ne touchera jamais au panel
