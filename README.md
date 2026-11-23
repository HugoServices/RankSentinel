# RankSentinel

A free, open-source WordPress plugin for quick, manual SEO health checks.

## Features (v0.1.0)

- Checks WordPress search engine visibility setting.
- Checks homepage HTTP response status.
- Detects common `noindex` directives on the homepage (HTML robots meta and X-Robots-Tag).
- Checks availability of the **WordPress core** sitemap at `/wp-sitemap.xml`.
- Stores and displays the latest manual scan in **Tools → RankSentinel**.

No SaaS account, API key or tracking required.

## Installation

1. Download this repository as a ZIP.
2. Upload it through **Plugins → Add New → Upload Plugin** (ensure the ZIP contains the `ranksentinel` directory).
3. Activate the plugin.
4. Go to **Tools → RankSentinel** and run a scan.

## Limitations

- This is an early MVP, **not** a full site crawler or monitoring service.
- The `noindex` check detects common directive formats, not every possible HTML variation.
- The sitemap check uses the WordPress core sitemap; sites using Rank Math, SEOPress, Yoast, etc. may use `/sitemap_index.xml` instead.
- HTTP requests to the site's own domain may be blocked by hosting configurations.
- Scans are manual, with no notifications or scheduled monitoring yet.

## Roadmap

- Support popular SEO plugin sitemaps.
- Check selected public URLs and report 404s.
- Compare page titles and H1s between scans.
- Scheduled scans and email alerts.
- Export results as JSON/CSV.

## Contributing

Issues and pull requests are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
