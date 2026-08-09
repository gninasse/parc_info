# Mise en service du module Achat — prérequis d'exploitation

> **À qui s'adresse ce document** : à la personne qui installe le module sur le serveur de l'établissement. Il ne décrit pas l'usage (voir `GUIDE_Utilisateur_Achat.md`) mais **ce qui doit être en place pour que le module fonctionne réellement**.
>
> Motif d'existence : plusieurs fonctions livrées **ne s'exécutent pas d'elles-mêmes**. Elles sont correctes, testées, et resteront pourtant inertes tant que les points ci-dessous ne sont pas faits. Une fonction qui ne se déclenche jamais est plus dangereuse qu'une fonction absente : on croit être averti, on ne l'est pas.

---

## 1. Les points à traiter avant l'ouverture aux utilisateurs

### 1.1 Le planificateur doit être branché au cron — sinon le résumé du lundi ne part jamais

`php artisan schedule:list` montre bien la tâche :

```
30 7 * * 1  php artisan achat:resume-reliquats
```

Mais Laravel n'exécute son planificateur que si **le système** l'appelle chaque minute. Vérification faite au 09/08/2026 sur le poste de développement : **aucune entrée `schedule:run` au crontab**. En l'état, le résumé hebdomadaire des bons non livrés (D-22) ne partira **jamais**, sans qu'aucune erreur ne le signale.

À poser sur le serveur, avec l'utilisateur qui fait tourner l'application :

```cron
* * * * * cd /chemin/vers/parc_info && php artisan schedule:run >> /dev/null 2>&1
```

**Contrôle après pose** : `php artisan achat:resume-reliquats` à la main doit afficher le nombre de destinataires. Puis, le lundi suivant, vérifier qu'une notification est arrivée.

### 1.2 La messagerie doit être configurée — sinon aucun courriel ne sort

État constaté : `MAIL_MAILER=log`, `MAIL_FROM_ADDRESS="hello@example.com"`, `MAIL_FROM_NAME="${APP_NAME}"` avec `APP_NAME=Laravel`.

Trois conséquences, dans l'ordre de gravité :

1. **`log`** signifie que les courriels sont écrits dans `storage/logs/laravel.log` et **ne partent pas**. C'est le bon réglage en développement ; en production, il faut un vrai transport SMTP.
2. **`hello@example.com`** est une adresse de démonstration. Beaucoup de serveurs de messagerie refusent ou classent en indésirable un message dont l'expéditeur n'appartient pas au domaine.
3. **`APP_NAME=Laravel`** fait que les courriels s'annoncent au nom de « Laravel ». C'est le même défaut que l'en-tête « Laravel » trouvé sur le premier bordereau PDF généré : un document officiel de l'établissement ne porte pas le nom du cadre technique qui l'a produit. La page de connexion affiche d'ailleurs encore « Connexion | Laravel ».

À corriger dans `.env` :

```dotenv
APP_NAME="CHU-YO"
MAIL_MAILER=smtp
MAIL_HOST=<serveur de messagerie de l'établissement>
MAIL_PORT=587
MAIL_USERNAME=<compte applicatif>
MAIL_PASSWORD=<mot de passe>
MAIL_FROM_ADDRESS="parc-info@chu-yo.bf"   # adresse du domaine de l'établissement
MAIL_FROM_NAME="${APP_NAME}"
```

Puis `php artisan config:clear`.

**Le module reste utilisable sans messagerie** : les notifications s'affichent dans la cloche, seul le canal courriel est muet. Ce n'est donc pas bloquant pour la mise en service, mais les utilisateurs qui n'ont coché que « par courriel » ne recevraient rien.

### 1.3 Envoi synchrone : aucun worker n'est nécessaire, mais l'envoi est dans la requête

`QUEUE_CONNECTION=database` est configuré, ce qui pourrait laisser croire que les notifications passent par la file. **Ce n'est pas le cas** : la classe `NotificationAchat` utilise le trait `Queueable` mais **n'implémente pas `ShouldQueue`**. Vérifié par l'observation : après un envoi réel, la table `jobs` reste à 0 et le courriel est rendu dans le même processus.

Conséquences, à connaître avant la mise en service :

- **aucun worker à installer ni à superviser.** C'est une simplification d'exploitation appréciable pour l'établissement ;
- **en contrepartie, l'envoi se fait pendant la requête.** Si le serveur SMTP répond lentement, l'utilisateur qui soumet un bon attend. Avec un SMTP local ou proche, cela reste négligeable ; avec un relais distant lent, cela se verrait.

**Ce risque est borné** : les déclencheurs sont placés hors transaction et enveloppés d'un `try/catch` qui journalise (`NotificationsAchatTest::test_un_echec_de_notification_ne_defait_pas_la_soumission`). Un SMTP en panne ralentit ou perd un courriel, il **ne défait jamais une commande**.

Si l'attente devenait perceptible en production, le remède tient en une ligne : faire implémenter `ShouldQueue` à `NotificationAchat` et lancer un worker (`php artisan queue:work`) sous supervision. À ne faire **que** si le besoin est constaté, pas par précaution : un worker est un processus de plus à surveiller, et un worker arrêté sans qu'on s'en aperçoive retarde silencieusement tous les envois.

---

## 2. Vérification après installation

Dans l'ordre, sur le serveur :

```bash
php artisan migrate --force          # dont les tables de notification
php artisan cores:sync-permissions achat
php artisan config:cache && php artisan view:cache
php artisan schedule:list            # la tâche du lundi doit apparaître
php artisan achat:indicateurs        # les 3 indicateurs du jalon (D-20)
php Modules/Achat/tests/Recette/verif_d22.php   # notification réelle, transaction annulée
```

Le dernier script est le plus parlant : il déclenche une vraie notification sur un vrai bon, affiche ce qui arrive en base et si le courriel a été rendu, **puis annule tout**. Il ne laisse aucune trace et peut être relancé autant de fois que voulu.

---

## 3. Ce qui reste à faire faire par l'établissement

| Sujet | Pourquoi cela ne peut pas être fait ici |
|---|---|
| **Adresses de courriel des utilisateurs** | Une notification par courriel n'a de destinataire que si le compte porte une adresse. Le canal est ignoré pour les comptes sans adresse (choix délibéré : un compte technique ne doit pas faire échouer un envoi). |
| **Attribution des permissions et des rôles** | Les 3 rôles sont seedés, mais qui vise et qui achète relève de l'organisation, pas du code. La séparation commande/visa (RGC-11) n'a d'effet que si deux personnes différentes les détiennent. |
| **Paramètres métier** (seuils, délais, bornes d'intérim) | Écran `Achat → Administration`. Les valeurs livrées sont des valeurs par défaut prudentes, pas des décisions de l'établissement. |
| **Sauvegarde de la base** | La procédure de test PostgreSQL (`docs/retours-experience/RETEX_Module_Stock.md` §5) suppose un `pg_dump` préalable. En production, la sauvegarde relève de l'exploitation. |

---

*Document d'exploitation du module Achat — CHU-YO, 09/08/2026. Les constats d'état (cron absent, `MAIL_MAILER=log`, aucun worker, `APP_NAME=Laravel`) ont été relevés sur l'environnement de développement et sont à revérifier sur le serveur cible.*
