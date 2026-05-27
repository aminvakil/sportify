# TODO

## Current baseline

- Docker development setup is verified. `httpd` serves `web/` static files and proxies dynamic requests to PHP.
- Runtime baseline: Symfony 7.4 LTS, PHP 8.5, Composer 2.2.x, MySQL 9.7, Apache httpd 2.4, Node 26/npm 11, and Gulp 4.
- Composer dependencies are current within the existing constraints. Current abandoned packages in `composer.lock`: none known.
- GitHub Actions CI is in place and green on `main`.
- Test coverage includes the basic tournament/user/prediction/scoring workflow, helper/repository calls, functional auth flows, and minimal frontend smoke coverage through `npm test`.

## Completed modernization

- Removed legacy Symfony/Doctrine blockers: SensioDistributionBundle, SensioFrameworkExtraBundle, `doctrine/annotations`, Doctrine cache/reflection packages, FOSUserBundle, FOSOAuthServerBundle, FOSRestBundle, NelmioApiDocBundle, Swiftmailer, and the unsupported `symfony/symfony` meta-package.
- Upgraded core backend dependencies: Doctrine Bundle/ORM/DBAL/Persistence/Event Manager, JMS Serializer/Bundle, Hateoas Bundle, Monolog Bundle, and PHPUnit Bridge.
- Replaced annotation-based routing, validation, and Doctrine ORM mappings with YAML/XML configuration.
- Replaced FOSUser/FOSOAuth behavior with app-owned Symfony Security registration/login/password-reset flows and token issuance/authentication against the existing OAuth tables.
- Replaced Swiftmailer email delivery with Symfony Mailer for registration and password reset.
- Migrated `phpunit.xml.dist` to the PHPUnit 9.6 schema.
- Symfony 7.4 deprecation re-check is clean for self and direct notices. The remaining indirect notices are from Doctrine DBAL schema events and require a future DBAL major upgrade.
- Modernized frontend tooling from Node 6/npm 3/Bower/Gulp 3/Laravel Elixir/`node-sass` to Node 26/npm 11, npm-managed dependencies, Gulp 4, and Dart Sass.
- Upgraded Docker MySQL from 5.7 to 9.7 with explicit utf8mb4 defaults. Schema validation, reserved-word checks, SQL mode, and charset/collation checks are clean.

## Deployment status

- Production Compose is separate from the dev stack via `docker-compose.prod.yml` and `docker/Dockerfile.prod`.
- Production runtime uses php-fpm + httpd. Node/npm are used only in the asset build stage and are not included in final runtime images.
- Production app configuration remains host-provided through `app/config/parameters.yml`; infrastructure settings are documented in `.env.example`.
- Production includes an idempotent `init` service that waits for the database, creates/updates schema, installs bundle assets, and clears/warms prod cache before app startup.
- First admin creation uses `sportify:user:create-admin`; regular user creation for deployments without SMTP uses `sportify:user:create`.
- Deployment documentation covers required local config files, first deployment, upgrades, scheduled commands, and smoke checks.

## App feature status

- Admin panel Data Updates shows the no-updates flash message without the removed `session` service.
- Submitted predictions can be sent to the configured Telegram chat shortly after kickoff with `sportify:telegram:send-predictions`.
- Data update Telegram notifications use the app-owned Telegram service/config instead of legacy hardcoded send/pin URLs. Sent messages are pinned by default and can be disabled with `telegram.pin_messages: false`.

## Next steps

No required backend, frontend, or deployment infrastructure modernization is currently pending.

Optional product work:

### Probability-weighted scoring

Users should still predict only scores. The predicted score determines the predicted outcome: home win, draw, or away win. Do not add a goal-difference or close-score bonus.

Each match needs a stored stage for scoring. Do not infer stage from date, because knockout schedules and imported fixture dates can change.

Base scoring by stage:

| Stage | Correct outcome | Exact score |
| --- | ---: | ---: |
| Group | 2 | 5 |
| Round of 32 / 1⁄16 | 3 | 7 |
| Round of 16 / 1⁄8 | 4 | 8 |
| Quarter-final / 1⁄4 | 5 | 10 |
| Semi-final | 5 | 12 |
| Final | 5 | 12 |

Probability bonus rules:

- Store a normalized betting-probability snapshot once when each match is added: home win, draw, away win, and source. Do not update it later and do not store an updated-time field.
- Prefer exact integer storage for probabilities, for example basis points where `10000 = 100%`, to avoid floating-point scoring/rounding surprises.
- Make the source specific enough to audit later, for example provider/bookmaker/market, not only the provider name.
- Apply the probability bonus only when the predicted outcome is correct.
- Add the probability bonus on top of the normal stage score; do not replace the normal score.
- Cap the probability bonus at the exact-score value for that match stage.
- Suggested probability bonus scale:
  - 50% or more: +0
  - 33%–49.99%: +25% of stage exact score
  - 20%–32.99%: +50% of stage exact score
  - 10%–19.99%: +75% of stage exact score
  - Less than 10%: +100% of stage exact score
- Round calculated bonus values to integer points.

Total scoring:

- Wrong outcome: 0 points.
- Correct outcome only: `stage outcome score + probability bonus`.
- Exact score: `stage exact score + probability bonus`.

Example: in a quarter-final, the base scores are outcome 5 and exact 10. If a user predicts the exact score for a less-than-10% probable winner, they should receive `10 + 10 = 20` points. If they predict only the correct outcome for the same match, they should receive `5 + 10 = 15` points.

### Prediction page changes

- Show the betting-probability snapshot on each prediction card: home win, draw, and away win percentages.
- Show the points available for each possible outcome on the match card, for example correct home win / draw / away win totals and exact-score totals after the probability bonus is applied.
- If probabilities are missing, show that no probability bonus is available instead of hiding the scoring rule.

### Telegram fixture-added message

- When fixture updates add new matches, include the added match list in the notification.
- Print each added match with its stored betting-probability snapshot.

### Telegram prediction message after kickoff

- Keep sending submitted predictions shortly after the match starts.
- Include the betting-probability snapshot in this pre-result message.
- Include each user's predicted score and derived predicted outcome.

### Telegram result/scoring message after full time

- When match results are updated and scores are calculated, send a Telegram result/scoring message.
- Include the final result, betting-probability snapshot, each user's prediction, whether it was wrong outcome/correct outcome/exact score, and how the final points were calculated.
- Include standings changes as the existing data update message does today.

### Implementation notes

- Exact-prediction percentage should not depend on a fixed point value once exact scores become variable by stage and probability bonus. Store a scoring result such as wrong/outcome/exact on each prediction when it is scored.
- Store scoring breakdown fields on each scored prediction, such as base points and probability bonus, so Telegram/result history can explain historical calculations even if scoring constants change later.
- Existing `api_mappings` can map matches/teams/tournaments to provider IDs; use it for the odds provider if the provider exposes stable IDs. If not, document and test the team/date matching strategy.
- Add tests for probability bonus boundaries, stage base scores, exact score handling, wrong-outcome zero points, stored scoring breakdown, exact-prediction percentage, prediction-page display data, fixture-added notification content, and both Telegram match prediction/result message formats.

### Suggested PR sequence

1. Research and choose a betting-probability source.
   - Only consider providers with a usable free tier.
   - Criteria: football/soccer odds coverage, supported competitions, pre-kickoff odds availability, API stability, terms that allow storing/displaying derived probabilities, rate limits, and reliable matching to existing fixtures/teams.
   - Likely candidates: The Odds API and API-Football odds. Avoid direct bookmaker scraping unless no API source works.
   - Deliverable: document the selected provider, sample response, rate limits, required config/env vars, normalization rule, source/bookmaker/market choice, and matching strategy.
2. Add the data model for match probability snapshots, match stage, and prediction scoring breakdown.
   - Add nullable match fields for stage, home/draw/away probabilities, and source. Existing matches must remain valid.
   - Add nullable prediction fields for scoring result, base points, and probability bonus.
   - Add admin/import handling for match stage; default only when the stage is genuinely known.
   - Use probability bonus `0` when probabilities are missing.
3. Extract scoring into a dedicated service while preserving current behavior.
   - Keep this PR behavior-equivalent to reduce risk before changing the scoring rules.
   - Return a structured scoring result, not just an integer, so later PRs can persist/explain the calculation.
4. Update fixture import status to expose added match details.
   - Current fixture imports report counts only. Add enough returned data for later fixture-added Telegram messages without re-querying broad match lists.
5. Implement the new stage/probability scoring rules.
   - Add stage base scores, probability bonus calculation, bonus cap by stage exact score, wrong-outcome zero points, and wrong/outcome/exact classification.
   - Persist the scoring breakdown on predictions.
   - Fix exact-prediction percentage so it uses scoring result, not a fixed exact-score point value.
6. Fetch and store probabilities when fixtures are added.
   - Store the snapshot once for newly added matches only.
   - Normalize bookmaker odds into probabilities if the provider returns odds instead of direct percentages.
   - Add matches even when probabilities are unavailable.
7. Show probabilities and scoring information on the predictions page.
8. Include added matches and probabilities in the fixture-added Telegram notification.
9. Include probabilities and derived outcomes in the after-kickoff Telegram prediction message.
10. Include final result, per-user scoring calculations, and standings changes in the after-full-time Telegram result/scoring message.

Deferred infrastructure work:

- Defer structural backend modernization until a framework step requires it.
- Prefer compatibility shims and focused route/config changes over broad rewrites unless a milestone explicitly calls for a replacement.

## PR sizing strategy

Use bigger PRs, but keep them coherent:

- Good larger PR: "remove a Doctrine blocker with mapping coverage" or "stabilize Symfony 7.4 by fixing one coherent group of deprecations/config breaks".
- Bad larger PR: mixing Symfony upgrades, frontend build modernization, broad directory layout changes, and unrelated cleanup.
- Every large PR should include or expand tests for the flows it changes.
- Prefer one PR per milestone, not one PR per deprecation notice or one PR per small config line.
- If a milestone uncovers unrelated work, note it in TODO instead of expanding scope indefinitely.

## Verification

See the "Verification rule" section of `AGENTS.md`. CI also replaces `football_api.token` with the `FOOTBALL_DATA_API_TOKEN` secret before running.
