# CS2 WeaponPaints Loadout Manager — fork

Fork of [wtf729/cs2-WeaponPaints-steamless-website](https://github.com/wtf729/cs2-WeaponPaints-steamless-website), rebased on **v1.3.0**.

**This file documents only what the fork changes.** Features, installation and day-to-day use are unchanged and are described in the [upstream README](https://github.com/wtf729/cs2-WeaponPaints-steamless-website#readme). `README_cn.md` is upstream's, in Simplified Chinese.

[English](#what-the-fork-changes) · [Français](#ce-que-le-fork-change)

---

## What the fork changes

### Files added

Nothing upstream ships. Removing them leaves the original site intact.

| File | Role |
|---|---|
| `class/inspect.php` | CS2 inspect link encoder and decoder (`CEconItemPreviewDataBlock`), written by hand, no dependency |
| `class/inspect_bridge.php` | Glue between the loadout tables and the encoder, kept apart so upstream changes rarely collide |
| `actions/inspect.php` | Import handler, and the read-only endpoint the page polls for kill counts |
| `assets/js/inspect.js` | The import window, the clipboard button and the counter refresh |
| `Dockerfile` | PHP-FPM + nginx image, with the extensions the site actually uses |
| `docker-compose.yml` | Deployment behind Traefik, entirely driven by `.env` |
| `nginx.conf` | Vhost: PHP entry point, denied paths, static assets, security headers |
| `.env.example` | Template for every setting |
| `.dockerignore`, `.gitattributes` | Build context, and LF endings for the files Linux consumes |

### Upstream files modified

| File | Change |
|---|---|
| `class/weapon_data.php` | Team 1 (*Global*) removed; charm offsets no longer clamped to ±1 |
| `class/application.php` | *Global* dropped from the side list |
| `class/translations.php` | 20 keys added, in both languages |
| `class/bootstrap.php`, `actions/bootstrap.php` | Register the two new files |
| `actions/skin.php` | Kill count settled once, before the branches, so no path can wipe it |
| `config.php` | Every setting reads the environment first; `LOCK_STATTRAK_COUNT` added |
| `index.php` | Two values handed to the page: viewer address, counter endpoint |
| `views/layout/header.php` | `data-team` on `<html>`, driving the accent colour |
| `views/layout/footer.php` | Loads the new script |
| `views/components/*.php` | 3D button on each card, import window, reset button, no *Global* link |
| `assets/css/style.css` | Accent driven by `--primary` / `--primary-rgb` in both themes |
| `.gitignore` | Ignores `.env` |

### 3D placement bridge

Placing stickers and charms from a flat picker is guesswork. Rather than rebuilding a 3D viewer, the fork bridges the site with an existing one through CS2 inspect links.

Every weapon, knife and glove card carries a **3D** button. It opens a window offering a link that loads that exact piece in [skincraft.gg](https://skincraft.gg), and a field to paste back the link the viewer produces. A *Paste from clipboard* button appears when the browser allows reading the clipboard.

1. Click **3D**, then **Open in SkinCraft**. The weapon arrives with its skin, wear, pattern, StatTrak, name tag, stickers and charm already applied.
2. Arrange everything in the viewer, then copy its inspect link.
3. Paste it back and import. Run `!ws` again or respawn in game to apply it.

Nothing that leaves the decoder reaches the database unchecked: the weapon must match the card being edited, and paint, sticker and charm ids are cross-referenced against `data/*.json` first. Fused paints are accepted when skin fusion is enabled.

The viewer refuses to be framed, so the button opens it in a tab rather than embedding it.

### Known limitation: fine sticker placement

The *Keep the fine sticker placement* checkbox is **off by default** and should usually stay that way.

`SetStickers()` in the WeaponPaints plugin writes `sticker slot N schema = 0` whenever a sticker has a non-zero offset. That discards the slot's base position on the model, so every sticker collapses onto a common origin and piles up in the middle of the weapon — five stickers then look like one.

Unticked, sticker offsets are dropped on import and the stickers fall back to their default, properly spread positions. Charm offsets are never affected. Tick it only if the plugin gains the ability to honour them.

### Charms

Requires the `weapon_keychain` column, which the site reads unconditionally:

```sql
ALTER TABLE `wp_player_skins` ADD COLUMN `weapon_keychain` VARCHAR(64) NOT NULL DEFAULT '0;0;0;0;0';
```

Charm offsets are world units — a genuine inspect link routinely carries values around 10 — so they are not restricted to a unit range.

### StatTrak counter

Players set their own kill count by default. The counter is game state the plugin maintains, so an editable field lets anyone claim a record they never earned: set `LOCK_STATTRAK_COUNT=true` to make it read-only. The check is enforced server side, in the settings dialog and on import alike. The StatTrak toggle itself stays editable either way.

The count survives everything else. Picking another skin, editing the wear or importing a link no longer resets it; a **Reset** button zeroes it deliberately, and stays available even when the field is locked.

Since the plugin keeps incrementing it in game, the page refreshes it every 30 seconds from `index.php?action=stattrak_counts`, a read-only endpoint. Hidden tabs stop polling, and a field being typed into is left alone.

### Sides only, no global tab

Loadouts are edited per side, T or CT. The *Global* tab, which wrote to both at once, is gone; an old link carrying `team=1` lands on the T side. Music kits and collectible pins used to appear on that tab alone — they are stored per side, so they now show on both.

Editing T turns the interface orange, CT blue. Everything derives from `--primary` and `--primary-rgb`, switched by `data-team` on the `html` element, so both themes follow the side and a third palette is a four-line addition.

### Container deployment

`docker-compose.yml` runs the site behind Traefik with no port published: the proxy is the only thing that reaches nginx. Every deployment-specific value — domain, router, entrypoint, middlewares, TLS options, certificate resolver, network names, database credentials — comes from `.env`, which git ignores. Nothing personal is tracked.

```bash
cp .env.example .env    # fill it in
docker compose up -d --build
```

`nginx.conf` denies `class/`, `actions/`, `views/`, `tools/` and `config.php`, so `index.php` stays the only entry point. A health check exercises nginx and PHP without touching the database.

### Login throttling

Failures are counted per client IP, which requires the container to see the real one: `nginx.conf` rebuilds the client address from `X-Forwarded-For`, trusted from the private ranges alone so a client cannot forge its own identity. Without it every visitor arrives as the proxy, and five wrong tries by one player lock the login for all of them.

Five failures per 30 minutes then lock that IP for five minutes, rather than upstream's one. Reaching the limit clears the failure count, so the lock is what really caps a brute force run — and loadout PINs have no minimum length.

### Settings

All optional except the database. Absent means the default applies.

| Setting | Default | Effect |
|---|---|---|
| `DB_HOST` `DB_PORT` `DB_NAME` `DB_USER` `DB_PASS` | — | The database the plugin uses |
| `SITE_ACCESS_PASSWORD` | *(empty)* | Password asked on entry; empty means an open site |
| `ADMIN_PASSWORD` | *(empty)* | Administrator mode; empty disables it |
| `DEFAULT_LANGUAGE` | `en` | `en` or `zh-CN` |
| `DEFAULT_WEB_THEME` | `dark` | `dark` or `light`; visitors can switch |
| `SITE_NAME_EN` `SITE_NAME_ZH_CN` | built-in | Site name per language |
| `ENABLE_SKIN_FUSION` | `true` | Cross-weapon paint combinations |
| `LOCK_STATTRAK_COUNT` | `false` | Makes the kill count read-only |
| `AUTH_RATE_LIMIT_ATTEMPTS` | `5` | Failures allowed in the window |
| `AUTH_RATE_LIMIT_WINDOW_SECONDS` | `1800` | Failure tracking window |
| `AUTH_RATE_LIMIT_LOCK_SECONDS` | `300` | Lock duration |
| `CONTAINER_NAME` | `cs2-skin-web` | Names the image and the container |
| `TZ` | `Europe/Paris` | Container timezone |

A plain PHP install without Docker still works by editing `config.php` directly: the environment is only read first, the values in the file remain the fallback.

### Fixes carried on top of upstream

* The StatTrak kill counter is read from `wp_player_skins` rather than the settings cache. It used to display as `0` for any counter the plugin had incremented in game, and saving wrote that zero straight back.
* A name tag held by the row is no longer blanked by the settings cache, which could only ever replace it.
* Charm offsets are no longer clamped into a unit range, which used to drag every imported charm onto the same wrong spot.
* Two stickers announcing the same slot no longer overwrite each other: the second moves to the first free slot, so five stickers stay five.
* Login throttling keys on the real client address behind a reverse proxy, and the lock lasts five minutes rather than one.

---

## Ce que le fork change

Fork de [wtf729/cs2-WeaponPaints-steamless-website](https://github.com/wtf729/cs2-WeaponPaints-steamless-website), rebasé sur la **v1.3.0**.

**Ce fichier ne documente que ce que le fork change.** Les fonctionnalités, l'installation et l'usage courant sont inchangés et décrits dans le [README de l'upstream](https://github.com/wtf729/cs2-WeaponPaints-steamless-website#readme).

### Fichiers ajoutés

Absents de l'upstream. Les retirer laisse le site d'origine intact.

| Fichier | Rôle |
|---|---|
| `class/inspect.php` | Encodeur et décodeur de liens d'inspection CS2 (`CEconItemPreviewDataBlock`), écrit à la main, sans dépendance |
| `class/inspect_bridge.php` | Liaison entre les tables de loadout et l'encodeur, à part pour limiter les conflits avec l'upstream |
| `actions/inspect.php` | Traitement de l'import, et le point d'entrée en lecture seule que la page interroge |
| `assets/js/inspect.js` | La fenêtre d'import, le bouton presse-papiers et le rafraîchissement du compteur |
| `Dockerfile` | Image PHP-FPM + nginx, avec les seules extensions utilisées |
| `docker-compose.yml` | Déploiement derrière Traefik, entièrement piloté par `.env` |
| `nginx.conf` | Vhost : point d'entrée PHP, chemins interdits, fichiers statiques, en-têtes de sécurité |
| `.env.example` | Modèle de tous les réglages |
| `.dockerignore`, `.gitattributes` | Contexte de build, et fins de ligne LF pour les fichiers lus sous Linux |

### Fichiers de l'upstream modifiés

| Fichier | Modification |
|---|---|
| `class/weapon_data.php` | Camp 1 (*Global*) retiré ; offsets de pendentif plus bornés à ±1 |
| `class/application.php` | *Global* retiré de la liste des camps |
| `class/translations.php` | 20 clés ajoutées, dans les deux langues |
| `class/bootstrap.php`, `actions/bootstrap.php` | Déclarent les deux nouveaux fichiers |
| `actions/skin.php` | Compteur de frags résolu une seule fois, avant les branches, pour qu'aucun chemin ne l'efface |
| `config.php` | Chaque réglage lit l'environnement en priorité ; `LOCK_STATTRAK_COUNT` ajouté |
| `index.php` | Deux valeurs transmises à la page : adresse du visionneur, point d'entrée du compteur |
| `views/layout/header.php` | `data-team` sur `<html>`, qui pilote la couleur d'accent |
| `views/layout/footer.php` | Charge le nouveau script |
| `views/components/*.php` | Bouton 3D sur chaque case, fenêtre d'import, bouton de remise à zéro, lien *Global* retiré |
| `assets/css/style.css` | Accent piloté par `--primary` / `--primary-rgb` dans les deux thèmes |
| `.gitignore` | Ignore `.env` |

### Pont de placement 3D

Placer stickers et pendentifs depuis un sélecteur plat relève de la devinette. Plutôt que de refaire un visionneur 3D, le fork relie le site à un visionneur existant par les liens d'inspection CS2.

Chaque case d'arme, de couteau et de gants porte un bouton **3D**. Il ouvre une fenêtre proposant un lien qui charge cette pièce exacte dans [skincraft.gg](https://skincraft.gg), et un champ pour recoller le lien que le visionneur produit. Un bouton *Coller depuis le presse-papiers* apparaît si le navigateur autorise la lecture du presse-papiers.

1. Clic sur **3D**, puis **Ouvrir dans SkinCraft**. L'arme arrive avec son skin, son usure, son motif, le StatTrak, le nametag, les stickers et le pendentif déjà appliqués.
2. Tout arranger dans le visionneur, puis copier son lien d'inspection.
3. Le recoller et importer. Refaire `!ws` ou réapparaître en jeu pour l'appliquer.

Rien de ce qui sort du décodeur n'atteint la base sans contrôle : l'arme doit correspondre à la case éditée, et les identifiants de peinture, de stickers et de pendentif sont recoupés avec `data/*.json`. Les peintures fusionnées sont acceptées quand la fusion est activée.

Le visionneur refuse d'être encadré, le bouton l'ouvre donc dans un onglet plutôt que de l'intégrer.

### Limite connue : le placement fin des stickers

La case *Garder le placement fin des stickers* est **décochée par défaut** et devrait le rester.

`SetStickers()`, dans le plugin WeaponPaints, écrit `sticker slot N schema = 0` dès qu'un sticker a un offset non nul. Cela détruit la position de base de l'emplacement sur le modèle : tous les stickers s'effondrent sur une origine commune et s'empilent au milieu de l'arme — cinq stickers n'en paraissent plus qu'un.

Décochée, les offsets sont abandonnés à l'import et les stickers reprennent leurs positions par défaut, correctement réparties. Les offsets de pendentif ne sont jamais concernés. À ne cocher que si le plugin apprend à les respecter.

### Pendentifs

Nécessite la colonne `weapon_keychain`, que le site lit systématiquement :

```sql
ALTER TABLE `wp_player_skins` ADD COLUMN `weapon_keychain` VARCHAR(64) NOT NULL DEFAULT '0;0;0;0;0';
```

Les offsets de pendentif sont des unités monde — un vrai lien d'inspection porte couramment des valeurs autour de 10 — ils ne sont donc pas ramenés dans un intervalle unitaire.

### Compteur StatTrak

Par défaut, chaque joueur règle son propre nombre de frags. Ce compteur est un état de jeu que le plugin entretient : un champ modifiable permet donc de s'attribuer un palmarès qu'on n'a jamais fait. `LOCK_STATTRAK_COUNT=true` le passe en lecture seule. Le contrôle est appliqué côté serveur, dans la fenêtre de réglages comme à l'import. L'interrupteur StatTrak lui-même reste modifiable dans les deux cas.

Le compteur survit à tout le reste. Changer de skin, modifier l'usure ou importer un lien ne le remet plus à zéro ; un bouton **Remettre à zéro** s'en charge volontairement, et reste disponible même quand le champ est verrouillé.

Comme le plugin continue de l'incrémenter en jeu, la page le rafraîchit toutes les 30 secondes depuis `index.php?action=stattrak_counts`, un point d'entrée en lecture seule. Un onglet masqué cesse d'interroger le serveur, et un champ en cours de saisie n'est pas touché.

### Uniquement T et CT

Les loadouts s'éditent par camp, T ou CT. L'onglet *Global*, qui écrivait aux deux à la fois, est retiré ; un ancien lien portant `team=1` arrive côté T. Les kits musicaux et les pin's n'apparaissaient que sur cet onglet — ils sont stockés par camp, ils s'affichent donc désormais des deux côtés.

Éditer le camp T passe l'interface en orange, le camp CT en bleu. Tout découle de `--primary` et `--primary-rgb`, commutées par `data-team` sur l'élément `html` : les deux thèmes suivent le camp, et une troisième palette tient en quatre lignes.

### Déploiement en conteneur

`docker-compose.yml` fait tourner le site derrière Traefik sans publier de port : seul le proxy joint nginx. Chaque valeur propre au déploiement — domaine, routeur, entrypoint, middlewares, options TLS, resolver de certificat, noms de réseaux, identifiants de base — vient de `.env`, que git ignore. Rien de personnel n'est suivi.

```bash
cp .env.example .env    # à remplir
docker compose up -d --build
```

`nginx.conf` interdit `class/`, `actions/`, `views/`, `tools/` et `config.php` : `index.php` reste le seul point d'entrée. Un health check éprouve nginx et PHP sans toucher à la base.

### Limitation des tentatives de connexion

Les échecs sont comptés par IP cliente, ce qui suppose que le conteneur voie la vraie : `nginx.conf` reconstruit l'adresse depuis `X-Forwarded-For`, à qui il ne fait confiance que depuis les plages privées, pour qu'un client ne puisse pas se forger une identité. Sans ça, chaque visiteur arrive sous l'identité du proxy et cinq erreurs d'un seul joueur bloquent la connexion pour tous.

Cinq échecs sur 30 minutes puis blocage de l'IP pendant cinq minutes, et non une seule comme chez l'upstream. Atteindre la limite remet le compteur d'échecs à zéro : c'est donc la durée du blocage qui plafonne vraiment une attaque — et les PIN de loadout n'ont aucune longueur minimale.

### Réglages

Tous optionnels sauf la base. Absent signifie que la valeur par défaut s'applique.

| Réglage | Défaut | Effet |
|---|---|---|
| `DB_HOST` `DB_PORT` `DB_NAME` `DB_USER` `DB_PASS` | — | La base utilisée par le plugin |
| `SITE_ACCESS_PASSWORD` | *(vide)* | Mot de passe demandé à l'entrée ; vide = site ouvert |
| `ADMIN_PASSWORD` | *(vide)* | Mode administrateur ; vide le désactive |
| `DEFAULT_LANGUAGE` | `en` | `en` ou `zh-CN` |
| `DEFAULT_WEB_THEME` | `dark` | `dark` ou `light` ; le visiteur peut changer |
| `SITE_NAME_EN` `SITE_NAME_ZH_CN` | intégré | Nom du site par langue |
| `ENABLE_SKIN_FUSION` | `true` | Combinaisons de peintures entre armes |
| `LOCK_STATTRAK_COUNT` | `false` | Passe le compteur de frags en lecture seule |
| `AUTH_RATE_LIMIT_ATTEMPTS` | `5` | Échecs autorisés dans la fenêtre |
| `AUTH_RATE_LIMIT_WINDOW_SECONDS` | `1800` | Fenêtre de comptage des échecs |
| `AUTH_RATE_LIMIT_LOCK_SECONDS` | `300` | Durée du blocage |
| `CONTAINER_NAME` | `cs2-skin-web` | Nomme l'image et le conteneur |
| `TZ` | `Europe/Paris` | Fuseau horaire du conteneur |

Une installation PHP classique sans Docker fonctionne toujours en éditant `config.php` : l'environnement n'est que lu en priorité, les valeurs du fichier restent le repli.

### Correctifs apportés par-dessus l'upstream

* Le compteur de frags StatTrak est lu depuis `wp_player_skins` et non depuis le cache de réglages. Il s'affichait à `0` pour tout compteur incrémenté en jeu par le plugin, et l'enregistrement gravait ce zéro en base.
* Un nametag porté par la ligne n'est plus effacé par le cache de réglages, qui ne peut désormais que le remplacer.
* Les offsets de pendentif ne sont plus ramenés dans un intervalle unitaire, ce qui collait tous les pendentifs importés au même mauvais endroit.
* Deux stickers annonçant le même emplacement ne s'écrasent plus : le second prend le premier emplacement libre, donc cinq stickers restent cinq.
* La limitation des tentatives s'indexe sur la vraie adresse du client derrière un reverse proxy, et le blocage dure cinq minutes au lieu d'une.

---

## Credits

* [wtf729/cs2-WeaponPaints-steamless-website](https://github.com/wtf729/cs2-WeaponPaints-steamless-website) — the site this forks
* [Nereziel/cs2-WeaponPaints](https://github.com/Nereziel/cs2-WeaponPaints) — the WeaponPaints plugin
* [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API) — the CS2 item data used by the updater
