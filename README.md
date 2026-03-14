# Lunara

A bespoke WordPress website for film reviews and a custom Oscars database.

## Repository Structure

```
wp-content/
├── themes/
│   └── lunara/               # Custom Lunara theme
│       ├── style.css         # Theme header + base styles
│       ├── functions.php     # Theme setup, CPTs, taxonomies, helpers
│       ├── index.php         # Main fallback template
│       ├── single.php        # Single post / film review template
│       ├── archive.php       # Archive (genre, decade, date) template
│       ├── page.php          # Static page template
│       ├── search.php        # Search results template
│       ├── 404.php           # 404 error page
│       ├── header.php        # Site header & navigation
│       ├── footer.php        # Site footer & widgets
│       ├── sidebar.php       # Sidebar widget area
│       ├── inc/
│       │   └── nav-fallback.php
│       ├── template-parts/
│       │   ├── content.php              # Generic post card
│       │   ├── content-lunara_review.php # Film review card
│       │   └── content-search.php       # Search result item
│       └── assets/
│           ├── css/main.css  # Supplementary styles
│           └── js/main.js    # Mobile nav + Oscars table filtering
└── plugins/
    └── lunara-oscars/        # Bespoke Oscars Database plugin
        ├── lunara-oscars.php # Plugin bootstrap
        ├── includes/
        │   ├── class-oscars-database.php   # DB CRUD (custom table)
        │   └── class-oscars-shortcodes.php # Front-end shortcodes
        ├── admin/
        │   ├── class-oscars-admin.php      # Admin list/edit UI
        │   └── admin.css
        └── assets/
            └── oscars.css    # Front-end plugin styles
```

## WordPress Setup

### Requirements

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+

### Installation

1. Clone this repository into the root of your WordPress installation:
   ```
   git clone https://github.com/TheAntagonist2020/Lunara.git .
   ```
   Or clone it alongside WordPress and copy the `wp-content` directory into place.

2. In the WordPress Admin:
   - **Appearance → Themes** – activate the **Lunara** theme.
   - **Plugins → Installed Plugins** – activate **Lunara Oscars Database**.
     The plugin will automatically create the `wp_lunara_oscars` database table on activation.

3. Assign menus under **Appearance → Menus**:
   - *Primary Menu* – main site navigation
   - *Footer Menu* – footer links
   - *Social Links* – social icons

4. Optionally configure widget areas under **Appearance → Widgets**:
   - Main Sidebar
   - Footer — Column 1 / 2 / 3

### Custom Post Type: Film Reviews

Reviews are stored in the `lunara_review` custom post type (slug: `/reviews/`).

Each review supports custom meta fields (editable via the **Film Details** meta box):

| Field              | Description                          |
|--------------------|--------------------------------------|
| Director           | Film director name                   |
| Release Year       | Four-digit year                      |
| Runtime            | Length in minutes                    |
| Rating             | Score 0–10 (supports half points)    |
| Certification      | e.g. 15, PG-13, R                    |
| Country            | Country of origin                    |
| Streaming On       | e.g. Netflix, Prime Video            |
| Oscar Nominations  | Number of Academy Award nominations  |
| Oscar Wins         | Number of Academy Award wins         |

**Taxonomies:**
- *Genres* (`/genre/`) – hierarchical
- *Decades* (`/decade/`) – flat

### Oscars Database Plugin

Navigate to **Oscars DB** in the WordPress admin sidebar to:
- Browse all nominations with live filters (year, category, winner/nominee)
- Add / edit / delete individual nominations

#### Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[lunara_oscars]` | Filterable table of all nominations |
| `[lunara_oscars year="2024"]` | Filter by ceremony year |
| `[lunara_oscars winners="only"]` | Show winners only |
| `[lunara_oscars_winners year="2024"]` | List of winners for a specific year |
| `[lunara_film_oscars film="Oppenheimer"]` | Oscar history for a specific film |

## Development

This repository tracks only custom code — WordPress core files are excluded by `.gitignore`.

To contribute:
1. Fork the repository
2. Make changes in a feature branch
3. Open a pull request against `main`
