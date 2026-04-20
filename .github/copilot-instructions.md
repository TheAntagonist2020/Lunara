# Copilot Instructions for Lunara Film

## Project Overview

**Lunara Film** is a highly customized, premium WordPress web platform dedicated to film criticism, essays, curated lists, and awards prognostication. It is built as a **Blocksy child theme** (`Text Domain: lunara-film`, `Template: blocksy`) paired with a bespoke **Academy Awards plugin** (slug: `academy-awards-table`, shortcode: `[academy_awards]`, DB table: `wp_academy_awards`).

**Stack:** PHP 7.4+, WordPress 6.0+, MySQL (via `$wpdb`), vanilla JS, CSS custom properties. No build tools or JS framework unless already present.

---

## 1. IMDB IDs Are Mandatory (Critical Requirement)

Every film, filmmaker, director, cast member, and awards nominee or winner that appears anywhere in this codebase **must** carry both:

- **`imdb_id`** — the title identifier (e.g. `tt0068646`) — also referred to as the IMDB title ID or "IMDB TT".
- **`imdb_nm`** — the name/person identifier (e.g. `nm0000380`) — also referred to as the IMDB name ID.

When generating or modifying **database schemas**, always include `imdb_id VARCHAR(20)` columns on film/title tables and `imdb_nm VARCHAR(20)` columns on person/personnel tables.

When generating **PHP data models, array shapes, or class properties**, always include both `imdb_id` and `imdb_nm` fields where applicable.

When generating **UI components or template files**, always include `data-imdb-id` and `data-imdb-nm` attributes on the relevant HTML elements so they are available for client-side linking and analytics.

When generating **API or AJAX handlers**, always validate and sanitize these fields; reject or flag entries that are missing or malformed (IDs must match `/^tt\d{7,8}$/` and NMs must match `/^nm\d{7,8}$/`).

The file `imdb-title-map.json` at the repository root maps `"title|year"` keys to IMDB title IDs and is the canonical local reference for film lookups. Prefer it for offline resolution before falling back to external APIs.

---

## 2. Oscars / Awards Database (Maximum Priority)

The awards database is the most critical feature of this application.

### Database Schema Principles

- The `wp_academy_awards` table is the primary store. Any schema additions must be backward-compatible and added via versioned `maybe_upgrade_schema()` checks inside the `Academy_Awards_Table` class.
- When scaffolding related tables or columns, always model a **highly relational structure** that explicitly links:
  - `films` → `awards` → `ceremony_years` → `categories` → `nominees` → `winners`
  - `nominees` / `winners` → `personnel` (directors, writers, actors, composers, etc.)
- Every film record must carry `imdb_id`; every person record must carry `imdb_nm`.

### Query & Performance Principles

- Use `$wpdb->prepare()` for **all** parameterized queries — never interpolate user input.
- Add `INDEX` declarations on `imdb_id`, `imdb_nm`, `year`, `category`, and `winner` columns for fast filtering.
- When writing complex multi-join queries for the awards section, add inline comments explaining join logic.
- Cache expensive read queries with WordPress Transients (`get_transient` / `set_transient`) and provide explicit cache-busting hooks on data import or manual edits.

### Plugin Entry Point

The main plugin class is `Academy_Awards_Table` in `academy-awards-table.php`. All new plugin functionality must be added as methods of this class or as separate included files loaded from its constructor — never as loose procedural code outside the class.

---

## 3. Poster & Media Integration

Visual identity is paramount. Every film data model, schema, and UI component must handle high-resolution movie posters robustly.

### Data Model Requirements

- Always include a `poster_url VARCHAR(500)` (or equivalent) column/field alongside `imdb_id` for every film entity.
- Include a `poster_local_path` or cache key field so locally cached versions can be served without repeated remote calls.

### TMDB Integration

- Assume integration with the [TMDB API](https://developer.themoviedb.org/). When writing image fetch helpers:
  - Accept a `tmdb_id` **and** fall back to `imdb_id` for lookups.
  - Support multiple TMDB image sizes (`w185`, `w342`, `w500`, `original`).
  - Store the TMDB base URL in a WordPress option (`lunara_tmdb_image_base_url`) so it can be updated without code changes.

### Frontend / Template Requirements

- All `<img>` tags for posters must include:
  - `loading="lazy"`
  - Explicit `width` and `height` attributes to prevent layout shift.
  - A `srcset` or `sizes` attribute for responsive delivery where feasible.
  - An `onerror` fallback that swaps in a placeholder image (e.g. `/wp-content/themes/lunara-film/assets/images/poster-placeholder.jpg`).
- Maintain a consistent 2:3 aspect ratio (150×225, 300×450, etc.) for all poster thumbnails via CSS (`aspect-ratio: 2/3; object-fit: cover;`).

---

## 4. Architecture & Code Style

### WordPress Conventions

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/): snake_case functions/variables, tabs for indentation, Yoda conditions.
- Prefix **all** theme functions with `lunara_` and plugin functions/hooks with `aat_` (Academy Awards Table).
- Sanitize all input (`sanitize_text_field`, `absint`, `esc_url_raw`, etc.) and escape all output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).
- Register custom post types and taxonomies with explicit `rewrite` slugs and `show_in_rest => true` for Gutenberg compatibility.

### Custom Post Types & Taxonomies (Theme)

- CPT: `lunara_review` — individual film reviews.
- Taxonomies: `lunara_genre`, `lunara_decade`, `lunara_director`.
- All CPT meta fields for films must include `imdb_id`; all CPT meta fields for people must include `imdb_nm`.

### Modular, Bespoke Front-End

- Avoid rigid UI frameworks or component libraries unless already present in the codebase.
- Write highly modular, scalable, and deeply customizable PHP templates and CSS.
- Prioritize flexible layout systems that allow distinct aesthetic shifts between content types:
  - Standard review
  - Deep-dive essay
  - Database / query view (e.g. awards table)
  - Hub / landing pages

### Content Interconnectivity

When scaffolding any new feature, always account for the relational web:

```
Film ←→ Director / Cast (imdb_nm) ←→ Essays / Reviews (lunara_review CPT)
  └──→ Awards History (wp_academy_awards) ←→ Ceremony Year / Category
```

Every new page template, AJAX handler, or admin screen that touches a Film entity should also expose hooks or data to surface that film's associated reviews, essays, and awards history.

---

## 5. Security

- Never trust `$_GET`, `$_POST`, or REST request data without sanitization and capability checks (`current_user_can()`).
- All AJAX handlers must verify a nonce (`check_ajax_referer`) before processing.
- Use `$wpdb->prepare()` for every database query that includes dynamic values.
- Validate `imdb_id` and `imdb_nm` fields against their expected regex patterns before persisting to the database.
