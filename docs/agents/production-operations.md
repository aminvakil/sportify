# Production operations

- `sportify:telegram:send-predictions` defaults to a 5-minute lookback; production cron runs at `5,35`, so installed cron should pass `--lookback-minutes=40`.
- To correct an imported match score: update `matches.home_goals`/`matches.away_goals`, recompute affected `predictions` fields, apply point deltas to `scores.points`, recalculate `scores.exact_percentage`, then recompute `scores.pos_new`.
- DB naming gotchas: the users table is `users`; the standings exact percentage column is `scores.exact_percentage`.
- Football-Data result corrections are not overwritten by the current importer once match goals are non-null; importer only fills goals when both DB goal fields are `NULL`.
- When manually sending Telegram messages, capture `message_id` if later edits may be needed; otherwise only follow-up messages are possible.
