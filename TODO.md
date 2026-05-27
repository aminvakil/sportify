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

Keep the first version simple. Users still predict only scores. The predicted score determines the predicted outcome: home win, draw, or away win. Do not add a goal-difference or close-score bonus.

Do not model competition type or match stage for v1. Instead, keep configurable default base scoring values and snapshot those values onto each match when it is added. When the admin knows that upcoming matches should be worth more, they change the defaults from that point onward; existing matches keep their stored values.

Initial default base scoring for newly added matches:

| Result | Points |
| --- | ---: |
| Correct outcome | 2 |
| Exact score | 5 |

Possible default values the admin may switch to later:

| Importance from now on | Correct outcome | Exact score |
| --- | ---: | ---: |
| Default | 2 | 5 |
| Medium | 3 | 7 |
| High | 5 | 10 |
| Highest | 5 | 12 |

Probability bonus rules:

- A cron/command should look ahead a configurable number of days, for example 7 or 14 days.
- It should add upcoming matches that are not already in the database, snapshotting the current default base scoring values onto each new match.
- It should set normalized betting probabilities only when adding a new match: home win, draw, away win, and source.
- If the provider returns bookmaker odds, normalize implied probabilities before storing them so the three outcomes total about 100%.
- Do not update probabilities after a match has been created. The initially stored probabilities are the scoring snapshot.
- Store probabilities as exact integers, preferably basis points where `10000 = 100%`, to avoid floating-point scoring/rounding surprises.
- Keep the source simple but auditable, for example provider/bookmaker/market when available.
- Apply the probability bonus only when the predicted outcome is correct.
- Add the probability bonus on top of the normal score; do not replace the normal score.
- Cap the probability bonus at the match's exact-score value.
- Probability bonus should be an integer from `0` through the match's exact-score value, so every value up to the cap is possible.
- Suggested formula, where `p` is the predicted outcome probability as a percentage and `cap` is the match's exact-score value:
  - If `p >= 50`, bonus is `0`.
  - If `p < 50`, bonus is `ceil((50 - p) * cap / 50)`, capped to `cap`.
  - With basis-point storage, use the equivalent integer formula: if `p_bps < 5000`, bonus is `ceil((5000 - p_bps) * cap / 5000)`.
- Example with cap `10`: probabilities near 50% give +1, around 40% gives +2, around 25% gives +5, around 10% gives +8, and very low probabilities can reach +10.
- If probabilities are missing, the probability bonus is `0`.

Total scoring:

- Wrong outcome: 0 points.
- Correct outcome only: `outcome score + probability bonus`.
- Exact score: `exact score + probability bonus`.

Example with outcome 2 and exact 5: if a user predicts the exact score for a 10% probable winner, the probability bonus is `ceil((50 - 10) * 5 / 50) = 4`, so they receive `5 + 4 = 9` points. If they predict only the correct outcome for the same match, they receive `2 + 4 = 6` points.

### Prediction page changes

- Show the betting-probability snapshot on each prediction card: home win, draw, and away win percentages.
- Show the points available for each possible outcome on the match card, for example correct home win / draw / away win totals and exact-score totals after the probability bonus is applied.
- If probabilities are missing, show that no probability bonus is available instead of hiding the scoring rule.

### Telegram fixture-added message

- When the upcoming-match cron/command adds new matches, include the added match list in the Telegram notification.
- Print each added match with its stored betting probabilities.

### Telegram prediction message after kickoff

- Keep sending submitted predictions shortly after the match starts.
- Include the betting-probability snapshot in this pre-result message.
- Include each user's predicted score and derived predicted outcome.

### Telegram result/scoring message after full time

- When match results are updated and scores are calculated, send a Telegram result/scoring message.
- Include the final result, betting-probability snapshot, each user's prediction, whether it was wrong outcome/correct outcome/exact score, and how the final points were calculated.
- Include standings changes as the existing data update message does today.

### Implementation notes

- Exact-prediction percentage should not depend on a fixed point value once probability bonus exists. Store a scoring result such as wrong/outcome/exact on each prediction when it is scored.
- Store scoring breakdown fields on each scored prediction, such as base points, probability bonus, and total points, so Telegram/result history can explain historical calculations even if defaults or match base points change later.
- Existing `api_mappings` can map matches/teams/tournaments to provider IDs; use it for the odds provider if the provider exposes stable IDs. If not, document and test the team/date matching strategy.
- Do not add match stage fields for v1. Per-stage scoring is represented by admin-controlled default base points that are snapshotted onto newly added matches.
- Add tests for probability bonus boundaries, base scores, exact score handling, wrong-outcome zero points, stored scoring breakdown, exact-prediction percentage, prediction-page display data, fixture-added notification content, and both Telegram match prediction/result message formats.

### Suggested PR sequence

Use fewer, milestone-sized PRs for this feature:

1. Research and choose a betting-probability source.
   - Only consider providers with a usable free tier.
   - Criteria: upcoming football/soccer fixtures and odds coverage, pre-kickoff odds availability, API stability, terms that allow storing/displaying derived probabilities, rate limits, and reliable matching to existing fixtures/teams.
   - Likely candidates: The Odds API and API-Football odds. Avoid direct bookmaker scraping unless no API source works.
   - Deliverable: document the selected provider, sample response, rate limits, required config/env vars, normalization rule, source/bookmaker/market choice, whether fixtures and odds come from the same provider or separate providers, and matching strategy.
2. Add probability/scoring persistence and scoring engine.
   - Add nullable match fields for home/draw/away probabilities and source. Existing matches must remain valid.
   - Add match fields for base outcome points and base exact points, populated from the current defaults when each match is created.
   - Add admin support for changing the current default outcome/exact base points used for future imported matches; existing matches should not be changed by default changes.
   - Add nullable prediction fields for scoring result, base points, probability bonus, and total points if needed for a stable scoring breakdown.
   - Extract scoring into a dedicated service that returns a structured scoring result, not just an integer.
   - Implement match-base-score/probability-bonus scoring: stored match base scores, bonus cap by the match's exact-score value, wrong-outcome zero points, and wrong/outcome/exact classification.
   - Persist the scoring breakdown on predictions.
   - Fix exact-prediction percentage so it uses scoring result, not a fixed exact-score point value.
   - Include scoring unit/integration tests in this PR.
3. Add upcoming-match/probability import and fixture-added Telegram notification.
   - Add/update the cron/command to look ahead a configurable number of days, for example 7 or 14 days.
   - Add missing upcoming matches with the current default base scoring values snapshotted onto each new match.
   - Set probabilities only when creating newly added matches; never refresh probabilities for existing matches.
   - Add matches even when probabilities are unavailable.
   - Return added match details for notifications.
   - Send the Telegram fixture-added message with added matches and stored probabilities.
   - Include tests for import matching, missing-probability behavior, and fixture-added notification content.
4. Add probability/scoring visibility across user-facing surfaces.
   - Show probabilities and scoring information on the predictions page.
   - Include probabilities and derived outcomes in the after-kickoff Telegram prediction message.
   - Include final result, per-user scoring calculations, and standings changes in the after-full-time Telegram result/scoring message.
   - Include tests for prediction-page display data and both Telegram match prediction/result message formats.

Deferred infrastructure work:

- Defer structural backend modernization until a framework step requires it.
- Prefer compatibility shims and focused route/config changes over broad rewrites unless a milestone explicitly calls for a replacement.

## PR sizing strategy

Use bigger PRs, but keep them coherent. Group related TODO implementation tasks into one PR when they belong to the same milestone; do not create many tiny PRs for tightly coupled pieces:

- Good larger PR: "remove a Doctrine blocker with mapping coverage" or "stabilize Symfony 7.4 by fixing one coherent group of deprecations/config breaks".
- Bad larger PR: mixing Symfony upgrades, frontend build modernization, broad directory layout changes, and unrelated cleanup.
- Every large PR should include or expand tests for the flows it changes.
- Prefer one PR per milestone, not one PR per deprecation notice or one PR per small config line.
- If a milestone uncovers unrelated work, note it in TODO instead of expanding scope indefinitely.

## Verification

See the "Verification rule" section of `AGENTS.md`. CI also replaces `football_api.token` with the `FOOTBALL_DATA_API_TOKEN` secret before running.
