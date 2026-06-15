# OnKupon Autonomous Commerce Content Agent

OnKupon Autonomous Commerce Content Agent is a source-only WordPress/WooCommerce plugin that keeps a marketplace/content-commerce site fresh by scanning active products, researching trusted sources, generating strict JSON editorial article plans through an OpenAI-compatible provider, validating content, publishing approved articles automatically, queueing social posts, collecting analytics, and updating learning weights.

The runtime plugin **never** calls Codex CLI, shells out to ChatGPT/Codex, executes arbitrary model-generated commands, or dynamically writes PHP code. All AI, research, and social integrations sit behind provider interfaces.

## Installation

1. Ensure WordPress runs PHP 8.1+ and WooCommerce is installed.
2. From this repository, run `bash onkupon-autonomous-commerce-agent/bin/build-zip.sh`.
3. Upload the generated `onkupon-autonomous-commerce-agent.zip` through **Plugins → Add New → Upload Plugin**.
4. Activate the plugin and open **OnKupon Agent → Settings**.
5. Configure status, safe mode, OpenAI-compatible provider, RSS sources, quotas, validation thresholds, and optional social/review settings.

## Source-only repository policy

Generated artifacts are intentionally excluded from git. Do not commit plugin ZIP files, `dist/`, `vendor/`, or `node_modules/`. The build script can produce a ZIP locally whenever needed.

## Dashboard

The plugin adds a top-level **OnKupon Agent** menu with:

- Overview
- Control Center
- Content Timeline
- Product Intelligence
- Social Queue
- Analytics
- Learning
- Integrations
- Logs
- Review Integrity
- Settings

Control Center actions require admin capability, nonce verification, and audit logging. Available controls include start, pause, resume, stop, emergency stop, run now, collect metrics, recalculate scores, clear locks, and safe mode toggle.

## Action Scheduler

When Action Scheduler is available, activation registers recurring jobs:

- Product scan every 6 hours
- Research every 2 hours
- Content generation every 4 hours
- Publishing hourly
- Social publishing every 15 minutes
- Metrics every 6 hours
- Learning daily
- Review requests daily
- Cleanup weekly

If Action Scheduler is missing, one-off queued operations gracefully fall back to WordPress cron where practical.

## OpenAI-compatible provider

Set secrets through constants or environment variables where possible:

```php
define( 'ONKUPON_AGENT_OPENAI_API_KEY', 'sk-...' );
```

Settings support base URL, model, temperature, max tokens, timeout, and daily budget. The provider uses WordPress HTTP APIs and records estimated token costs.

## Research providers

`ResearchProviderInterface` defines search/fetch/normalize methods. `RssResearchProvider` safely reads configured RSS feeds and respects allow/block lists. `SearchProviderInterface` is available for future official search APIs and must not be implemented by scraping Google Search.

## Publishing and validation

Articles are generated as strict JSON and must pass schema, required-field, quality, risk, thin-content, unsafe-claim, keyword-stuffing, and HTML sanitization checks before publishing. Invalid content is logged, rejected, and persisted to the Content Timeline with diagnostics and a sanitized preview.

## Social publishing

The social queue stores queued and processed posts. Provider stubs are present for LinkedIn, X, Facebook Page, Instagram, and Manual Quora Suggestions. Quora suggestions are stored as manual suggestions and not auto-posted.

## Review integrity policy

The plugin does not create fake customer reviews, fake ratings, fake 5-star claims, customer impersonation, or fake social proof. Review Integrity supports verified-buyer review request workflows and clearly labeled editorial/AI-assisted product insights only.


## Troubleshooting: Content Timeline is empty

If article generation appears to fail but **OnKupon Agent → Content Timeline** is empty, first confirm that the content generation job actually ran. The **Run Now** button queues product scan, research, and content generation actions; after clicking it, the admin notice shows how many actions were queued and whether Action Scheduler is available.

Check **WooCommerce → Status → Scheduled Actions** for pending, running, failed, or completed OnKupon actions. If Action Scheduler is unavailable, the plugin falls back to WP-Cron single events, so make sure WordPress cron is running.

Rejected article candidates are saved to the timeline with `rejected` status, validation notes, word count, quality/risk scores, and a short sanitized preview.

## Troubleshooting: Action Scheduler pending jobs

If jobs remain pending, check **WooCommerce → Status → Scheduled Actions** and filter for `onkupon_agent_*` hooks. Confirm WP-Cron or a real server cron is running due events. A recommended cron command is shown below.

## Troubleshooting: Article rejected: Content is too thin

If the admin log shows `warning | validation | Article rejected` with `Content is too thin`, the generated article body did not meet the configured word-count threshold. The validator now uses Unicode-aware word counting for Turkish and other non-English content, so words with Turkish characters are counted correctly.

Recommended fixes:

- Increase `openai_max_tokens` so the model has enough output budget for a long-form article.
- Lower `min_article_words` temporarily for testing; the setting defaults to `250` and the UI enforces a minimum of `50`.
- Ensure WooCommerce has active product data so the prompt can build product-aware sections.
- Ensure the prompt asks for a long-form body; the built-in prompt explicitly requires at least `min_article_words`, natural Turkish paragraphs with headings, concise answer, FAQ, product-aware sections, and CTA.
- Review the validation log context, which includes word count, minimum required words, body character length, title, scores, related product IDs, a sanitized body preview, and rejection reasons.

To inspect scheduled jobs in WordPress Admin, open **WooCommerce → Status → Scheduled Actions** and search for `onkupon_agent_content`, `onkupon_agent_research`, or `onkupon_agent_product_scan`.

## WP-CLI

```bash
wp onkupon-agent status
wp onkupon-agent start
wp onkupon-agent pause
wp onkupon-agent resume
wp onkupon-agent stop
wp onkupon-agent emergency-stop
wp onkupon-agent run --job=product-scan|research|content|publish|social|metrics|learning|all
wp onkupon-agent collect-metrics
wp onkupon-agent reset-locks
wp onkupon-agent build-report
```

## Recommended cron

```cron
* * * * * cd /path/to/wordpress && wp cron event run --due-now --quiet
```

## Testing

```bash
bash onkupon-autonomous-commerce-agent/bin/run-tests.sh
```

The current test script performs PHP syntax verification across source files. PHPUnit stubs are included for the core pure-logic modules and should be expanded with a WordPress test-suite bootstrap.


## Build ZIP with GitHub Actions

You can build the installable plugin ZIP without Codespaces or a local PHP setup:

1. Go to the repository on GitHub and open **Actions**.
2. Select **Build WordPress Plugin ZIP**.
3. Click **Run workflow**.
4. When the run completes, download the artifact named `onkupon-autonomous-commerce-agent`.
5. Upload `onkupon-autonomous-commerce-agent.zip` to **WordPress Admin → Plugins → Add New → Upload Plugin**.

The workflow lints PHP source files, builds the ZIP from the inner plugin folder only, excludes generated/dependency files, and uploads the ZIP as a workflow artifact. The generated ZIP must not be committed to this repository.

## Build

```bash
bash onkupon-autonomous-commerce-agent/bin/build-zip.sh
```

The ZIP is generated locally in the repository root and ignored by git.
