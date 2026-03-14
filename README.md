# Lunara Film

A bespoke WordPress website for film reviews and the Lunara Oscar Ledger — a comprehensive, searchable record of every Academy Award nominee and winner.

**Live site:** [lunarafilm.com](https://lunarafilm.com)

---

## Repository Structure

This repository tracks the custom code only. WordPress core, `wp-config.php`, and uploads are excluded by `.gitignore`.

```
wp-content/
├── themes/
│   └── lunara/                          # Child theme for Blocksy
│       ├── style.css                    # Theme header (Template: blocksy) + base styles
│       ├── functions.php                # Theme setup, CPT, meta boxes, carousel, helpers
│       ├── front-page.php               # Homepage template
│       ├── archive-review.php           # Film review archive
│       ├── taxonomy-lunara_director.php # Director taxonomy archive
│       └── assets/
│           ├── css/
│           │   └── lunara-carousel-admin.css   # Carousel manager admin styles
│           ├── js/
│           │   ├── lunara-carousel.js          # Front-end poster carousel
│           │   └── lunara-carousel-admin.js    # Carousel manager admin UI
│           └── data/
│               └── imdb-title-map.json         # IMDb title ID lookup for reviews
└── plugins/
    └── academy-awards-table/            # Lunara Film — Academy Awards Database plugin
        ├── academy-awards-table.php     # Plugin bootstrap + main class
        ├── readme.txt                   # Plugin readme / changelog
        ├── data/
        │   └── oscars.csv               # Bundled dataset (97 ceremonies, 12 000+ nominations)
        ├── assets/
        │   ├── css/
        │   │   ├── academy-awards-table.css    # Front-end table styles
        │   │   └── admin.css                   # Admin panel styles
        │   ├── js/
        │   │   ├── academy-awards-table.js     # DataTables + filters + entity links
        │   │   ├── admin.js                    # Admin CSV import + tracker + poster UI
        │   │   └── tracker-v2.js               # Awards Tracker frontend tabs
        │   └── img/
        │       └── oscar.png                   # Oscar statuette icon (add manually)
        └── templates/
            ├── table-display.php        # [academy_awards] shortcode output
            ├── entity-page.php          # Film / Person / Company profile pages
            ├── hub-page.php             # /oscars/ceremonies/, /oscars/categories/ hubs
            ├── tracker-v2.php           # [lunara_awards_tracker_v2] frontend
            ├── admin-page.php           # WP Admin: import + shortcode reference
            ├── tracker-admin.php        # WP Admin: Awards Tracker picks editor
            └── poster-admin.php         # WP Admin: Poster Library
```

---

## Setup

### Requirements

- WordPress 6.0+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- **Parent theme:** [Blocksy](https://wordpress.org/themes/blocksy/) (free version)

### Installation

1. Install and activate the **Blocksy** parent theme via **Appearance → Themes → Add New**.

2. Clone this repository into your WordPress root (or copy the `wp-content/` folder into place):
   ```bash
   git clone https://github.com/TheAntagonist2020/Lunara.git .
   ```

3. In WP Admin:
   - **Appearance → Themes** — activate **Lunara Film** (child theme).
   - **Plugins → Installed Plugins** — activate **Lunara Film — Academy Awards Database**.

4. Import the Oscars dataset:
   - Go to **Academy Awards** in the WP Admin sidebar.
   - Click **Import Bundled oscars.csv** — this loads the full historical dataset into the `wp_academy_awards` table (chunked to avoid timeouts).

5. Add the Oscar statuette icon:
   - Upload `oscar.png` to `wp-content/plugins/academy-awards-table/assets/img/oscar.png`
   - (This image is not tracked in git — add your own or use a free SVG statuette.)

---

## Theme Details

The Lunara child theme extends [Blocksy](https://wordpress.org/themes/blocksy/) with:

- **`review` custom post type** — film reviews at `/reviews/`
- **`lunara_director` taxonomy** — director archives
- **Debrief meta box** — per-review fields: score (0–5 stars), release year, IMDb title ID, where to watch, theme echo, counter-program, career context
- **Poster carousel** — managed via **Appearance → Carousel Manager**; front-end shortcode `[lunara_carousel set="homepage"]`
- **Star rating renderer** — `lunara_render_stars( $score )` returns accessible HTML star icons

### Shortcodes (theme)

| Shortcode | Description |
|-----------|-------------|
| `[lunara_reviews count="6"]` | Grid of latest reviews |
| `[lunara_posts count="3"]` | Latest blog posts |
| `[lunara_carousel set="homepage"]` | Poster carousel |

---

## Oscars Database Plugin

The **Academy Awards Table** plugin powers the full Oscars section of the site.

### Features

- Interactive, server-side paginated table (DataTables) with instant search
- Dropdown filters: Category, Type, Year, Ceremony
- Winners-only toggle
- Clickable film/person profiles (internal entity pages with IMDb reference links)
- Hub pages: `/oscars/ceremonies/`, `/oscars/categories/`
- Awards Tracker (Predictions / Locks / Watchlist / Longshots)
- Poster Library — maps IMDb title IDs to WordPress media attachments
- One-click CSV importer + delta (single ceremony) updater

### Shortcodes (plugin)

| Shortcode | Description |
|-----------|-------------|
| `[academy_awards]` | Full interactive nominations table |
| `[academy_awards category="BEST PICTURE"]` | Filter by category |
| `[academy_awards year="2024"]` | Filter by year |
| `[academy_awards winners_only="true"]` | Winners only |
| `[academy_awards ceremony="latest"]` | Most recent ceremony |
| `[lunara_awards_tracker_v2]` | Awards Tracker (Predictions/Locks/Watchlist) |

### Database Tables

| Table | Purpose |
|-------|---------|
| `wp_academy_awards` | All nominations (97 ceremonies, 12 000+ rows) |
| `wp_aat_tracker` | Awards Tracker picks |
| `wp_aat_posters` | IMDb ID → Media Library attachment map |

---

## Development

This repository tracks only custom code. WordPress core, configuration, uploads, and default themes/plugins are excluded by `.gitignore`.

To contribute:
1. Fork the repository
2. Make changes in a feature branch
3. Open a pull request against `main`
