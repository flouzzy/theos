# AGENTS.md - Règles de Conduite Académie Le Rocher

Ce document définit les protocoles opérationnels que tout Agent (Jules, Gemini, etc.) doit suivre scrupuleusement.

## 👑 Principes Fondateurs
1. **Source de Vérité:** `GEMINI.md` pour l'architecture Docker actuelle.
2. **Priorité Sécurité:** Ne jamais exposer de ports conflictuels (6379, 3306) sur l'hôte sans vérification.
3. **Planification:** Toujours proposer un plan avant de modifier le `Makefile` ou le `compose.yaml`.

## 🛠 Workflow Technique Obligatoire

### Modification du Code PHP
1. Vérifier si le container est "Healthy" via `docker ps`.
2. Appliquer le changement.
3. Vérifier la syntaxe PHP (`php -l`) pour éviter de bloquer le redémarrage (l'entrypoint échouerait).
4. Vider le cache via `make cc`.

### Règles de Code PHP Sensibles
- **Imports:** Préférer systématiquement l'utilisation des déclarations `use` en haut de fichier plutôt que de type-hinter avec des FQCN (ex: `SiteSettingRepository` plutôt que `\App\Repository\SiteSettingRepository`).

### Gestion des Base de Données
- Ne JAMAIS supprimer les données du volume `database_data` sans confirmation explicite.
- Toujours utiliser le port **3307** pour les connexions externes (DBeaver, Sequel Ace, etc.).

### Mise à jour des Dépendances
- Utiliser `make vendor` pour installer les nouveaux packages via Docker.
- Ne jamais modifier manuellement `composer.lock` à l'extérieur du container pour éviter les problèmes de plateforme.

## 📋 Gestion des Tâches
- Toute correction technique majeure doit être consignée dans `tasks/lessons.md`.
- Les tâches en attente doivent être ajoutées à `tasks/todo.md`.

## 🧪 Validation de Fin de Session
Avant de quitter, l'agent doit :
1. Vérifier que `curl -I http://localhost:8095` ne retourne pas une boucle de redirection (308 infini).
2. S'assurer que les logs Docker ne contiennent pas d'erreurs critiques (`docker compose logs -f php --tail 50`).
3. Confirmer que la base de données Docker est accessible via `database:3306` depuis le container PHP.

<!-- gitnexus:start -->
# GitNexus — Code Intelligence

This project is indexed by GitNexus as **academie** (2903 symbols, 6820 relationships, 220 execution flows). Use the GitNexus MCP tools to understand code, assess impact, and navigate safely.

> If any GitNexus tool warns the index is stale, run `npx gitnexus analyze` in terminal first.

## Always Do

- **MUST run impact analysis before editing any symbol.** Before modifying a function, class, or method, run `gitnexus_impact({target: "symbolName", direction: "upstream"})` and report the blast radius (direct callers, affected processes, risk level) to the user.
- **MUST run `gitnexus_detect_changes()` before committing** to verify your changes only affect expected symbols and execution flows.
- **MUST warn the user** if impact analysis returns HIGH or CRITICAL risk before proceeding with edits.
- When exploring unfamiliar code, use `gitnexus_query({query: "concept"})` to find execution flows instead of grepping. It returns process-grouped results ranked by relevance.
- When you need full context on a specific symbol — callers, callees, which execution flows it participates in — use `gitnexus_context({name: "symbolName"})`.

## When Debugging

1. `gitnexus_query({query: "<error or symptom>"})` — find execution flows related to the issue
2. `gitnexus_context({name: "<suspect function>"})` — see all callers, callees, and process participation
3. `READ gitnexus://repo/academie/process/{processName}` — trace the full execution flow step by step
4. For regressions: `gitnexus_detect_changes({scope: "compare", base_ref: "main"})` — see what your branch changed

## When Refactoring

- **Renaming**: MUST use `gitnexus_rename({symbol_name: "old", new_name: "new", dry_run: true})` first. Review the preview — graph edits are safe, text_search edits need manual review. Then run with `dry_run: false`.
- **Extracting/Splitting**: MUST run `gitnexus_context({name: "target"})` to see all incoming/outgoing refs, then `gitnexus_impact({target: "target", direction: "upstream"})` to find all external callers before moving code.
- After any refactor: run `gitnexus_detect_changes({scope: "all"})` to verify only expected files changed.

## Never Do

- NEVER edit a function, class, or method without first running `gitnexus_impact` on it.
- NEVER ignore HIGH or CRITICAL risk warnings from impact analysis.
- NEVER rename symbols with find-and-replace — use `gitnexus_rename` which understands the call graph.
- NEVER commit changes without running `gitnexus_detect_changes()` to check affected scope.

## Tools Quick Reference

| Tool | When to use | Command |
|------|-------------|---------|
| `query` | Find code by concept | `gitnexus_query({query: "auth validation"})` |
| `context` | 360-degree view of one symbol | `gitnexus_context({name: "validateUser"})` |
| `impact` | Blast radius before editing | `gitnexus_impact({target: "X", direction: "upstream"})` |
| `detect_changes` | Pre-commit scope check | `gitnexus_detect_changes({scope: "staged"})` |
| `rename` | Safe multi-file rename | `gitnexus_rename({symbol_name: "old", new_name: "new", dry_run: true})` |
| `cypher` | Custom graph queries | `gitnexus_cypher({query: "MATCH ..."})` |

## Impact Risk Levels

| Depth | Meaning | Action |
|-------|---------|--------|
| d=1 | WILL BREAK — direct callers/importers | MUST update these |
| d=2 | LIKELY AFFECTED — indirect deps | Should test |
| d=3 | MAY NEED TESTING — transitive | Test if critical path |

## Resources

| Resource | Use for |
|----------|---------|
| `gitnexus://repo/academie/context` | Codebase overview, check index freshness |
| `gitnexus://repo/academie/clusters` | All functional areas |
| `gitnexus://repo/academie/processes` | All execution flows |
| `gitnexus://repo/academie/process/{name}` | Step-by-step execution trace |

## Self-Check Before Finishing

Before completing any code modification task, verify:
1. `gitnexus_impact` was run for all modified symbols
2. No HIGH/CRITICAL risk warnings were ignored
3. `gitnexus_detect_changes()` confirms changes match expected scope
4. All d=1 (WILL BREAK) dependents were updated

## CLI

- Re-index: `npx gitnexus analyze`
- Check freshness: `npx gitnexus status`
- Generate docs: `npx gitnexus wiki`

<!-- gitnexus:end -->
