# RankSentinel

**Le bilan SEO express de votre site WordPress.** Extension gratuite et open source qui vérifie en quelques secondes que les moteurs de recherche peuvent trouver et indexer votre site.

*A free, open-source WordPress plugin for quick SEO health checks. Interface in English and French.*

## Ce que vérifie RankSentinel

| Vérification | Ce qui est contrôlé |
| --- | --- |
| Visibilité pour les moteurs de recherche | Le réglage WordPress « Demander aux moteurs de recherche de ne pas indexer ce site ». |
| Réponse de la page d’accueil | Le code HTTP renvoyé par la page d’accueil (200, redirection, erreur). |
| Indexation de la page d’accueil | Les directives `noindex` dans les balises meta robots et l’en-tête `X-Robots-Tag`. |
| Plan de site XML | La disponibilité du plan de site de WordPress à l’adresse `/wp-sitemap.xml`. |

Chaque analyse donne un **score sur 100**, le résultat de chaque vérification et, en cas de problème, **un conseil concret** pour le corriger. La dernière analyse reste affichée dans **Outils → RankSentinel**.

Aucun compte, aucune clé d’API, aucun suivi : rien n’est envoyé à un service extérieur.

## Installation

1. Téléchargez ce dépôt au format ZIP (bouton **Code → Download ZIP**).
2. Dans WordPress, allez dans **Extensions → Ajouter → Téléverser une extension** et choisissez le ZIP.
3. Activez l’extension.
4. Ouvrez **Outils → RankSentinel** et lancez l’analyse.

Configuration requise : WordPress 6.5 ou plus récent, PHP 7.4 ou plus récent.

## Limites

- C’est une première version : ce n’est ni un robot d’exploration complet ni un service de surveillance.
- La détection de `noindex` couvre les formats courants, pas toutes les variantes HTML possibles.
- Le plan de site contrôlé est celui de WordPress. Avec Yoast, Rank Math ou SEOPress, il se trouve souvent à `/sitemap_index.xml`.
- Certains hébergeurs bloquent les requêtes d’un site vers lui-même.
- Les analyses sont manuelles, sans alerte ni planification pour l’instant.

## Feuille de route

- Prendre en charge les plans de site des extensions SEO courantes.
- Vérifier une sélection d’URL publiques et signaler les erreurs 404.
- Comparer les titres et les H1 d’une analyse à l’autre.
- Analyses planifiées et alertes par e-mail.
- Export des résultats en JSON ou CSV.

## Traductions

L’interface est écrite en anglais et traduite en français (`languages/`). Les contributions pour d’autres langues sont les bienvenues.

## Contribuer

Les tickets et les demandes de fusion sont les bienvenus. Voir [CONTRIBUTING.md](CONTRIBUTING.md).

## Journal des modifications

### 0.2.0
- Nouvelle interface : score sur 100, cartes de résultats colorées et conseils pour corriger chaque problème.
- Traduction française complète.
- Détection de `noindex` étendue aux balises `googlebot`.
- Nouvel essai en GET quand le serveur refuse les requêtes HEAD pour le plan de site.
- Lien « Lancer une analyse » dans la liste des extensions.

### 0.1.0
- Première version.

## Licence

GPL-2.0-or-later. Voir [LICENSE](LICENSE).
