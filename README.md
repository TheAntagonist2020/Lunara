# Lunara

The repository for the Lunara film website, featuring reviews and a bespoke Oscars database.

## Installation into WordPress

Lunara is distributed as a WordPress theme, and may include one or more custom plugins.
To deploy this repository into a WordPress site, use the standard WordPress directory
layout under `wp-content`:

1. Clone or download this repository somewhere on your server or development machine
   (it does **not** need to live inside your WordPress directory):

   ```bash
   git clone https://github.com/your-org/lunara.git
   ```

2. Identify the Lunara **theme** directory in this repository. This is the folder that
   contains a `style.css` file with a WordPress `Theme Name` header (for example,
   a folder named `lunara` or similar).

3. Copy or symlink that theme directory into your WordPress installation under:

   ```text
   /path/to/wordpress/wp-content/themes/<lunara-theme-folder>/
   ```

   After this step, the Lunara theme folder should be a direct child of
   `wp-content/themes/`.

4. If this repository includes one or more custom **plugins** (each plugin is typically
   a folder containing a main PHP file with a WordPress plugin header), copy or symlink
   each plugin folder into:

   ```text
   /path/to/wordpress/wp-content/plugins/<plugin-folder>/
   ```

   After this step, each Lunara plugin folder should be a direct child of
   `wp-content/plugins/`.

5. Log in to your WordPress admin dashboard:

   - Go to **Appearance → Themes** and activate the **Lunara** theme.
   - Go to **Plugins → Installed Plugins** and activate any Lunara plugins you copied
     into `wp-content/plugins/`.

Once these steps are complete, your WordPress site will use the Lunara theme and
any associated plugins to power the film reviews and Oscars database functionality.

---

## API Reference

All endpoints use the WordPress AJAX API and are called via `POST` to `/wp-admin/admin-ajax.php`.

### Authentication

| Type | How to authenticate |
|---|---|
| **Public** | No authentication required; a nonce is accepted but not enforced |
| **Admin** | Must be logged in with `manage_options` capability; every request must include a valid `nonce` generated from the `aat_admin_nonce` action (except where noted) |

---

### Public Endpoints

These endpoints are accessible to all visitors (no login required). They power the front-end Oscars database table.

#### `aat_get_awards_datatable`

Server-side DataTables endpoint for the interactive Oscars table. Returns paginated, sorted, and filtered rows in the format DataTables expects.

**Method:** `POST`  
**Action parameter:** `action=aat_get_awards_datatable`  
**Auth:** Public (nonce optional)

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Optional. Accepted but not enforced for public read access. |
| `draw` | int | DataTables draw counter (echoed back). |
| `start` | int | Row offset (0-based). |
| `length` | int | Rows per page (1–200; default 25). |
| `search[value]` | string | Global search string. Matches against nominee, film, category, and more. |
| `order[0][column]` | int | Column index to sort by (1=Ceremony, 2=Year, 3=Category, 4=Nominee, 5=Film, 6=Winner). |
| `order[0][dir]` | string | Sort direction: `asc` or `desc`. |
| `category` | string | Filter to a canonical category name (e.g. `BEST PICTURE`). |
| `class` | string | Filter to an award class (e.g. `Acting`, `Directing`). |
| `year` | string | Filter to a ceremony year (e.g. `2024`, `1927/28`). |
| `ceremony` | int | Filter to a ceremony number (e.g. `97`). |
| `winners_only` | string | Pass `true` to return only winning nominations. |
| `columns[2][search][value]` | string | Decade prefix filter on the Year column (e.g. `202` for the 2020s). |

**Response:**

```json
{
  "draw": 1,
  "recordsTotal": 10834,
  "recordsFiltered": 42,
  "data": [
    {
      "ceremony": 97,
      "year": "2024",
      "class": "Picture",
      "canonical_category": "BEST PICTURE",
      "category": "Best Picture",
      "film": "Anora",
      "film_id": "tt28607951",
      "name": "",
      "nominees": "",
      "nominee_ids": "",
      "winner": "1",
      "detail": "",
      "note": "",
      "citation": "",
      "category_slug": "best-picture",
      "review_url": "https://example.com/reviews/anora/"
    }
  ],
  "stats": {
    "filtered_total": 42,
    "filtered_winners": 5
  }
}
```

---

#### `aat_get_awards_meta`

Returns distinct filter-dropdown values (categories, classes, years, ceremonies) and global record counts. Results are cached for 10 minutes.

**Method:** `POST`  
**Action parameter:** `action=aat_get_awards_meta`  
**Auth:** Public (nonce optional)

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Optional. Accepted but not enforced for public read access. |

**Response:**

```json
{
  "success": true,
  "data": {
    "categories": ["BEST PICTURE", "DIRECTING", "..."],
    "classes": ["Acting", "Directing", "Picture", "..."],
    "years": ["2024", "2023", "..."],
    "ceremonies": [97, 96, "..."],
    "totals": {
      "records": 10834,
      "winners": 1240,
      "categories": 148,
      "ceremonies": 97
    }
  }
}
```

---

#### `aat_get_awards_data`

Legacy (non-paginated) awards data endpoint. Returns all matching rows in a single response. For large datasets, prefer `aat_get_awards_datatable` instead.

**Method:** `POST`  
**Action parameter:** `action=aat_get_awards_data`  
**Auth:** Public (nonce optional)

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Optional. Accepted but not enforced for public read access. |
| `category` | string | Filter to a canonical category name. |
| `class` | string | Filter to an award class. |
| `year` | string | Filter to a ceremony year (e.g. `2024`). |
| `ceremony` | int | Filter to a ceremony number. |
| `winners_only` | string | Pass `true` to return only winning nominations. |
| `search` | string | Search string matched against nominee, film, category, and nominees fields. |

**Response:**

```json
{
  "success": true,
  "data": {
    "data": [ /* array of award row objects */ ],
    "categories": [],
    "classes": [],
    "years": [],
    "ceremonies": [],
    "total": 42
  }
}
```

---

### Admin Endpoints

All admin endpoints require a logged-in user with `manage_options` capability. Unless specified otherwise, include a `nonce` generated from `wp_create_nonce('aat_admin_nonce')`.

#### `aat_import_bundled_data`

Imports the bundled `oscars.csv` dataset in chunks to avoid PHP timeouts. Call this endpoint repeatedly, incrementing `offset` by `chunk_size` each time, until `done` is `true`.

**Method:** `POST`  
**Action parameter:** `action=aat_import_bundled_data`  
**Nonce action:** `aat_admin_nonce`

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Required. |
| `offset` | int | Row offset to start this chunk from (0 for the first request). |

**Response (in-progress):**

```json
{
  "success": true,
  "data": {
    "done": false,
    "offset": 500,
    "imported": 500,
    "total": 10834
  }
}
```

**Response (complete):**

```json
{
  "success": true,
  "data": {
    "done": true,
    "imported": 10834,
    "total": 10834
  }
}
```

---

#### `aat_import_data`

Uploads and imports a full CSV, TSV, or JSON awards dataset. **Replaces all existing data** (TRUNCATE + import).

**Method:** `POST` (multipart/form-data)  
**Action parameter:** `action=aat_import_data`  
**Nonce action:** `aat_admin_nonce`

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Required. |
| `import_file` or `file` | file | Required. A CSV/TSV file with a header row, or a JSON array of objects. |

**CSV/TSV required columns:** `Ceremony`, `Year`, `Class`, `CanonicalCategory`, `Category`, `Film`, `Name`, `Winner`  
**CSV/TSV optional columns:** `FilmId`, `Nominees`, `NomineeIds`, `Detail`, `Note`, `Citation`  
**JSON fields:** Same names as CSV columns (PascalCase).

The `Winner` column is interpreted as a boolean. For CSV/TSV the accepted truthy values are `1`, `true`, and `yes` (case-insensitive); any other value is treated as `false`. For JSON any truthy value is accepted.

**Response:**

```json
{
  "success": true,
  "data": {
    "imported": 10834,
    "errors": 0
  }
}
```

---

#### `aat_import_ceremony_delta`

Replaces the data for a single ceremony using an uploaded TSV/CSV file. Use this to add new nominations or update winner flags without re-importing the full dataset.

**Method:** `POST` (multipart/form-data)  
**Action parameter:** `action=aat_import_ceremony_delta`  
**Nonce action:** `aat_admin_nonce`

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Required. |
| `delta_file` | file | Required. A CSV/TSV file for a single ceremony (same column format as `aat_import_data`). |

**Response:**

```json
{
  "success": true,
  "data": {
    "imported": 57,
    "errors": 0,
    "ceremony": 97
  }
}
```

---

#### `aat_repair_schema`

Recreates missing database tables and flushes WordPress rewrite rules. Use this after a plugin update or if entity/hub permalink pages stop working.

**Method:** `POST`  
**Action parameter:** `action=aat_repair_schema`  
**Nonce action:** `aat_admin_nonce`

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Required. |

**Response:**

```json
{
  "success": true,
  "data": {
    "message": "Schema and rewrite rules repaired."
  }
}
```

---

#### `aat_clear_data`

Deletes all rows from the Academy Awards database table and invalidates caches. **This action is irreversible.**

**Method:** `POST`  
**Action parameter:** `action=aat_clear_data`  
**Nonce action:** `aat_admin_nonce`

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Required. |

**Response:**

```json
{
  "success": true,
  "data": {
    "message": "All data cleared."
  }
}
```

---

### Awards Tracker Endpoints

These endpoints manage the editorial predictions tracker (Predictions / Locks / Watchlist / Longshots).

#### `aat_tracker_search_entities`

Searches the awards database for films, people, and companies by name or IMDb ID. Returns up to 20 suggestions for use in the tracker admin autocomplete.

**Method:** `POST`  
**Action parameter:** `action=aat_tracker_search_entities`  
**Nonce action:** `aat_admin_nonce`

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Required. |
| `q` | string | Required. Search query (minimum 2 characters) or a bare IMDb ID (`tt…`, `nm…`, `co…`). |

**Response:**

```json
{
  "success": true,
  "data": {
    "results": [
      { "id": "tt28607951", "type": "title", "label": "Anora" },
      { "id": "nm0000001",  "type": "name",  "label": "Fred Astaire" },
      { "id": "co0000001",  "type": "company", "label": "Universal Pictures" }
    ]
  }
}
```

---

#### `aat_tracker_add_pick`

Adds a new tracker pick or updates an existing one (matched by ceremony + category + tier + entity). Pick tiers control the display section in the front-end tracker.

**Method:** `POST`  
**Action parameter:** `action=aat_tracker_add_pick`  
**Nonce action:** `aat_admin_nonce`

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Required. |
| `ceremony` | int | Required. Ceremony number (e.g. `97`). |
| `canonical_category` | string | Required. Canonical category name (e.g. `BEST PICTURE`). |
| `entity_type` | string | Required. `title`, `name`, or `company`. |
| `entity_id` | string | Required. IMDb-style entity ID (`tt…`, `nm…`, or `co…`). |
| `tier` | string | Pick tier: `prediction`, `lock`, `watch`, or `longshot` (default `watch`). Tiers rank confidence from highest (`prediction`) to lowest (`longshot`). |
| `rank` | int | Display rank within the tier (1–100; default 1). |
| `note` | string | Optional editorial note displayed on the tracker. |

**Response:**

```json
{
  "success": true,
  "data": { "message": "Saved." }
}
```

---

#### `aat_tracker_delete_pick`

Removes a tracker pick by its database ID.

**Method:** `POST`  
**Action parameter:** `action=aat_tracker_delete_pick`  
**Nonce action:** `aat_admin_nonce`

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Required. |
| `id` | int | Required. The primary key (`id`) of the tracker row to delete. |

**Response:**

```json
{
  "success": true,
  "data": { "message": "Deleted." }
}
```

---

### Poster Library Endpoints

These endpoints manage the unified poster library that maps IMDb title IDs to WordPress media attachments.

#### `aat_posters_save`

Maps an IMDb title ID to a WordPress media attachment, making the poster available site-wide (tracker, entity pages, hub pages).

**Method:** `POST`  
**Action parameter:** `action=aat_posters_save`  
**Nonce action:** `aat_admin_nonce`

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Required. |
| `imdb_id` | string | Required. IMDb title ID in `tt` format (e.g. `tt28607951`). |
| `attachment_id` | int | Required. WordPress media library attachment ID. |
| `source` | string | Optional. Origin label (e.g. `manual`, `review-sync`). Defaults to `manual`. |

**Response:**

```json
{
  "success": true,
  "data": { "message": "Poster saved." }
}
```

---

#### `aat_posters_delete`

Removes the poster mapping for a given IMDb title ID.

**Method:** `POST`  
**Action parameter:** `action=aat_posters_delete`  
**Nonce action:** `aat_admin_nonce`

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Required. |
| `imdb_id` | string | Required. IMDb title ID in `tt` format (e.g. `tt28607951`). |

**Response:**

```json
{
  "success": true,
  "data": { "message": "Removed." }
}
```

---

#### `aat_posters_sync_from_reviews`

Scans all published film reviews for a `_lunara_imdb_title_id` post meta value and automatically populates the poster library using each review's featured image.

**Method:** `POST`  
**Action parameter:** `action=aat_posters_sync_from_reviews`  
**Nonce action:** `aat_admin_nonce`

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Required. |

**Response:**

```json
{
  "success": true,
  "data": {
    "message": "Synced from reviews.",
    "synced": 42,
    "skipped": 3
  }
}
```

---

### Theme Endpoints

#### `lunara_save_carousel_order`

Saves the display order of posts in the homepage featured carousel.

**Method:** `POST`  
**Action parameter:** `action=lunara_save_carousel_order`  
**Nonce action:** `lunara_carousel_admin`

| Parameter | Type | Description |
|---|---|---|
| `nonce` | string | Required. Generated from `wp_create_nonce('lunara_carousel_admin')`. |
| `order[]` | int[] | Required. Ordered array of WordPress post IDs representing the new carousel sequence. |

**Response:**

```json
{
  "success": true,
  "data": {
    "message": "Order saved.",
    "count": 6
  }
}
```
