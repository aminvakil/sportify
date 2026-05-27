# Betting probability source research

## Decision

Use **The Odds API v4** as the first betting-probability source.

The first implementation should continue using `football-data.org` for results/status updates unless a later PR explicitly replaces that flow. Use The Odds API for upcoming fixture discovery and pre-kickoff head-to-head odds snapshots. Imported upcoming matches must have a complete odds snapshot; if The Odds API cannot be reached or does not return all three home/draw/away outcomes, do not create the match. When a match is created from The Odds API, store the provider event id if the local mapping can support its string id; otherwise store enough audited source text on the match and use team/date matching until `api_mappings.api_object_id` is widened from integer to string.

## Why this provider

- Has a documented free starter plan: **500 credits per month**.
- Soccer coverage includes major leagues and UEFA competitions.
- The `/v4/sports` and `/v4/sports/{sport}/events` endpoints do not count against quota.
- The main `/v4/sports/{sport}/odds` endpoint returns upcoming/live events with bookmaker odds in one response.
- For the needed market, quota cost is predictable: `1 market * 1 region = 1 credit` per competition request.
- Terms allow use in websites, mobile apps, dashboards, analytical tools, and other user-facing applications, provided the data is not resold or redistributed as a standalone data product.

## Provider comparison

| Provider | Free tier | Fit | Decision |
| --- | --- | --- | --- |
| The Odds API v4 | 500 credits/month | Good football/soccer odds coverage, clear quota model, clear user-facing-app terms, simple h2h market | Selected |
| API-Football / API-Sports odds | Free access exists through API-Sports/RapidAPI, but local research could not verify current odds terms through Cloudflare-protected docs | Has fixtures and odds under one football-specific API, but terms and current free-tier odds limits need manual verification | Do not use for v1 |

## API shape to use

Base URL:

```text
https://api.the-odds-api.com
```

Configuration needed later:

```yaml
odds_api.name: the_odds_api
odds_api.base_uri: https://api.the-odds-api.com
odds_api.key: '%env(ODDS_API_KEY)%'
odds_api.regions: eu
odds_api.markets: h2h
odds_api.odds_format: decimal
odds_api.lookahead_days: 14
odds_api.bookmaker_preference: ['pinnacle', 'betfair_ex_eu', 'betfair', 'unibet_eu', 'unibet']
```

Use competition sport keys configured per local tournament, for example `soccer_epl`, `soccer_uefa_champs_league`, `soccer_spain_la_liga`, etc. Do not use the special `upcoming` sport key for the cron because its time filters do not apply and it only returns the next eight events across sports.

Useful endpoints:

```text
GET /v4/sports/?apiKey={apiKey}
GET /v4/sports/{sport}/events?apiKey={apiKey}&commenceTimeFrom={from}&commenceTimeTo={to}
GET /v4/sports/{sport}/odds/?apiKey={apiKey}&regions=eu&markets=h2h&oddsFormat=decimal&commenceTimeFrom={from}&commenceTimeTo={to}
```

The `/events` endpoint is useful for fixture discovery without spending quota. The `/odds` endpoint should be called only for the selected lookahead window and configured competitions.

## Sample response shape

The `/odds` response contains one object per event:

```json
{
  "id": "bda33adca828c09dc3cac3a856aef176",
  "sport_key": "soccer_epl",
  "commence_time": "2026-08-15T14:00:00Z",
  "home_team": "Example Home",
  "away_team": "Example Away",
  "bookmakers": [
    {
      "key": "pinnacle",
      "title": "Pinnacle",
      "last_update": "2026-08-10T12:00:00Z",
      "markets": [
        {
          "key": "h2h",
          "outcomes": [
            { "name": "Example Home", "price": 1.80 },
            { "name": "Draw", "price": 3.60 },
            { "name": "Example Away", "price": 4.50 }
          ]
        }
      ]
    }
  ]
}
```

For soccer, the `h2h` market has three outcomes: home win, draw, and away win. Pick one bookmaker deterministically from `odds_api.bookmaker_preference`; if none are present, use the first bookmaker returned that has all three `h2h` outcomes. Store the source as:

```text
the_odds_api:{sport_key}:{event_id}:{bookmaker_key}:h2h
```

## Normalization rule

Use decimal odds. Convert bookmaker odds to implied probabilities and remove the bookmaker overround before storing basis points.

For decimal prices `home`, `draw`, and `away`:

```text
raw_home = 1 / home
raw_draw = 1 / draw
raw_away = 1 / away
raw_total = raw_home + raw_draw + raw_away
home_bps = round(raw_home / raw_total * 10000)
draw_bps = round(raw_draw / raw_total * 10000)
away_bps = 10000 - home_bps - draw_bps
```

Use the away value as the remainder so the stored values always total exactly `10000`. If any of the three h2h outcomes is missing, skip the event and do not create a local match.

## Matching strategy

1. Map each local tournament to a The Odds API `sport_key` in `api_mappings` or a new dedicated config/mapping table.
2. For newly imported events, create a local match when no existing match matches the same tournament, home team, away team, and kickoff time within a small tolerance, for example 10 minutes.
3. Prefer stable provider event ids for dedupe once `api_mappings.api_object_id` can store The Odds API string ids.
4. Match teams by an explicit team mapping when present. Otherwise normalize names for comparison: lowercase, remove punctuation, collapse whitespace, and keep a small alias list for known provider/local naming differences.
5. If a tournament/team cannot be matched safely, skip that event and report it in command output rather than guessing.

## Implementation notes for later PRs

- Add `ODDS_API_KEY` to deployment documentation and local parameter setup; do not commit real keys.
- Store probabilities only when creating a match. Do not refresh probabilities for existing matches.
- Store probabilities as integer basis points.
- Do not add imported matches when odds are missing or The Odds API is unreachable; every newly imported match must have a stored probability snapshot.
- Track response headers `x-requests-remaining`, `x-requests-used`, and `x-requests-last` in debug logs or command output summaries.
- The Odds API terms mention responsible-gambling messaging when the service is used to promote bookmakers or gambling services. Sportify uses derived probabilities for a private prediction game, not bookmaker promotion, but the user-facing copy should avoid betting calls to action.

## Sources checked

- The Odds API v4 docs: `https://the-odds-api.com/liveapi/guides/v4/`
- The Odds API pricing/home page: `https://the-odds-api.com/`
- The Odds API terms: `https://the-odds-api.com/terms-and-conditions.html`
- API-Football docs and API-Sports pages were attempted, but the public pages returned Cloudflare challenge pages from this environment.
