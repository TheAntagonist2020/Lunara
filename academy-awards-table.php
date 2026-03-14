<?php
/**
 * Plugin Name: Lunara Film — Academy Awards Database
 * Plugin URI: https://lunarafilm.com/oscars/
 * Description: A premium, server-side searchable database of every Academy Award nominee and winner (1st ceremony through 2024), compiled and maintained by Lunara Film.
 * Version: 2.4.7
 * Author: Lunara Film (Dalton Johnson)
 * Author URI: https://lunarafilm.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: academy-awards-table
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AAT_VERSION', '2.4.7');
define('AAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AAT_BUNDLED_CSV_PATH', AAT_PLUGIN_DIR . 'data/oscars.csv');

/**
 * Main Plugin Class
 */
class Academy_Awards_Table {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Consistent helper for the main Academy Awards database table.
     */
    private function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . "academy_awards";
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'maybe_upgrade_schema'));
        // Entity pages (Film / Person / Company)
        add_filter('query_vars', array($this, 'register_query_vars'));
        add_action('init', array($this, 'register_rewrite_rules'), 9);
        add_filter('template_include', array($this, 'maybe_entity_template'));
        add_filter('template_include', array($this, 'maybe_hub_template'));
        add_filter('pre_get_document_title', array($this, 'filter_entity_document_title'), 20);
        add_filter('pre_get_document_title', array($this, 'filter_hub_document_title'), 20);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_shortcode('academy_awards', array($this, 'render_shortcode'));

        add_shortcode('lunara_awards_tracker', array($this, 'render_tracker_shortcode'));

        // Tracker V2 (Predictions / Locks / Watchlist)
        add_shortcode('lunara_awards_tracker_v2', array($this, 'render_tracker_v2_shortcode'));
        add_shortcode('academy_awards_tracker_v2', array($this, 'render_tracker_v2_shortcode'));

        // Keep poster library in sync when reviews are saved
        add_action('save_post', array($this, 'maybe_sync_poster_from_review'), 20, 2);


        // AJAX handlers
        // (Legacy) Used by older front-end code.
        add_action('wp_ajax_aat_get_awards_data', array($this, 'ajax_get_awards_data'));
        add_action('wp_ajax_nopriv_aat_get_awards_data', array($this, 'ajax_get_awards_data'));

        // Meta for filter dropdowns + global stats (loaded once)
        add_action('wp_ajax_aat_get_awards_meta', array($this, 'ajax_get_awards_meta'));
        add_action('wp_ajax_nopriv_aat_get_awards_meta', array($this, 'ajax_get_awards_meta'));

        // Server-side DataTables endpoint
        add_action('wp_ajax_aat_get_awards_datatable', array($this, 'ajax_get_awards_datatable'));
        add_action('wp_ajax_nopriv_aat_get_awards_datatable', array($this, 'ajax_get_awards_datatable'));

        // Admin
        add_action('wp_ajax_aat_import_data', array($this, 'ajax_import_data'));
        add_action('wp_ajax_aat_import_bundled_data', array($this, 'ajax_import_bundled_data'));
        add_action('wp_ajax_aat_import_ceremony_delta', array($this, 'ajax_import_ceremony_delta'));
        add_action('wp_ajax_aat_repair_schema', array($this, 'ajax_repair_schema'));
        add_action('wp_ajax_aat_clear_data', array($this, 'ajax_clear_data'));

        // Tracker V2 admin AJAX
        add_action('wp_ajax_aat_tracker_search_entities', array($this, 'ajax_tracker_search_entities'));
        add_action('wp_ajax_aat_tracker_add_pick', array($this, 'ajax_tracker_add_pick'));
        add_action('wp_ajax_aat_tracker_delete_pick', array($this, 'ajax_tracker_delete_pick'));

        // Poster Library admin AJAX
        add_action('wp_ajax_aat_posters_save', array($this, 'ajax_posters_save'));
        add_action('wp_ajax_aat_posters_delete', array($this, 'ajax_posters_delete'));
        add_action('wp_ajax_aat_posters_sync_from_reviews', array($this, 'ajax_posters_sync_from_reviews'));


        // Activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'academy_awards';
        $charset_collate = $wpdb->get_charset_collate();
        if (stripos($charset_collate, "latin1") !== false) {
            $charset_collate = "DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        }

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            ceremony int(3) NOT NULL,
            year varchar(10) NOT NULL,
            class varchar(50) NOT NULL,
            canonical_category varchar(255) NOT NULL,
            category varchar(255) NOT NULL,
            film varchar(500) DEFAULT '',
            film_id varchar(255) DEFAULT '',
            name varchar(500) NOT NULL,
            nominees text,
            nominee_ids text,
            winner tinyint(1) DEFAULT 0,
            detail text,
            note text,
            citation text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ceremony (ceremony),
            KEY year (year),
            KEY class (class),
            KEY canonical_category (canonical_category(191)),
            KEY category (category(191)),
            KEY winner (winner),
            KEY film (film(191)),
            KEY name (name(191)),
            KEY ceremony_cat_winner (ceremony, canonical_category(191), winner),
            KEY film_id (film_id(191))
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Tracker + Poster Library tables (v1.9.0+)
        $tracker_table = $wpdb->prefix . 'aat_tracker';
        $poster_table  = $wpdb->prefix . 'aat_posters';

        $sql_tracker = "CREATE TABLE IF NOT EXISTS $tracker_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ceremony int(3) NOT NULL,
            canonical_category varchar(255) NOT NULL,
            entity_type varchar(20) NOT NULL DEFAULT 'title',
            entity_id varchar(32) NOT NULL,
            tier varchar(20) NOT NULL DEFAULT 'watch',
            rank int(11) NOT NULL DEFAULT 1,
            note text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ceremony (ceremony),
            KEY canonical_category (canonical_category(191)),
            KEY entity_id (entity_id),
            KEY tier (tier),
            UNIQUE KEY uniq_pick (ceremony, canonical_category(191), tier, entity_type, entity_id)
        ) $charset_collate;";

        $sql_posters = "CREATE TABLE IF NOT EXISTS $poster_table (
            imdb_id varchar(16) NOT NULL,
            attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
            source varchar(191) DEFAULT '',
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (imdb_id),
            KEY attachment_id (attachment_id)
        ) $charset_collate;";

        dbDelta($sql_tracker);
        dbDelta($sql_posters);


        // Store version
        add_option('aat_db_version', AAT_VERSION);

        // Ensure our Film/Person routes are registered.
        $this->register_rewrite_rules();
        flush_rewrite_rules();
        update_option('aat_rewrite_version', AAT_VERSION, false);
    }

    /**
     * Run schema upgrades when updating the plugin (plugin updates do not call activate()).
     */
    public function maybe_upgrade_schema() {
        $installed = get_option('aat_db_version', '0');
        if (version_compare((string) $installed, AAT_VERSION, '>=')) {
            return;
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'academy_awards';
        $charset_collate = $wpdb->get_charset_collate();
        if (stripos($charset_collate, "latin1") !== false) {
            $charset_collate = "DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        }

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            ceremony int(3) NOT NULL,
            year varchar(10) NOT NULL,
            class varchar(50) NOT NULL,
            canonical_category varchar(255) NOT NULL,
            category varchar(255) NOT NULL,
            film varchar(500) DEFAULT '',
            film_id varchar(255) DEFAULT '',
            name varchar(500) NOT NULL,
            nominees text,
            nominee_ids text,
            winner tinyint(1) DEFAULT 0,
            detail text,
            note text,
            citation text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ceremony (ceremony),
            KEY year (year),
            KEY class (class),
            KEY canonical_category (canonical_category(191)),
            KEY category (category(191)),
            KEY winner (winner),
            KEY film (film(191)),
            KEY name (name(191)),
            KEY ceremony_cat_winner (ceremony, canonical_category(191), winner),
            KEY film_id (film_id(191))
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Tracker + Poster Library tables
        $tracker_table = $wpdb->prefix . 'aat_tracker';
        $poster_table  = $wpdb->prefix . 'aat_posters';

        $sql_tracker = "CREATE TABLE IF NOT EXISTS $tracker_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ceremony int(3) NOT NULL,
            canonical_category varchar(255) NOT NULL,
            entity_type varchar(20) NOT NULL DEFAULT 'title',
            entity_id varchar(32) NOT NULL,
            tier varchar(20) NOT NULL DEFAULT 'watch',
            rank int(11) NOT NULL DEFAULT 1,
            note text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ceremony (ceremony),
            KEY canonical_category (canonical_category(191)),
            KEY entity_id (entity_id),
            KEY tier (tier),
            UNIQUE KEY uniq_pick (ceremony, canonical_category(191), tier, entity_type, entity_id)
        ) $charset_collate;";

        $sql_posters = "CREATE TABLE IF NOT EXISTS $poster_table (
            imdb_id varchar(16) NOT NULL,
            attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
            source varchar(191) DEFAULT '',
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (imdb_id),
            KEY attachment_id (attachment_id)
        ) $charset_collate;";

        dbDelta($sql_tracker);
        dbDelta($sql_posters);

        update_option('aat_db_version', AAT_VERSION);

        // Keep rewrites healthy on upgrades
        $rewrite_version = get_option('aat_rewrite_version', '0');
        if (version_compare((string) $rewrite_version, AAT_VERSION, '<')) {
            $this->register_rewrite_rules();
            flush_rewrite_rules();
            update_option('aat_rewrite_version', AAT_VERSION, false);
        }
    }


    /**
     * Initialize plugin
     */
    public function init() {
        load_plugin_textdomain('academy-awards-table', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // One-time rewrite refresh when the plugin updates (keeps Film/Person pages working without manual permalinks flush).
        $rewrite_version = (string) get_option('aat_rewrite_version', '');
        if ($rewrite_version !== AAT_VERSION) {
            // Refresh cached hub page detection when the plugin updates.
            delete_transient('aat_hub_page_ids_' . $this->get_entity_base_slug() . '_v1');

            $this->register_rewrite_rules();
            flush_rewrite_rules();
            update_option('aat_rewrite_version', AAT_VERSION, false);
        }
    }

    /**
     * Register query vars for entity pages.
     */
    public function register_query_vars($vars) {
        $vars[] = 'aat_entity';
        $vars[] = 'aat_entity_id';
        $vars[] = 'aat_hub';
        $vars[] = 'aat_hub_id';
        return $vars;
    }

    /**
     * Entity base slug, filterable.
     */
    public function get_entity_base_slug() {
        $slug = apply_filters('aat_entity_base_slug', 'oscars');
        $slug = sanitize_title($slug);
        return $slug ? $slug : 'oscars';
    }

    /**
     * Build the base URL for entity pages.
     */
    public function get_entity_base_url() {
        return trailingslashit(home_url('/' . $this->get_entity_base_slug() . '/'));
    }

    /**
     * URL to the main database page that contains the [academy_awards] shortcode.
     *
     * Themes can override this via:
     *   add_filter('aat_database_url', fn() => home_url('/oscars-database/'));
     */
    public function get_database_url() {
        $url = apply_filters('aat_database_url', '');
        $url = is_string($url) ? trim($url) : '';
        if (!empty($url)) {
            return esc_url_raw($url);
        }

        // Auto-detect a published page that contains the [academy_awards] shortcode.
        // This removes the need for site owners to hardcode aat_database_url in their theme.
        $cache_key = 'aat_database_url_autodetect_v1';
        $cached = get_transient($cache_key);
        if (is_string($cached) && $cached !== '') {
            return esc_url_raw($cached);
        }

        global $wpdb;
        if ($wpdb instanceof wpdb) {
            $like = '%' . $wpdb->esc_like('[academy_awards') . '%';
            $page_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = 'page' AND post_content LIKE %s ORDER BY menu_order ASC, ID ASC LIMIT 1",
                    $like
                )
            );

            if (empty($page_id)) {
                // Fallback: some sites might place the shortcode in a post.
                $page_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('post','page') AND post_content LIKE %s ORDER BY post_type DESC, ID ASC LIMIT 1",
                        $like
                    )
                );
            }

            $page_id = intval($page_id);
            if ($page_id > 0) {
                $permalink = get_permalink($page_id);
                if (is_string($permalink) && $permalink !== '') {
                    set_transient($cache_key, $permalink, 12 * HOUR_IN_SECONDS);
                    return esc_url_raw($permalink);
                }
            }
        }

        // Final fallback: the entity base URL (typically /oscars/).
        return esc_url_raw($this->get_entity_base_url());
    }


    /**
     * Detect optional, user-created WordPress pages for hub indexes under the base slug page.
     *
     * Why this exists:
     * - The plugin ships canonical hub routes like /oscars/categories/ and /oscars/ceremonies/.
     * - But many sites prefer custom slugs (e.g. /oscars/categories-page/) for menu or editorial reasons.
     *
     * If we can detect those pages, we:
     * 1) Add rewrite aliases so those URLs render the hub pages
     * 2) Use those permalinks in footer links and hub navigation
     * 3) Pull the page editor content as intro copy, so the site can control tone/voice
     *
     * This is cached and will refresh when the plugin version updates (rewrite flush).
     */
    public function get_detected_hub_page_ids() {
        $base_slug = $this->get_entity_base_slug();
        $cache_key = 'aat_hub_page_ids_' . $base_slug . '_v1';
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $ids = array(
            'ceremonies' => 0,
            'categories' => 0,
            'about'      => 0,
        );

        // Prefer the actual /{base}/ page as the parent, if it exists.
        $parent = get_page_by_path($base_slug);
        $parent_id = ($parent instanceof WP_Post) ? intval($parent->ID) : 0;

        $args = array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        );

        if ($parent_id > 0) {
            $args['post_parent'] = $parent_id;
        }

        $pages = get_posts($args);
        if (is_array($pages)) {
            foreach ($pages as $p) {
                if (!($p instanceof WP_Post)) continue;

                $title = (string) $p->post_title;
                $slug  = (string) $p->post_name;

                $t = strtolower($title);
                $s = strtolower($slug);

                if ($ids['ceremonies'] === 0 && (strpos($t, 'ceremon') !== false || strpos($s, 'ceremon') !== false)) {
                    $ids['ceremonies'] = intval($p->ID);
                    continue;
                }

                if ($ids['categories'] === 0 && (strpos($t, 'categor') !== false || strpos($s, 'categor') !== false)) {
                    $ids['categories'] = intval($p->ID);
                    continue;
                }

                if ($ids['about'] === 0 && (strpos($t, 'about') !== false || strpos($s, 'about') !== false)) {
                    $ids['about'] = intval($p->ID);
                    continue;
                }
            }
        }

        set_transient($cache_key, $ids, 12 * HOUR_IN_SECONDS);
        return $ids;
    }

    public function get_hub_page_post($hub) {
        $hub = sanitize_text_field((string) $hub);
        $ids = $this->get_detected_hub_page_ids();
        $id = isset($ids[$hub]) ? intval($ids[$hub]) : 0;
        if ($id > 0) {
            $p = get_post($id);
            if ($p instanceof WP_Post) {
                return $p;
            }
        }
        return null;
    }

    public function get_hub_page_slug($hub) {
        $p = $this->get_hub_page_post($hub);
        if ($p instanceof WP_Post) {
            return sanitize_title((string) $p->post_name);
        }
        return '';
    }

    public function get_hub_page_url($hub) {
        $p = $this->get_hub_page_post($hub);
        if ($p instanceof WP_Post) {
            $url = get_permalink($p);
            if (is_string($url) && $url !== '') {
                return esc_url_raw($url);
            }
        }
        return '';
    }

    /**
     * Hub URLs
     */
    public function get_ceremonies_index_url() {
        $url = $this->get_hub_page_url('ceremonies');
        if (!empty($url)) return $url;
        return esc_url_raw($this->get_entity_base_url() . 'ceremonies/');
    }

    public function get_categories_index_url() {
        $url = $this->get_hub_page_url('categories');
        if (!empty($url)) return $url;
        return esc_url_raw($this->get_entity_base_url() . 'categories/');
    }

    public function get_about_url() {
        $url = $this->get_hub_page_url('about');
        if (!empty($url)) return $url;
        return esc_url_raw($this->get_entity_base_url() . 'about/');
    }

    public function get_ceremony_url($ceremony) {
        $n = intval($ceremony);
        if ($n <= 0) return '';
        return esc_url_raw($this->get_entity_base_url() . 'ceremony/' . $n . '/');
    }

    public function get_category_url($canonical_category) {
        $cat = (string) $canonical_category;
        if ($cat === '') return '';
        return esc_url_raw($this->get_entity_base_url() . 'category/' . sanitize_title($cat) . '/');
    }

    /**
     * Register rewrite rules for entity pages.
     */
    public function register_rewrite_rules() {
        $base = $this->get_entity_base_slug();
        // Entity pages
        add_rewrite_rule('^' . preg_quote($base, '/') . '/(title|name|company)/(tt\d+|nm\d+|co\d+)/?$', 'index.php?aat_entity=$matches[1]&aat_entity_id=$matches[2]', 'top');

        // Hub pages
        add_rewrite_rule('^' . preg_quote($base, '/') . '/ceremony/(\d{1,3})/?$', 'index.php?aat_hub=ceremony&aat_hub_id=$matches[1]', 'top');
        add_rewrite_rule('^' . preg_quote($base, '/') . '/category/([^/]+)/?$', 'index.php?aat_hub=category&aat_hub_id=$matches[1]', 'top');
        add_rewrite_rule('^' . preg_quote($base, '/') . '/ceremonies/?$', 'index.php?aat_hub=ceremonies', 'top');
        add_rewrite_rule('^' . preg_quote($base, '/') . '/categories/?$', 'index.php?aat_hub=categories', 'top');
        add_rewrite_rule('^' . preg_quote($base, '/') . '/about/?$', 'index.php?aat_hub=about', 'top');

        // Optional hub page aliases (lets the site use custom slugs like /oscars/categories-page/).
        $hub_ids = $this->get_detected_hub_page_ids();

        if (!empty($hub_ids['ceremonies'])) {
            $slug = $this->get_hub_page_slug('ceremonies');
            if (!empty($slug) && $slug !== 'ceremonies') {
                add_rewrite_rule('^' . preg_quote($base, '/') . '/' . preg_quote($slug, '/') . '/?$', 'index.php?aat_hub=ceremonies', 'top');
            }
        }

        if (!empty($hub_ids['categories'])) {
            $slug = $this->get_hub_page_slug('categories');
            if (!empty($slug) && $slug !== 'categories') {
                add_rewrite_rule('^' . preg_quote($base, '/') . '/' . preg_quote($slug, '/') . '/?$', 'index.php?aat_hub=categories', 'top');
            }
        }

        if (!empty($hub_ids['about'])) {
            $slug = $this->get_hub_page_slug('about');
            if (!empty($slug) && $slug !== 'about') {
                add_rewrite_rule('^' . preg_quote($base, '/') . '/' . preg_quote($slug, '/') . '/?$', 'index.php?aat_hub=about', 'top');
            }
        }
    }

    /**
     * True when the current request is one of our entity pages.
     */
    public function is_entity_request() {
        $entity = get_query_var('aat_entity');
        $id = get_query_var('aat_entity_id');
        return (!empty($entity) && !empty($id));
    }

    /**
     * True when the current request is one of our hub pages
     * (Ceremony / Category / Ceremonies index / Categories index / About).
     */
    public function is_hub_request() {
        $hub = get_query_var('aat_hub');
        return !empty($hub);
    }

    /**
     * Intercept and render Film/Person/Company pages.
     */
    public function maybe_entity_template($template) {
        if (!$this->is_entity_request()) {
            return $template;
        }

        $entity_template = AAT_PLUGIN_DIR . 'templates/entity-page.php';
        if (file_exists($entity_template)) {
            return $entity_template;
        }

        return $template;
    }

    /**
     * Intercept and render hub pages.
     */
    public function maybe_hub_template($template) {
        if (!$this->is_hub_request() || $this->is_entity_request()) {
            return $template;
        }

        $hub_template = AAT_PLUGIN_DIR . 'templates/hub-page.php';
        if (file_exists($hub_template)) {
            return $hub_template;
        }

        return $template;
    }

    /**
     * Adjust <title> on Film/Person pages for better UX/SEO.
     */
    public function filter_entity_document_title($title) {
        if (!$this->is_entity_request()) {
            return $title;
        }

        $entity = sanitize_text_field(get_query_var('aat_entity'));
        $id = sanitize_text_field(get_query_var('aat_entity_id'));
        $label = $this->get_entity_display_name($entity, $id);
        if ($label) {
            $prefix = ($entity === 'title') ? 'Film' : (($entity === 'company') ? 'Company' : 'Person');
            return $label . ' — ' . $prefix . ' — Academy Awards Database';
        }

        return $title;
    }

    /**
     * Adjust <title> on hub pages (Ceremony / Category / Index / About).
     */
    public function filter_hub_document_title($title) {
        if (!$this->is_hub_request() || $this->is_entity_request()) {
            return $title;
        }

        $hub = sanitize_text_field(get_query_var('aat_hub'));
        $hub_id = sanitize_text_field(get_query_var('aat_hub_id'));

        if ($hub === 'ceremony') {
            $ceremony = intval($hub_id);
            if ($ceremony > 0) {
                $year = $this->get_ceremony_year($ceremony);
                $label = $this->ordinal($ceremony) . ' Academy Awards';
                if (!empty($year)) {
                    return $label . ' (' . $year . ') — Academy Awards Database';
                }
                return $label . ' — Academy Awards Database';
            }
        }

        if ($hub === 'category') {
            $cat = $this->resolve_category_slug($hub_id);
            if (!empty($cat)) {
                return $this->format_category_display($cat) . ' — Category — Academy Awards Database';
            }
        }

        if ($hub === 'ceremonies') {
            return 'Ceremonies — Academy Awards Database';
        }

        if ($hub === 'categories') {
            return 'Categories — Academy Awards Database';
        }

        if ($hub === 'about') {
            return 'About This Database — Academy Awards Database';
        }

        return $title;
    }

    /**
     * Ordinal helper (97 => 97th)
     */
    public function ordinal($n) {
        $n = intval($n);
        if ($n <= 0) return '';
        $s = array('th', 'st', 'nd', 'rd');
        $v = $n % 100;
        return $n . ($s[($v - 20) % 10] ?? $s[$v] ?? $s[0]);
    }

    /**
     * Get the ceremony year label for a ceremony number.
     */
    public function get_ceremony_year($ceremony) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'academy_awards';
        $ceremony = intval($ceremony);
        if ($ceremony <= 0) return '';
        $sql = $wpdb->prepare("SELECT MIN(year) FROM $table_name WHERE ceremony = %d", $ceremony);
        $year = $wpdb->get_var($sql);
        return is_string($year) ? $year : '';
    }


    /**
     * Get the latest ceremony number in the dataset.
     */
    public function get_max_ceremony() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'academy_awards';
        $max = intval($wpdb->get_var("SELECT MAX(ceremony) FROM $table_name"));
        return $max;
    }

    /**
     * Get the latest year label in the dataset (based on max ceremony).
     */
    public function get_latest_year_label() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'academy_awards';
        $row = $wpdb->get_row("SELECT year FROM $table_name ORDER BY ceremony DESC, id DESC LIMIT 1", ARRAY_A);
        $year = is_array($row) ? (string) ($row['year'] ?? '') : '';
        return $year;
    }

    /**
     * Convert canonical categories to a friendlier display label (for common entries).
     */
    public function format_category_display($canonical_category) {
        $cat = (string) $canonical_category;
        if ($cat === '') return '';
        $map = array(
            'ACTOR IN A LEADING ROLE' => 'Best Actor',
            'ACTRESS IN A LEADING ROLE' => 'Best Actress',
            'ACTOR IN A SUPPORTING ROLE' => 'Best Supporting Actor',
            'ACTRESS IN A SUPPORTING ROLE' => 'Best Supporting Actress',
            'BEST PICTURE' => 'Best Picture',
            'DIRECTING' => 'Best Director',
        );
        if (isset($map[$cat])) {
            return $map[$cat];
        }
        return $cat;
    }

    /**
     * Resolve a category slug (sanitize_title(canonical_category)) back to canonical_category.
     */
    public function resolve_category_slug($slug) {
        $slug = sanitize_title((string) $slug);
        if ($slug === '') return '';

        $cache_key = 'aat_category_slug_map_v1';
        $map = get_transient($cache_key);
        if (!is_array($map)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'academy_awards';
            $cats = $wpdb->get_col("SELECT DISTINCT canonical_category FROM $table_name WHERE canonical_category != '' ORDER BY canonical_category ASC");
            $map = array();
            if (is_array($cats)) {
                foreach ($cats as $cat) {
                    $cat = (string) $cat;
                    $s = sanitize_title($cat);
                    if ($s && !isset($map[$s])) {
                        $map[$s] = $cat;
                    }
                }
            }
            set_transient($cache_key, $map, 12 * HOUR_IN_SECONDS);
        }

        return isset($map[$slug]) ? (string) $map[$slug] : '';
    }

    /**
     * Helper: create a safe WHERE clause for pipe-delimited id fields.
     */
    private function build_pipe_match_where($field, $id, &$values) {
        $field = preg_replace('/[^a-zA-Z0-9_]/', '', $field);
        $id = (string) $id;
        $values = array(
            $id,
            $id . '|%',
            '%|' . $id . '|%',
            '%|' . $id,
        );
        return "($field = %s OR $field LIKE %s OR $field LIKE %s OR $field LIKE %s)";
    }

    /**
     * Query rows for an entity.
     */
    public function get_entity_rows($entity, $id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'academy_awards';

        $entity = sanitize_text_field($entity);
        $id = sanitize_text_field($id);

        if (!in_array($entity, array('title', 'name', 'company'), true)) {
            return array();
        }

        if ($entity === 'title' && !preg_match('/^tt\d+$/', $id)) {
            return array();
        }
        if ($entity === 'name' && !preg_match('/^nm\d+$/', $id)) {
            return array();
        }
        if ($entity === 'company' && !preg_match('/^co\d+$/', $id)) {
            return array();
        }

        $field = ($entity === 'title') ? 'film_id' : 'nominee_ids';

        $values = array();
        $where = $this->build_pipe_match_where($field, $id, $values);

        $sql = "SELECT DISTINCT ceremony, year, class, canonical_category, category, film, film_id, name, nominees, nominee_ids, winner, detail, note, citation FROM $table_name WHERE $where ORDER BY ceremony DESC, canonical_category ASC, winner DESC";
        $sql = $wpdb->prepare($sql, $values);
        $rows = $wpdb->get_results($sql, ARRAY_A);

        if (!is_array($rows)) {
            return array();
        }

        foreach ($rows as $i => $row) {
            $rows[$i] = $this->apply_row_hotfixes($row);
        }

        return $rows;
    }

    /**
     * Apply targeted display/data hotfixes for known problematic rows.
     * These overrides protect the live UI even when the stored dataset is stale
     * or carries a bad credit string.
     */
    private function apply_row_hotfixes($row) {
        if (!is_array($row)) {
            return array();
        }

        $ceremony = isset($row['ceremony']) ? intval($row['ceremony']) : 0;
        $year = isset($row['year']) ? trim((string) $row['year']) : '';
        $cat = strtoupper(trim((string) ($row['canonical_category'] ?? $row['category'] ?? '')));
        $film = trim((string) ($row['film'] ?? ''));
        $film_id = trim((string) ($row['film_id'] ?? ''));

        // Specific credit correction reported by Dalton.
        if ($ceremony === 98 && $year === '2025' && $cat === 'ACTRESS IN A SUPPORTING ROLE' && $film_id === 'tt22042012') {
            $row['name'] = 'HAILEE STEINFELD';
            $row['nominees'] = 'Hailee Steinfeld';
            $row['nominee_ids'] = 'nm2794962';
        }

        // Backfill specific Best Picture rows that are blank in the bundled dataset.
        if ($cat === 'BEST PICTURE' && trim((string) ($row['name'] ?? '')) === '' && trim((string) ($row['nominees'] ?? '')) === '') {
            $best_picture_backfills = array(
                'Crouching Tiger, Hidden Dragon' => array(
                    'name' => 'Bill Kong, Hsu Li Kong and Ang Lee',
                    'nominees' => 'Bill Kong|Hsu Li Kong|Ang Lee',
                    'nominee_ids' => '',
                    'detail' => 'Producers',
                ),
                'Good Night, and Good Luck.' => array(
                    'name' => 'Grant Heslov',
                    'nominees' => 'Grant Heslov',
                    'nominee_ids' => 'nm0381416',
                    'detail' => 'Producer',
                ),
                'Three Billboards Outside Ebbing, Missouri' => array(
                    'name' => 'Graham Broadbent, Pete Czernin and Martin McDonagh',
                    'nominees' => 'Graham Broadbent|Pete Czernin|Martin McDonagh',
                    'nominee_ids' => '',
                    'detail' => 'Producers',
                ),
                'The Godfather Part III' => array(
                    'name' => 'Francis Ford Coppola',
                    'nominees' => 'Francis Ford Coppola',
                    'nominee_ids' => 'nm0000338',
                    'detail' => 'Producer',
                ),
                'Hello, Dolly!' => array(
                    'name' => 'Ernest Lehman',
                    'nominees' => 'Ernest Lehman',
                    'nominee_ids' => 'nm0500073',
                    'detail' => 'Producer',
                ),
                'Rachel, Rachel' => array(
                    'name' => 'Paul Newman',
                    'nominees' => 'Paul Newman',
                    'nominee_ids' => 'nm0000056',
                    'detail' => 'Producer',
                ),
                'All This, and Heaven Too' => array(
                    'name' => 'Warner Bros.',
                    'nominees' => 'Warner Bros.',
                    'nominee_ids' => '',
                    'detail' => 'Production company',
                ),
            );

            if (isset($best_picture_backfills[$film])) {
                $patch = $best_picture_backfills[$film];
                foreach ($patch as $k => $v) {
                    $row[$k] = $v;
                }
            }
        }

        return $row;
    }

    /**
     * Determine a display name for an entity using the dataset.
     */
    public function get_entity_display_name($entity, $id) {
        $cache_key = 'aat_entity_label_' . md5($entity . ':' . $id);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return (string) $cached;
        }

        $rows = $this->get_entity_rows($entity, $id);
        $label = '';

        if (!empty($rows)) {
            $first = $rows[0];
            if ($entity === 'title') {
                $label = $this->map_pipe_value_to_id($first['film'] ?? '', $first['film_id'] ?? '', $id);
            } else {
                $label = $this->map_pipe_value_to_id($first['nominees'] ?? '', $first['nominee_ids'] ?? '', $id);
                if (!$label) {
                    // Fallback: sometimes the Name field contains the person string.
                    $label = (string) ($first['name'] ?? '');
                }
            }
        }

        $label = trim((string) $label);
        set_transient($cache_key, $label, 12 * HOUR_IN_SECONDS);
        return $label;
    }

    /**
     * Map a pipe-delimited value list to the matching pipe-delimited id list.
     */
    public function map_pipe_value_to_id($value_list, $id_list, $target_id) {
        $values = array_filter(array_map('trim', explode('|', (string) $value_list)), 'strlen');
        $ids = array_filter(array_map('trim', explode('|', (string) $id_list)), 'strlen');
        $target_id = trim((string) $target_id);

        if (!empty($values) && count($values) === count($ids)) {
            foreach ($ids as $idx => $id) {
                if ($id === $target_id && isset($values[$idx])) {
                    return (string) $values[$idx];
                }
            }
        }

        // Fallback: first value.
        return !empty($values) ? (string) $values[0] : '';
    }

    /**
     * Look up the display name for a title (film) by its IMDb ID.
     */
    public function lookup_title_label($tt_id) {
        $tt_id = trim((string) $tt_id);
        if ($tt_id === '' || !preg_match('/^tt\d+$/', $tt_id)) {
            return '';
        }
        return $this->get_entity_display_name('title', $tt_id);
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Load assets only on:
        //  - pages that contain the [academy_awards] shortcode, OR
        //  - our internal Film/Person/Company pages, OR
        //  - our hub pages (Ceremony/Category/Index/About)
        $is_entity = $this->is_entity_request();
        $is_hub = $this->is_hub_request();
        $hub = $is_hub ? sanitize_text_field(get_query_var('aat_hub')) : '';
        $hub_needs_table = $is_hub && in_array($hub, array('ceremony', 'category'), true);

        $is_table_page = false;
        $is_tracker_v2_page = false;

        if (!$is_entity) {
            global $post;
            if ($post instanceof WP_Post) {
                $is_table_page = (
                    has_shortcode($post->post_content, 'academy_awards') ||
                    has_shortcode($post->post_content, 'lunara_awards_tracker')
                );

                $is_tracker_v2_page = (
                    has_shortcode($post->post_content, 'lunara_awards_tracker_v2') ||
                    has_shortcode($post->post_content, 'academy_awards_tracker_v2')
                );
            }
        }

        // Allow themes/site owners to force-load assets where needed.
        if (apply_filters('aat_force_enqueue_assets', false) === true) {
            $is_table_page = true;
        }

        if (!$is_entity && !$is_hub && !$is_table_page && !$is_tracker_v2_page) {
            return;
        }

        // Always load plugin styles for the table, entity pages, and hub pages.
        // Only load DataTables and the plugin JS when we are actually rendering a table.
        if ($is_table_page || $hub_needs_table) {
            // DataTables CSS
            wp_enqueue_style(
                'datatables-css',
                'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
                array(),
                '1.13.7'
            );

            wp_enqueue_style(
                'datatables-responsive-css',
                'https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css',
                array('datatables-css'),
                '2.5.0'
            );

            // Plugin CSS
            wp_enqueue_style(
                'aat-styles',
                AAT_PLUGIN_URL . 'assets/css/academy-awards-table.css',
                array('datatables-css'),
                AAT_VERSION
            );

            // jQuery (WordPress includes this)
            wp_enqueue_script('jquery');

            // DataTables JS
            wp_enqueue_script(
                'datatables-js',
                'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
                array('jquery'),
                '1.13.7',
                true
            );

            wp_enqueue_script(
                'datatables-responsive-js',
                'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js',
                array('datatables-js'),
                '2.5.0',
                true
            );

            // Plugin JS
            wp_enqueue_script(
                'aat-script',
                AAT_PLUGIN_URL . 'assets/js/academy-awards-table.js',
                array('jquery', 'datatables-js', 'datatables-responsive-js'),
                AAT_VERSION,
                true
            );

            // Localize script
            wp_localize_script('aat-script', 'aatData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aat_nonce'),
                'entityBase' => $this->get_entity_base_url(),
            ));
        } else {
            // Entity pages + hub pages (non-table): just the plugin styling (keeps pages light and fast).
            wp_enqueue_style(
                'aat-styles',
                AAT_PLUGIN_URL . 'assets/css/academy-awards-table.css',
                array(),
                AAT_VERSION
            );

            // Tracker V2 page (no DataTables required)
            if ($is_tracker_v2_page) {
                wp_enqueue_script(
                    'aat-tracker-v2',
                    AAT_PLUGIN_URL . 'assets/js/tracker-v2.js',
                    array('jquery'),
                    AAT_VERSION,
                    true
                );

                wp_localize_script('aat-tracker-v2', 'aatTracker', array(
                    'entityBase' => $this->get_entity_base_url(),
                    'databaseUrl' => $this->get_database_url(),
                ));
            }
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts($hook) {
        /**
         * IMPORTANT (WordPress.com / managed hosts):
         * The $hook suffix can vary depending on how the admin menu is registered.
         * If we rely only on $hook matching, our admin JS may never load, which makes
         * the import buttons appear to "do nothing".
         *
         * Safer: gate by the `page` query var (our menu slugs) instead.
         */
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $allowed_pages = array('academy-awards-table', 'academy-awards-tracker', 'academy-awards-posters');

        if (!in_array($page, $allowed_pages, true)) {
            return;
        }

        // Media library is needed on Poster Library screen
        // Media library is needed on Poster Library screen
        if ($page === 'academy-awards-posters') {
            wp_enqueue_media();
        }


        wp_enqueue_style(
            'aat-admin-styles',
            AAT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AAT_VERSION
        );

        wp_enqueue_script(
            'aat-admin-script',
            AAT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            AAT_VERSION,
            true
        );

        wp_localize_script('aat-admin-script', 'aatAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aat_admin_nonce'),
            'entityBase' => $this->get_entity_base_url(),
            'databaseUrl' => $this->get_database_url(),
        ));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Academy Awards Table', 'academy-awards-table'),
            __('Academy Awards', 'academy-awards-table'),
            'manage_options',
            'academy-awards-table',
            array($this, 'render_admin_page'),
            'dashicons-awards',
            30
        );

        add_submenu_page(
            'academy-awards-table',
            __('Awards Tracker (V2)', 'academy-awards-table'),
            __('Awards Tracker', 'academy-awards-table'),
            'manage_options',
            'academy-awards-tracker',
            array($this, 'render_tracker_admin_page')
        );

        add_submenu_page(
            'academy-awards-table',
            __('Poster Library', 'academy-awards-table'),
            __('Poster Library', 'academy-awards-table'),
            'manage_options',
            'academy-awards-posters',
            array($this, 'render_poster_admin_page')
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'academy_awards';
        $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_winners = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE winner = 1");
        $categories = $wpdb->get_var("SELECT COUNT(DISTINCT canonical_category) FROM $table_name WHERE canonical_category != ''");
        $years = $wpdb->get_var("SELECT COUNT(DISTINCT year) FROM $table_name WHERE year != ''");

        include AAT_PLUGIN_DIR . 'templates/admin-page.php';
    }

    /**
     * Render Tracker V2 admin page
     */
    public function render_tracker_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'academy-awards-table'));
        }

        global $wpdb;
        $awards_table = $wpdb->prefix . 'academy_awards';
        $tracker_table = $wpdb->prefix . 'aat_tracker';

        $ceremonies = $wpdb->get_col("SELECT DISTINCT ceremony FROM $awards_table ORDER BY ceremony DESC");
        $categories = $wpdb->get_col("SELECT DISTINCT canonical_category FROM $awards_table WHERE canonical_category != '' ORDER BY canonical_category ASC");

        $selected_ceremony = isset($_GET['ceremony']) ? intval($_GET['ceremony']) : 0;
        if ($selected_ceremony <= 0) {
            $selected_ceremony = $this->get_max_ceremony();
        }

        $year_label = $this->get_ceremony_year($selected_ceremony);

        $picks = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tracker_table WHERE ceremony = %d ORDER BY canonical_category ASC, tier ASC, rank ASC, updated_at DESC",
                $selected_ceremony
            ),
            ARRAY_A
        );
        if (!is_array($picks)) $picks = array();

        include AAT_PLUGIN_DIR . 'templates/tracker-admin.php';
    }

    /**
     * Render Poster Library admin page
     */
    public function render_poster_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'academy-awards-table'));
        }

        global $wpdb;
        $poster_table = $wpdb->prefix . 'aat_posters';

        $total_posters = intval($wpdb->get_var("SELECT COUNT(*) FROM $poster_table"));
        $rows = $wpdb->get_results("SELECT * FROM $poster_table ORDER BY updated_at DESC LIMIT 500", ARRAY_A);
        if (!is_array($rows)) $rows = array();

        include AAT_PLUGIN_DIR . 'templates/poster-admin.php';
    }


    /**
     * Render shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'class' => '',
            'year' => '',
            'ceremony' => '',
            'winners_only' => 'false',
            // Layout variants: full (default) or embedded (used for hub pages)
            'layout' => 'full',
            'limit' => 0,
        ), $atts, 'academy_awards');

        // Convenience: allow ceremony="latest" / year="latest" so pages auto-update after new imports.
        $cer = strtolower(trim((string) ($atts['ceremony'] ?? '')));
        if ($cer === 'latest' || $cer === 'current') {
            $max = $this->get_max_ceremony();
            if ($max > 0) {
                $atts['ceremony'] = (string) $max;
            }
        }

        $yr = strtolower(trim((string) ($atts['year'] ?? '')));
        if ($yr === 'latest' || $yr === 'current') {
            $latest_year = $this->get_latest_year_label();
            if (!empty($latest_year)) {
                $atts['year'] = $latest_year;
            }
        }

        ob_start();
        include AAT_PLUGIN_DIR . 'templates/table-display.php';
        return ob_get_clean();
    }


    /**
     * Shortcode: Lunara Awards Tracker (Oscar season surface)
     * Usage: [lunara_awards_tracker] or [lunara_awards_tracker ceremony="latest"]
     *
     * This is a curated wrapper around the main database table, intended for your
     * Awards Tracker page. It defaults to the latest ceremony, and uses the embedded
     * layout to fit neatly into your site design.
     */
    public function render_tracker_shortcode($atts) {
        $atts = shortcode_atts(array(
            'ceremony'      => 'latest',
            'year'          => '',
            'layout'        => 'embedded',
            'winners_only'  => 'false',
            'category'      => '',
            'class'         => '',
        ), $atts, 'lunara_awards_tracker');

        // Reuse the main shortcode renderer so we keep one code path.
        return $this->render_shortcode($atts);
    }


    /**
     * Tracker V2 shortcode (Predictions / Locks / Watchlist / Longshots)
     * Usage: [lunara_awards_tracker_v2 ceremony="latest"]
     */
    public function render_tracker_v2_shortcode($atts) {
        $atts = shortcode_atts(array(
            'ceremony' => 'latest',
            'show_selector' => 'true',
            'show_posters' => 'true',
            'show_imdb' => 'true',
            'show_review_links' => 'true',
        ), $atts, 'lunara_awards_tracker_v2');

        $cer = strtolower(trim((string) ($atts['ceremony'] ?? '')));
        $ceremony = 0;
        if ($cer === 'latest' || $cer === 'current' || $cer === '') {
            $ceremony = $this->get_max_ceremony();
        } else {
            $ceremony = intval($atts['ceremony']);
        }
        if ($ceremony <= 0) {
            $ceremony = $this->get_max_ceremony();
        }

        // Allow URL override for easy season switching: ?ceremony=97
        if (isset($_GET['ceremony'])) {
            $q = intval($_GET['ceremony']);
            if ($q > 0) $ceremony = $q;
        }

        $show_selector = ($atts['show_selector'] === 'true' || $atts['show_selector'] === true || $atts['show_selector'] === '1');
        $show_posters = ($atts['show_posters'] === 'true' || $atts['show_posters'] === true || $atts['show_posters'] === '1');
        $show_imdb = ($atts['show_imdb'] === 'true' || $atts['show_imdb'] === true || $atts['show_imdb'] === '1');
        $show_review_links = ($atts['show_review_links'] === 'true' || $atts['show_review_links'] === true || $atts['show_review_links'] === '1');

        $season_label = $this->ordinal($ceremony) . ' Academy Awards';
        $year_label = $this->get_ceremony_year($ceremony);

        $picks = $this->get_tracker_picks($ceremony);

        // Ceremony dropdown options (for selector)
        global $wpdb;
        $awards_table = $wpdb->prefix . 'academy_awards';
        $ceremonies = $wpdb->get_col("SELECT DISTINCT ceremony FROM $awards_table ORDER BY ceremony DESC");
        if (!is_array($ceremonies)) $ceremonies = array();

        ob_start();
        include AAT_PLUGIN_DIR . 'templates/tracker-v2.php';
        return ob_get_clean();
    }

    /**
     * Get tracker picks for a ceremony, grouped by tier and category.
     */
    public function get_tracker_picks($ceremony) {
        global $wpdb;
        $tracker_table = $wpdb->prefix . 'aat_tracker';

        $ceremony = intval($ceremony);
        if ($ceremony <= 0) return array();

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tracker_table WHERE ceremony = %d ORDER BY canonical_category ASC, FIELD(tier,'prediction','lock','watch','longshot') ASC, rank ASC, updated_at DESC",
                $ceremony
            ),
            ARRAY_A
        );
        if (!is_array($rows)) $rows = array();

        $grouped = array(
            'prediction' => array(),
            'lock' => array(),
            'watch' => array(),
            'longshot' => array(),
        );

        foreach ($rows as $r) {
            $tier = isset($r['tier']) ? (string) $r['tier'] : 'watch';
            if (!isset($grouped[$tier])) $tier = 'watch';
            $cat = isset($r['canonical_category']) ? (string) $r['canonical_category'] : '';
            if ($cat === '') continue;
            if (!isset($grouped[$tier][$cat])) $grouped[$tier][$cat] = array();
            $grouped[$tier][$cat][] = $r;
        }

        return $grouped;
    }

    /**
     * Build IMDb URL for an IMDb entity ID.
     */
    public function build_imdb_url($id) {
        $id = trim((string) $id);
        if (preg_match('/^tt\d+$/', $id)) return 'https://www.imdb.com/title/' . $id . '/';
        if (preg_match('/^nm\d+$/', $id)) return 'https://www.imdb.com/name/' . $id . '/';
        if (preg_match('/^co\d+$/', $id)) return 'https://www.imdb.com/company/' . $id . '/';
        return '';
    }

    /**
     * Build internal Lunara entity URL from an IMDb entity ID.
     */
    public function build_entity_url_from_id($id) {
        $id = trim((string) $id);
        if ($id === '') return '';
        $base = $this->get_entity_base_url();
        if (preg_match('/^tt\d+$/', $id)) return esc_url_raw($base . 'title/' . $id . '/');
        if (preg_match('/^nm\d+$/', $id)) return esc_url_raw($base . 'name/' . $id . '/');
        if (preg_match('/^co\d+$/', $id)) return esc_url_raw($base . 'company/' . $id . '/');
        return '';
    }

    /**
     * Poster Library: get attachment ID for a title.
     *
     * Strategy:
     *  1) Prefer the featured image from a linked Lunara review (if present)
     *  2) Fall back to the Poster Library mapping table
     */

    /**
     * Retrieve a TMDB API key from wp-config.php or the stored option.
     */
    public function get_tmdb_api_key() {
        if ( defined('AAT_TMDB_API_KEY') && AAT_TMDB_API_KEY ) {
            return (string) AAT_TMDB_API_KEY;
        }
        $option = get_option('aat_tmdb_api_key', '');
        return is_string($option) ? trim($option) : '';
    }

    /**
     * Fetch and cache TMDB data for an IMDb title id.
     */
    public function get_tmdb_data_for_imdb_id( $imdb_id ) {
        $imdb_id = strtolower( trim( (string) $imdb_id ) );
        if ( ! preg_match('/^tt\d+$/', $imdb_id) ) {
            return array();
        }

        $cache_key = 'aat_tmdb_' . $imdb_id;
        $cached = get_transient( $cache_key );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $key = $this->get_tmdb_api_key();
        if ( $key === '' ) {
            return array();
        }

        $find_url = add_query_arg(
            array(
                'api_key' => $key,
                'external_source' => 'imdb_id',
            ),
            'https://api.themoviedb.org/3/find/' . rawurlencode( $imdb_id )
        );
        $response = wp_remote_get( $find_url, array( 'timeout' => 15 ) );
        if ( is_wp_error( $response ) ) {
            return array();
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $movie = ! empty( $data['movie_results'][0] ) ? $data['movie_results'][0] : array();
        if ( empty( $movie['id'] ) ) {
            set_transient( $cache_key, array(), DAY_IN_SECONDS );
            return array();
        }

        $details_url = add_query_arg(
            array(
                'api_key' => $key,
                'append_to_response' => 'credits',
            ),
            'https://api.themoviedb.org/3/movie/' . intval( $movie['id'] )
        );
        $details_response = wp_remote_get( $details_url, array( 'timeout' => 15 ) );
        if ( is_wp_error( $details_response ) ) {
            return array();
        }

        $details = json_decode( wp_remote_retrieve_body( $details_response ), true );
        if ( ! is_array( $details ) ) {
            $details = array();
        }
        if ( ! empty( $details['poster_path'] ) ) {
            $details['poster_full'] = 'https://image.tmdb.org/t/p/w780' . $details['poster_path'];
        }
        if ( ! empty( $details['backdrop_path'] ) ) {
            $details['backdrop_full'] = 'https://image.tmdb.org/t/p/w1280' . $details['backdrop_path'];
        }
        set_transient( $cache_key, $details, 7 * DAY_IN_SECONDS );
        return $details;
    }

    public function get_poster_attachment_id_for_title($tt) {
        $tt = strtolower(trim((string) $tt));
        if (!preg_match('/^tt\d{7,8}$/', $tt)) return 0;

        // 1) Review featured image
        $review_ids = $this->get_review_ids_for_title_id($tt, 1);
        if (!empty($review_ids)) {
            $rid = (int) $review_ids[0];
            $thumb_id = get_post_thumbnail_id($rid);
            if ($thumb_id) return (int) $thumb_id;
        }

        // 2) Poster mapping table
        global $wpdb;
        $poster_table = $wpdb->prefix . 'aat_posters';
        $aid = $wpdb->get_var($wpdb->prepare("SELECT attachment_id FROM $poster_table WHERE imdb_id = %s", $tt));
        return intval($aid);
    }

    public function get_poster_img_html_for_title($tt, $size = 'medium', $attrs = array()) {
        $aid = $this->get_poster_attachment_id_for_title($tt);
        if (!$aid) return '';
        $defaults = array(
            'loading' => 'lazy',
            'decoding' => 'async',
            'class' => 'aat-poster-img',
        );
        $attrs = is_array($attrs) ? array_merge($defaults, $attrs) : $defaults;
        return wp_get_attachment_image($aid, $size, false, $attrs);
    }

    /**
     * Save/update poster mapping.
     */
    public function set_poster_attachment_id($tt, $attachment_id, $source = '') {
        $tt = strtolower(trim((string) $tt));
        $attachment_id = intval($attachment_id);
        $source = sanitize_text_field($source);

        if (!preg_match('/^tt\d{7,8}$/', $tt)) return false;

        global $wpdb;
        $poster_table = $wpdb->prefix . 'aat_posters';

        $now = current_time('mysql');
        $existing = $wpdb->get_var($wpdb->prepare("SELECT imdb_id FROM $poster_table WHERE imdb_id = %s", $tt));

        if ($existing) {
            $wpdb->update(
                $poster_table,
                array(
                    'attachment_id' => $attachment_id,
                    'source' => $source,
                    'updated_at' => $now,
                ),
                array('imdb_id' => $tt),
                array('%d','%s','%s'),
                array('%s')
            );
        } else {
            $wpdb->insert(
                $poster_table,
                array(
                    'imdb_id' => $tt,
                    'attachment_id' => $attachment_id,
                    'source' => $source,
                    'updated_at' => $now,
                ),
                array('%s','%d','%s','%s')
            );
        }

        return true;
    }

    /**
     * When a review is saved, automatically sync its featured image into the Poster Library.
     */
    public function maybe_sync_poster_from_review($post_id, $post) {
        if (wp_is_post_revision($post_id)) return;
        if (!($post instanceof WP_Post)) return;

        $review_post_type = $this->get_review_post_type();
        if ($post->post_type !== $review_post_type) return;

        if (!current_user_can('edit_post', $post_id)) return;

        $meta_key = $this->get_review_imdb_meta_key();
        $tt = strtolower(trim((string) get_post_meta($post_id, $meta_key, true)));
        if (!preg_match('/^tt\d{7,8}$/', $tt)) return;

        $thumb_id = get_post_thumbnail_id($post_id);
        if (!$thumb_id) return;

        $this->set_poster_attachment_id($tt, (int) $thumb_id, 'review-featured-image');
    }

    /**
     * Bulk-sync posters from existing reviews.
     * Returns array( synced => int, skipped => int )
     */
    public function sync_posters_from_reviews() {
        $meta_key = $this->get_review_imdb_meta_key();
        $post_type = $this->get_review_post_type();

        $q = new WP_Query(array(
            'post_type'              => $post_type,
            'post_status'            => 'publish',
            'posts_per_page'         => 5000,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query'             => array(
                array(
                    'key'     => $meta_key,
                    'compare' => 'EXISTS',
                ),
            ),
        ));

        $synced = 0;
        $skipped = 0;

        if (!empty($q->posts) && is_array($q->posts)) {
            foreach ($q->posts as $pid) {
                $pid = (int) $pid;
                $tt = strtolower(trim((string) get_post_meta($pid, $meta_key, true)));
                if (!preg_match('/^tt\d{7,8}$/', $tt)) { $skipped++; continue; }
                $thumb_id = get_post_thumbnail_id($pid);
                if (!$thumb_id) { $skipped++; continue; }

                $ok = $this->set_poster_attachment_id($tt, (int) $thumb_id, 'bulk-review-sync');
                if ($ok) $synced++; else $skipped++;
            }
        }

        return array('synced' => $synced, 'skipped' => $skipped);
    }

    /**
     * Verify admin AJAX requests.
     */
    private function verify_admin_ajax_request() {
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'aat_admin_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce.'));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
    }

    /**
     * Admin AJAX: entity search (titles + people + companies).
     * Used by Tracker V2 and Poster Library admin screens.
     */
    public function ajax_tracker_search_entities() {
        $this->verify_admin_ajax_request();

        global $wpdb;
        $awards_table = $wpdb->prefix . 'academy_awards';

        $q = isset($_REQUEST['q']) ? sanitize_text_field(wp_unslash($_REQUEST['q'])) : '';
        $q = trim($q);

        if (strlen($q) < 2) {
            wp_send_json_success(array('results' => array()));
        }

        $results = array();
        $seen = array();

        // If the user pasted a raw IMDb ID, return it directly.
        if (preg_match('/^(tt\d{7,8}|nm\d{7,8}|co\d{7,8})$/i', $q, $m)) {
            $id = strtolower($m[1]);
            $type = (strpos($id, 'tt') === 0) ? 'title' : ((strpos($id, 'nm') === 0) ? 'name' : 'company');
            $label = $this->get_entity_display_name($type, $id);
            if ($label === '') $label = strtoupper($id);
            $results[] = array('id' => $id, 'type' => $type, 'label' => $label);
            wp_send_json_success(array('results' => $results));
        }

        $like = '%' . $wpdb->esc_like($q) . '%';

        // Film titles
        $film_rows = $wpdb->get_results(
            $wpdb->prepare("SELECT film, film_id FROM $awards_table WHERE film != '' AND film LIKE %s LIMIT 80", $like),
            ARRAY_A
        );
        if (is_array($film_rows)) {
            foreach ($film_rows as $r) {
                $films = array_filter(array_map('trim', explode('|', (string) ($r['film'] ?? ''))), 'strlen');
                $ids = array_filter(array_map('trim', explode('|', (string) ($r['film_id'] ?? ''))), 'strlen');
                if (empty($films) || empty($ids) || count($films) !== count($ids)) continue;

                foreach ($ids as $i => $id) {
                    $id = strtolower($id);
                    $title = (string) ($films[$i] ?? '');
                    if ($title === '' || !preg_match('/^tt\d+$/', $id)) continue;
                    if (stripos($title, $q) === false) continue;

                    if (isset($seen[$id])) continue;
                    $seen[$id] = true;
                    $results[] = array('id' => $id, 'type' => 'title', 'label' => $title);
                    if (count($results) >= 20) break 2;
                }
            }
        }

        // People / companies in nominee strings
        $name_rows = $wpdb->get_results(
            $wpdb->prepare("SELECT nominees, nominee_ids, name FROM $awards_table WHERE name != '' AND name LIKE %s LIMIT 80", $like),
            ARRAY_A
        );
        if (is_array($name_rows)) {
            foreach ($name_rows as $r) {
                $names = array_filter(array_map('trim', explode('|', (string) ($r['nominees'] ?? ''))), 'strlen');
                $ids = array_filter(array_map('trim', explode('|', (string) ($r['nominee_ids'] ?? ''))), 'strlen');

                if (empty($names) || empty($ids) || count($names) !== count($ids)) {
                    // fallback: try the Name field with first ID (best-effort)
                    $fallback_name = (string) ($r['name'] ?? '');
                    if ($fallback_name !== '' && !empty($ids)) {
                        $id = strtolower((string) $ids[0]);
                        if (preg_match('/^(nm\d+|co\d+)$/', $id) && !isset($seen[$id])) {
                            $seen[$id] = true;
                            $type = (strpos($id, 'nm') === 0) ? 'name' : 'company';
                            $results[] = array('id' => $id, 'type' => $type, 'label' => $fallback_name);
                        }
                    }
                    continue;
                }

                foreach ($ids as $i => $id) {
                    $id = strtolower($id);
                    $label = (string) ($names[$i] ?? '');
                    if ($label === '') continue;
                    if (stripos($label, $q) === false) continue;
                    if (!preg_match('/^(nm\d+|co\d+)$/', $id)) continue;
                    if (isset($seen[$id])) continue;
                    $seen[$id] = true;
                    $type = (strpos($id, 'nm') === 0) ? 'name' : 'company';
                    $results[] = array('id' => $id, 'type' => $type, 'label' => $label);
                    if (count($results) >= 20) break 2;
                }
            }
        }

        wp_send_json_success(array('results' => $results));
    }

    /**
     * Admin AJAX: add/update tracker pick.
     */
    public function ajax_tracker_add_pick() {
        $this->verify_admin_ajax_request();

        // Defensive: on some hosts (and some manual upload/update flows) activate() might not run.
        // Ensure the tracker table exists before we write.
        $this->maybe_upgrade_schema();

        global $wpdb;
        $tracker_table = $wpdb->prefix . 'aat_tracker';

        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tracker_table));
        if ($exists !== $tracker_table) {
            wp_send_json_error(array('message' => 'Tracker table is missing. Please re-activate the plugin or run the schema repair.'));
        }

        $ceremony = isset($_POST['ceremony']) ? intval($_POST['ceremony']) : 0;
        $category = isset($_POST['canonical_category']) ? sanitize_text_field(wp_unslash($_POST['canonical_category'])) : '';
        $tier = isset($_POST['tier']) ? sanitize_text_field(wp_unslash($_POST['tier'])) : 'watch';
        $entity_type = isset($_POST['entity_type']) ? sanitize_text_field(wp_unslash($_POST['entity_type'])) : 'title';
        $entity_id = isset($_POST['entity_id']) ? strtolower(sanitize_text_field(wp_unslash($_POST['entity_id']))) : '';
        $rank = isset($_POST['rank']) ? intval($_POST['rank']) : 1;
        $note = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';

        $allowed_tiers = array('prediction','lock','watch','longshot');
        if (!in_array($tier, $allowed_tiers, true)) $tier = 'watch';

        if ($ceremony <= 0 || $category === '' || $entity_id === '') {
            wp_send_json_error(array('message' => 'Missing required fields.'));
        }

        // Normalize rank
        if ($rank <= 0) $rank = 1;
        if ($rank > 100) $rank = 100;

        // Validate entity id
        if ($entity_type === 'title' && !preg_match('/^tt\d+$/', $entity_id)) $entity_type = 'title';
        if ($entity_type === 'name' && !preg_match('/^nm\d+$/', $entity_id)) $entity_type = 'title';
        if ($entity_type === 'company' && !preg_match('/^co\d+$/', $entity_id)) $entity_type = 'title';

        $now = current_time('mysql');

        $existing_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $tracker_table WHERE ceremony=%d AND canonical_category=%s AND tier=%s AND entity_type=%s AND entity_id=%s",
                $ceremony, $category, $tier, $entity_type, $entity_id
            )
        );

        $result = null;

        if ($existing_id) {
            $result = $wpdb->update(
                $tracker_table,
                array(
                    'rank' => $rank,
                    'note' => $note,
                    'updated_at' => $now,
                ),
                array('id' => intval($existing_id)),
                array('%d','%s','%s'),
                array('%d')
            );
        } else {
            $result = $wpdb->insert(
                $tracker_table,
                array(
                    'ceremony' => $ceremony,
                    'canonical_category' => $category,
                    'entity_type' => $entity_type,
                    'entity_id' => $entity_id,
                    'tier' => $tier,
                    'rank' => $rank,
                    'note' => $note,
                    'created_at' => $now,
                    'updated_at' => $now,
                ),
                array('%d','%s','%s','%s','%s','%d','%s','%s','%s')
            );
        }

        if ($result === false || !empty($wpdb->last_error)) {
            wp_send_json_error(array('message' => 'Database write failed. ' . (defined('WP_DEBUG') && WP_DEBUG ? $wpdb->last_error : '')));
        }

        wp_send_json_success(array('message' => 'Saved.'));
    }

    /**
     * Admin AJAX: delete tracker pick.
     */
    public function ajax_tracker_delete_pick() {
        $this->verify_admin_ajax_request();

        $this->maybe_upgrade_schema();

        global $wpdb;
        $tracker_table = $wpdb->prefix . 'aat_tracker';

        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tracker_table));
        if ($exists !== $tracker_table) {
            wp_send_json_error(array('message' => 'Tracker table is missing.'));
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            wp_send_json_error(array('message' => 'Invalid ID.'));
        }

        $result = $wpdb->delete($tracker_table, array('id' => $id), array('%d'));

        if ($result === false || !empty($wpdb->last_error)) {
            wp_send_json_error(array('message' => 'Delete failed.' . (defined('WP_DEBUG') && WP_DEBUG ? (' ' . $wpdb->last_error) : '')));
        }

        wp_send_json_success(array('message' => 'Deleted.'));
    }

    /**
     * Admin AJAX: save poster mapping.
     */
    public function ajax_posters_save() {
        $this->verify_admin_ajax_request();

        $tt = isset($_POST['imdb_id']) ? strtolower(sanitize_text_field(wp_unslash($_POST['imdb_id']))) : '';
        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
        $source = isset($_POST['source']) ? sanitize_text_field(wp_unslash($_POST['source'])) : 'manual';

        if (!preg_match('/^tt\d{7,8}$/', $tt) || $attachment_id <= 0) {
            wp_send_json_error(array('message' => 'Invalid IMDb ID or attachment.'));
        }

        $this->set_poster_attachment_id($tt, $attachment_id, $source);
        wp_send_json_success(array('message' => 'Poster saved.'));
    }

    public function ajax_posters_delete() {
        $this->verify_admin_ajax_request();

        global $wpdb;
        $poster_table = $wpdb->prefix . 'aat_posters';

        $tt = isset($_POST['imdb_id']) ? strtolower(sanitize_text_field(wp_unslash($_POST['imdb_id']))) : '';
        if (!preg_match('/^tt\d{7,8}$/', $tt)) {
            wp_send_json_error(array('message' => 'Invalid IMDb ID.'));
        }

        $wpdb->delete($poster_table, array('imdb_id' => $tt), array('%s'));
        wp_send_json_success(array('message' => 'Removed.'));
    }

    public function ajax_posters_sync_from_reviews() {
        $this->verify_admin_ajax_request();
        $out = $this->sync_posters_from_reviews();
        wp_send_json_success(array(
            'message' => 'Synced from reviews.',
            'synced' => $out['synced'] ?? 0,
            'skipped' => $out['skipped'] ?? 0,
        ));
    }

    /**
     * Reviews integration
     * We connect Lunara Film reviews (CPT: review) to IMDb title IDs (tt1234567).
     * Store the review film ID in post meta (default: _lunara_imdb_title_id).
     */
    public function get_review_post_type() {
        return apply_filters('aat_review_post_type', 'review');
    }

    public function get_review_imdb_meta_key() {
        return apply_filters('aat_review_imdb_meta_key', '_lunara_imdb_title_id');
    }

    /**
     * Return review post IDs that correspond to a given IMDb title id (tt...).
     */
    public function get_review_ids_for_title_id($tt, $limit = 5) {
        $tt = strtolower(trim((string) $tt));
        if (!preg_match('/^tt\d{7,8}$/', $tt)) {
            return array();
        }

        $limit = max(1, (int) $limit);
        $cache_key = 'aat_review_ids_' . $tt . '_' . $limit;
        $cached = get_transient($cache_key);
        if ($cached !== false && is_array($cached)) {
            return $cached;
        }

        $args = array(
            'post_type'              => $this->get_review_post_type(),
            'post_status'            => 'publish',
            'posts_per_page'         => $limit,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query'             => array(
                array(
                    'key'     => $this->get_review_imdb_meta_key(),
                    'value'   => $tt,
                    'compare' => '=',
                ),
            ),
            'orderby'                => 'date',
            'order'                  => 'DESC',
        );

        $ids = get_posts($args);
        $ids = is_array($ids) ? array_values(array_filter($ids, 'is_numeric')) : array();

        set_transient($cache_key, $ids, 6 * HOUR_IN_SECONDS);
        return $ids;
    }

    /**
     * AJAX: Get awards meta (filter dropdown values + global counts)
     */
    public function ajax_get_awards_meta() {
        // Nonce is optional for public, read-only endpoints (avoids cached-page nonce expiry issues).
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
        if (!empty($nonce) && !wp_verify_nonce($nonce, 'aat_nonce')) {
            // Intentionally allow through (public read-only data).
        }

        // Return cached meta if available.
        $meta_cache_key = 'aat_awards_meta_v1';
        $cached_meta = get_transient($meta_cache_key);
        if ($cached_meta !== false && is_array($cached_meta)) {
            wp_send_json_success($cached_meta);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'academy_awards';

        // Get unique values for filters
        $categories = $wpdb->get_col("SELECT DISTINCT canonical_category FROM $table_name WHERE canonical_category != '' ORDER BY canonical_category ASC");
        $classes = $wpdb->get_col("SELECT DISTINCT class FROM $table_name WHERE class != '' ORDER BY class ASC");
        $years = $wpdb->get_col("SELECT DISTINCT year FROM $table_name ORDER BY ceremony DESC");
        $ceremonies = $wpdb->get_col("SELECT DISTINCT ceremony FROM $table_name ORDER BY ceremony DESC");

        $total_records = intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name"));
        $total_winners = intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE winner = 1"));

        $meta_data = array(
            'categories' => $categories,
            'classes' => $classes,
            'years' => $years,
            'ceremonies' => $ceremonies,
            'totals' => array(
                'records' => $total_records,
                'winners' => $total_winners,
                'categories' => is_array($categories) ? count($categories) : 0,
                'ceremonies' => is_array($ceremonies) ? count($ceremonies) : 0,
            ),
        );

        set_transient($meta_cache_key, $meta_data, 10 * MINUTE_IN_SECONDS);

        wp_send_json_success($meta_data);
    }

    /**
     * Helper: Build WHERE SQL and values for prepared statements.
     */
    private function build_where_sql($wpdb, $category, $class, $year, $ceremony, $winners_only, $global_search, $year_prefix, &$values) {
        $where_clauses = array('1=1');
        $values = array();

        if (!empty($category)) {
            $where_clauses[] = 'canonical_category = %s';
            $values[] = $category;
        }

        if (!empty($class)) {
            $where_clauses[] = 'class = %s';
            $values[] = $class;
        }

        if (!empty($year)) {
            $where_clauses[] = 'year = %s';
            $values[] = $year;
        } elseif (!empty($year_prefix)) {
            // Used by the decade quick filters (e.g. 2020s => "202%")
            $where_clauses[] = 'year LIKE %s';
            $values[] = $wpdb->esc_like($year_prefix) . '%';
        }

        if (!empty($ceremony) && intval($ceremony) > 0) {
            $where_clauses[] = 'ceremony = %d';
            $values[] = intval($ceremony);
        }

        if ($winners_only) {
            $where_clauses[] = 'winner = 1';
        }

        if (!empty($global_search)) {
            $search_term = '%' . $wpdb->esc_like($global_search) . '%';
            $where_clauses[] = '(name LIKE %s OR film LIKE %s OR canonical_category LIKE %s OR category LIKE %s OR nominees LIKE %s OR detail LIKE %s OR note LIKE %s)';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }

        return implode(' AND ', $where_clauses);
    }

    /**
     * AJAX: DataTables server-side endpoint
     *
     * Returns JSON in the format DataTables expects:
     * { draw, recordsTotal, recordsFiltered, data }
     */
    public function ajax_get_awards_datatable() {
        // Nonce is optional for public, read-only endpoints (avoids cached-page nonce expiry issues).
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
        if (!empty($nonce) && !wp_verify_nonce($nonce, 'aat_nonce')) {
            // Intentionally allow through (public read-only data).
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'academy_awards';

        // DataTables paging/search/order
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
        $start = isset($_POST['start']) ? max(0, intval($_POST['start'])) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 25;

        // Guardrails (prevents someone from requesting the whole DB in one request)
        if ($length < 1) {
            $length = 25;
        }
        $length = min($length, 200);

        $global_search = '';
        if (isset($_POST['search']) && is_array($_POST['search']) && isset($_POST['search']['value'])) {
            $global_search = sanitize_text_field(wp_unslash($_POST['search']['value']));
        }

        $order_col_idx = 1;
        $order_dir = 'desc';
        if (isset($_POST['order']) && is_array($_POST['order']) && !empty($_POST['order'][0])) {
            $order_col_idx = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 1;
            $order_dir = isset($_POST['order'][0]['dir']) ? sanitize_text_field(wp_unslash($_POST['order'][0]['dir'])) : 'desc';
        }
        $order_dir = (strtolower($order_dir) === 'asc') ? 'ASC' : 'DESC';

        // Custom UI filters
        $category = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : '';
        $class = isset($_POST['class']) ? sanitize_text_field(wp_unslash($_POST['class'])) : '';
        $year = isset($_POST['year']) ? sanitize_text_field(wp_unslash($_POST['year'])) : '';
        $ceremony = isset($_POST['ceremony']) ? intval($_POST['ceremony']) : 0;
        $winners_only = isset($_POST['winners_only']) && wp_unslash($_POST['winners_only']) === 'true';

        // Column search (used by decade quick-filters via column search on the Year column)
        $year_prefix = '';
        if (empty($year) && isset($_POST['columns']) && is_array($_POST['columns']) && isset($_POST['columns'][2]) && isset($_POST['columns'][2]['search']) && is_array($_POST['columns'][2]['search'])) {
            $year_prefix = isset($_POST['columns'][2]['search']['value']) ? sanitize_text_field(wp_unslash($_POST['columns'][2]['search']['value'])) : '';
        }

        // Map DataTables column indexes to database columns for ordering.
        // Indexes correspond to the front-end column definitions:
        // 0 control, 1 ceremony, 2 year, 3 category, 4 nominee, 5 film, 6 status
        $order_map = array(
            1 => 'ceremony',
            2 => 'year',
            3 => 'canonical_category',
            4 => 'name',
            5 => 'film',
            6 => 'winner',
        );
        $order_col = isset($order_map[$order_col_idx]) ? $order_map[$order_col_idx] : 'ceremony';

        // WHERE
        $values = array();
        $where_sql = $this->build_where_sql($wpdb, $category, $class, $year, $ceremony, $winners_only, $global_search, $year_prefix, $values);

        // counts
        $records_total_key = 'aat_records_total_v1';
        $records_total = get_transient($records_total_key);
        if ($records_total === false) {
            $records_total = intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name"));
            set_transient($records_total_key, $records_total, 5 * MINUTE_IN_SECONDS);
        }
        $records_total = intval($records_total);

        $count_filtered_sql = "SELECT COUNT(*) FROM $table_name WHERE $where_sql";
        if (!empty($values)) {
            $count_filtered_sql = $wpdb->prepare($count_filtered_sql, $values);
        }
        $records_filtered = intval($wpdb->get_var($count_filtered_sql));

        // winners within filtered set (used for the stats bar)
        $winners_filtered_sql = "SELECT COUNT(*) FROM $table_name WHERE $where_sql AND winner = 1";
        if (!empty($values)) {
            $winners_filtered_sql = $wpdb->prepare($winners_filtered_sql, $values);
        }
        $winners_filtered = intval($wpdb->get_var($winners_filtered_sql));

        // Data
        $fields = 'ceremony, year, class, canonical_category, category, film, film_id, name, nominees, nominee_ids, winner, detail, note, citation';

        // Stable ordering: whatever the user chooses, keep consistent tie-breakers.
        $order_sql = "$order_col $order_dir, ceremony DESC, canonical_category ASC, winner DESC, id DESC";

        $data_sql = "SELECT $fields FROM $table_name WHERE $where_sql ORDER BY $order_sql LIMIT %d OFFSET %d";
        $data_values = array_merge($values, array($length, $start));
        $data_sql = $wpdb->prepare($data_sql, $data_values);

        $rows = $wpdb->get_results($data_sql, ARRAY_A);
        if (!is_array($rows)) {
            $rows = array();
        }

        // Add stable, URL-safe slugs for hub page linking (Category pages).
        // Keeps the front-end simple and ensures our slugs match WordPress sanitize_title().
        foreach ($rows as $i => $row) {
            $row = $this->apply_row_hotfixes($row);
            $cat = isset($row['canonical_category']) ? (string) $row['canonical_category'] : '';
            $row['category_slug'] = $cat ? sanitize_title($cat) : '';
            $rows[$i] = $row;
        }


        // OPTIONAL: Attach Lunara review URLs (if you have a review that matches this film's IMDb title id).
        // We keep this lightweight and only add a single URL (newest review) per film.
        $tt_ids = array();
        foreach ($rows as $row) {
            $film_ids_raw = isset($row['film_id']) ? (string) $row['film_id'] : '';
            if ($film_ids_raw === '') continue;
            $ids = array_filter(array_map('trim', explode('|', $film_ids_raw)), 'strlen');
            foreach ($ids as $fid) {
                $fid = strtolower($fid);
                if (preg_match('/^tt\d{7,8}$/', $fid)) {
                    $tt_ids[$fid] = true;
                }
            }
        }

        $review_map = array();
        if (!empty($tt_ids)) {
            $meta_key = $this->get_review_imdb_meta_key();
            $post_type = $this->get_review_post_type();

            $q = new WP_Query(array(
                'post_type'              => $post_type,
                'post_status'            => 'publish',
                'posts_per_page'         => 100, // safety cap; only relevant for the current page of results
                'fields'                 => 'ids',
                'no_found_rows'          => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => false,
                'orderby'                => 'date',
                'order'                  => 'DESC',
                'meta_query'             => array(
                    array(
                        'key'     => $meta_key,
                        'value'   => array_keys($tt_ids),
                        'compare' => 'IN',
                    ),
                ),
            ));

            if (!empty($q->posts) && is_array($q->posts)) {
                update_postmeta_cache($q->posts);
                foreach ($q->posts as $pid) {
                    $pid = (int) $pid;
                    $tt = strtolower(trim((string) get_post_meta($pid, $meta_key, true)));
                    if ($tt && !isset($review_map[$tt])) {
                        $review_map[$tt] = get_permalink($pid);
                    }
                }
            }
        }

        foreach ($rows as $i => $row) {
            $rows[$i]['review_url'] = '';
            $film_ids_raw = isset($row['film_id']) ? (string) $row['film_id'] : '';
            if ($film_ids_raw === '') continue;
            $ids = array_filter(array_map('trim', explode('|', $film_ids_raw)), 'strlen');
            foreach ($ids as $fid) {
                $fid = strtolower($fid);
                if (isset($review_map[$fid])) {
                    $rows[$i]['review_url'] = (string) $review_map[$fid];
                    break;
                }
            }
        }

        // DataTables expects a bare JSON object, not a wp_send_json_success wrapper.
        wp_send_json(array(
            'draw' => $draw,
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered,
            'data' => $rows,
            'stats' => array(
                'filtered_total' => $records_filtered,
                'filtered_winners' => $winners_filtered,
            ),
        ));
    }

    /**
     * AJAX: Get awards data (legacy, non-server-side)
     */
    public function ajax_get_awards_data() {
        // Nonce is optional for public, read-only endpoints (avoids cached-page nonce expiry issues).
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
        if (!empty($nonce) && !wp_verify_nonce($nonce, 'aat_nonce')) {
            // Intentionally allow through (public read-only data).
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'academy_awards';

        // Get filter parameters
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $class = isset($_POST['class']) ? sanitize_text_field($_POST['class']) : '';
        $year = isset($_POST['year']) ? sanitize_text_field($_POST['year']) : '';
        $ceremony = isset($_POST['ceremony']) ? intval($_POST['ceremony']) : 0;
        $winners_only = isset($_POST['winners_only']) && $_POST['winners_only'] === 'true';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        // Build query
        $where_clauses = array('1=1');
        $values = array();

        if (!empty($category)) {
            $where_clauses[] = 'canonical_category = %s';
            $values[] = $category;
        }

        if (!empty($class)) {
            $where_clauses[] = 'class = %s';
            $values[] = $class;
        }

        if (!empty($year)) {
            $where_clauses[] = 'year = %s';
            $values[] = $year;
        }

        if ($ceremony > 0) {
            $where_clauses[] = 'ceremony = %d';
            $values[] = $ceremony;
        }

        if ($winners_only) {
            $where_clauses[] = 'winner = 1';
        }

        if (!empty($search)) {
            $where_clauses[] = '(name LIKE %s OR film LIKE %s OR category LIKE %s OR nominees LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }

        $where = implode(' AND ', $where_clauses);

        // Only return the fields we need for the front-end table.
        $query = "SELECT ceremony, year, class, canonical_category, category, film, film_id, name, nominee_ids, winner, detail, note, citation FROM $table_name WHERE $where ORDER BY ceremony DESC, canonical_category ASC, winner DESC";

        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }

        $results = $wpdb->get_results($query, ARRAY_A);

        // Filter values are now served by the dedicated ajax_get_awards_meta() endpoint.
        wp_send_json_success(array(
            'data' => $results,
            'categories' => array(),
            'classes' => array(),
            'years' => array(),
            'ceremonies' => array(),
            'total' => count($results),
        ));
    }

    /**
     * AJAX: Import data from CSV/JSON
     */
    
/**
 * Import Oscars data from an uploaded CSV/TSV or JSON file.
 *
 * This endpoint does a full replace (TRUNCATE + import).
 * For the most reliable workflow, prefer the bundled importer for the full dataset,
 * and use the "Quick Ceremony Update" delta importer for new nominations/winners.
 */
public function ajax_import_data() {
    $this->verify_admin_ajax_request();

    $upload = null;
    if (!empty($_FILES['import_file']) && isset($_FILES['import_file']['tmp_name'])) {
        $upload = $_FILES['import_file'];
    } elseif (!empty($_FILES['file']) && isset($_FILES['file']['tmp_name'])) {
        $upload = $_FILES['file'];
    }

    if (!$upload) {
        wp_send_json_error(array('message' => 'No file uploaded.'));
    }

    if (!empty($upload['error'])) {
        wp_send_json_error(array('message' => 'Upload error: ' . (int) $upload['error']));
    }

    $tmp_path = $upload['tmp_name'];
    $filename = isset($upload['name']) ? (string) $upload['name'] : '';
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    global $wpdb;
    $table_name = $this->get_table_name();
    $this->maybe_upgrade_schema();

    // Full replace import
    $wpdb->query("TRUNCATE TABLE $table_name");

    // Invalidate performance caches
    delete_transient('aat_records_total_v1');
    delete_transient('aat_awards_meta_v1');

    // JSON import (array of objects)
    if ($ext === 'json') {
        $raw = @file_get_contents($tmp_path);
        $rows = json_decode($raw, true);

        if (!is_array($rows)) {
            wp_send_json_error(array('message' => 'Invalid JSON file.'));
        }

        $imported = 0;
        $errors = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                $errors++;
                continue;
            }

            $result = $wpdb->insert(
                $table_name,
                array(
                    'ceremony' => isset($row['Ceremony']) ? intval($row['Ceremony']) : 0,
                    'year' => isset($row['Year']) ? intval($row['Year']) : 0,
                    'class' => isset($row['Class']) ? sanitize_text_field($row['Class']) : '',
                    'canonical_category' => isset($row['CanonicalCategory']) ? sanitize_text_field($row['CanonicalCategory']) : '',
                    'category' => isset($row['Category']) ? sanitize_text_field($row['Category']) : '',
                    'film' => isset($row['Film']) ? sanitize_text_field($row['Film']) : '',
                    'film_id' => isset($row['FilmId']) ? sanitize_text_field($row['FilmId']) : '',
                    'name' => isset($row['Name']) ? sanitize_text_field($row['Name']) : '',
                    'nominees' => isset($row['Nominees']) ? sanitize_textarea_field($row['Nominees']) : '',
                    'nominee_ids' => isset($row['NomineeIds']) ? sanitize_textarea_field($row['NomineeIds']) : '',
                    'winner' => !empty($row['Winner']) ? 1 : 0,
                    'detail' => isset($row['Detail']) ? sanitize_textarea_field($row['Detail']) : '',
                    'note' => isset($row['Note']) ? sanitize_textarea_field($row['Note']) : '',
                    'citation' => isset($row['Citation']) ? sanitize_textarea_field($row['Citation']) : '',
                )
            );

            if ($result === false) {
                $errors++;
                continue;
            }

            $imported++;
        }

        wp_send_json_success(array(
            'imported' => $imported,
            'errors' => $errors,
        ));
    }

    // CSV/TSV import
    $sample = @file_get_contents($tmp_path, false, null, 0, 4096);
    $delimiter = (is_string($sample) && strpos($sample, "\t") !== false) ? "\t" : ",";

    try {
        $sf = new SplFileObject($tmp_path, 'r');
        $sf->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $sf->setCsvControl($delimiter);

        $header = $sf->fgetcsv();
        if (!is_array($header) || count($header) < 3) {
            wp_send_json_error(array('message' => 'Could not read header row. Please check the file format.'));
        }

        $header = array_map('trim', $header);

        $required = array('Ceremony', 'Year', 'Class', 'CanonicalCategory', 'Category', 'Film', 'Name', 'Winner');
        $missing = array();
        foreach ($required as $req) {
            if (!in_array($req, $header, true)) {
                $missing[] = $req;
            }
        }
        if (!empty($missing)) {
            wp_send_json_error(array(
                'message' => 'Missing required columns: ' . implode(', ', $missing) . '.'
            ));
        }

        $imported = 0;
        $errors = 0;

        while (!$sf->eof()) {
            $row = $sf->fgetcsv();

            if ($row === false || $row === null) {
                continue;
            }

            // fgetcsv can return [null] at EOF
            if (is_array($row) && count($row) === 1 && $row[0] === null) {
                continue;
            }

            if (count($row) !== count($header)) {
                $errors++;
                continue;
            }

            $data = array_combine($header, $row);
            if (!is_array($data)) {
                $errors++;
                continue;
            }

            $winner_val = isset($data['Winner']) ? strtolower(trim((string) $data['Winner'])) : '';
            $winner_bool = ($winner_val === '1' || $winner_val === 'true' || $winner_val === 'yes');

            $result = $wpdb->insert(
                $table_name,
                array(
                    'ceremony' => isset($data['Ceremony']) ? intval($data['Ceremony']) : 0,
                    'year' => isset($data['Year']) ? intval($data['Year']) : 0,
                    'class' => isset($data['Class']) ? sanitize_text_field($data['Class']) : '',
                    'canonical_category' => isset($data['CanonicalCategory']) ? sanitize_text_field($data['CanonicalCategory']) : '',
                    'category' => isset($data['Category']) ? sanitize_text_field($data['Category']) : '',
                    'film' => isset($data['Film']) ? sanitize_text_field($data['Film']) : '',
                    'film_id' => isset($data['FilmId']) ? sanitize_text_field($data['FilmId']) : '',
                    'name' => isset($data['Name']) ? sanitize_text_field($data['Name']) : '',
                    'nominees' => isset($data['Nominees']) ? sanitize_textarea_field($data['Nominees']) : '',
                    'nominee_ids' => isset($data['NomineeIds']) ? sanitize_textarea_field($data['NomineeIds']) : '',
                    'winner' => $winner_bool ? 1 : 0,
                    'detail' => isset($data['Detail']) ? sanitize_textarea_field($data['Detail']) : '',
                    'note' => isset($data['Note']) ? sanitize_textarea_field($data['Note']) : '',
                    'citation' => isset($data['Citation']) ? sanitize_textarea_field($data['Citation']) : '',
                )
            );

            if ($result === false) {
                $errors++;
                continue;
            }

            $imported++;
        }

        wp_send_json_success(array(
            'imported' => $imported,
            'errors' => $errors,
        ));
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Import failed: ' . $e->getMessage()));
    }
}

/**
 * Delta import: replace exactly one ceremony using an uploaded TSV/CSV file.
 *
 * This is the most reliable way to ingest new nominations/winners without re-importing full history.
 */
public function ajax_import_ceremony_delta() {
    $this->verify_admin_ajax_request();

    if (empty($_FILES['delta_file']) || !isset($_FILES['delta_file']['tmp_name'])) {
        wp_send_json_error(array('message' => 'No file uploaded.'));
    }

    $file = $_FILES['delta_file'];

    if (!empty($file['error'])) {
        wp_send_json_error(array('message' => 'Upload error: ' . (int) $file['error']));
    }

    $tmp_path = $file['tmp_name'];

    $sample = @file_get_contents($tmp_path, false, null, 0, 4096);
    $delimiter = (is_string($sample) && strpos($sample, "\t") !== false) ? "\t" : ",";

    global $wpdb;
    $table_name = $this->get_table_name();
    $this->maybe_upgrade_schema();

    try {
        $sf = new SplFileObject($tmp_path, 'r');
        $sf->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $sf->setCsvControl($delimiter);

        $header = $sf->fgetcsv();
        if (!is_array($header) || count($header) < 3) {
            wp_send_json_error(array('message' => 'Could not read header row. Please check the file format.'));
        }
        $header = array_map('trim', $header);

        $required = array('Ceremony', 'Year', 'Class', 'CanonicalCategory', 'Category', 'Film', 'Name', 'Winner');
        $missing = array();
         foreach ($required as $req) {
            if (!in_array($req, $header, true)) {
                $missing[] = $req;
            }
        }
        if (!empty($missing)) {
            wp_send_json_error(array(
                'message' => 'Missing required columns: ' . implode(', ', $missing) . '.'
            ));
        }

        $ceremony_set = array();
        $rows = array();
        $skipped = 0;

        while (!$sf->eof()) {
            $row = $sf->fgetcsv();

            if ($row === false || $row === null) {
                continue;
            }

            if (is_array($row) && count($row) === 1 && $row[0] === null) {
                continue;
            }

            if (count($row) !== count($header)) {
                $skipped++;
                continue;
            }

            $data = array_combine($header, $row);
            if (!is_array($data)) {
                $skipped++;
                continue;
            }

            $cer = isset($data['Ceremony']) ? intval($data['Ceremony']) : 0;
            if ($cer <= 0) {
                $skipped++;
                continue;
            }

            $ceremony_set[$cer] = true;
            $rows[] = $data;
        }

        $ceremonies = array_keys($ceremony_set);
        if (count($ceremonies) !== 1) {
            wp_send_json_error(array(
                'message' => 'Delta import expects exactly one Ceremony in the file. Found: ' . count($ceremonies) . '. If you have multiple ceremonies, import them one at a time (recommended).'
            ));
        }
        $ceremony = intval($ceremonies[0]);

        // Replace that ceremony only
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE ceremony = %d", $ceremony));

        // Invalidate performance caches
        delete_transient('aat_records_total_v1');
        delete_transient('aat_awards_meta_v1');

        $imported = 0;
        $errors = 0;

        foreach ($rows as $data) {
            $winner_val = isset($data['Winner']) ? strtolower(trim((string) $data['Winner'])) : '';
            $winner_bool = ($winner_val === '1' || $winner_val === 'true' || $winner_val === 'yes');

            $result = $wpdb->insert(
                $table_name,
                array(
                    'ceremony' => isset($data['Ceremony']) ? intval($data['Ceremony']) : 0,
                    'year' => isset($data['Year']) ? intval($data['Year']) : 0,
                    'class' => isset($data['Class']) ? sanitize_text_field($data['Class']) : '',
                    'canonical_category' => isset($data['CanonicalCategory']) ? sanitize_text_field($data['CanonicalCategory']) : '',
                    'category' => isset($data['Category']) ? sanitize_text_field($data['Category']) : '',
                    'film' => isset($data['Film']) ? sanitize_text_field($data['Film']) : '',
                    'film_id' => isset($data['FilmId']) ? sanitize_text_field($data['FilmId']) : '',
                    'name' => isset($data['Name']) ? sanitize_text_field($data['Name']) : '',
                    'nominees' => isset($data['Nominees']) ? sanitize_textarea_field($data['Nominees']) : '',
                    'nominee_ids' => isset($data['NomineeIds']) ? sanitize_textarea_field($data['NomineeIds']) : '',
                    'winner' => $winner_bool ? 1 : 0,
                    'detail' => isset($data['Detail']) ? sanitize_textarea_field($data['Detail']) : '',
                    'note' => isset($data['Note']) ? sanitize_textarea_field($data['Note']) : '',
                    'citation' => isset($data['Citation']) ? sanitize_textarea_field($data['Citation']) : '',
                )
            );

            if ($result === false) {
                $errors++;
                continue;
            }

            $imported++;
        }

        wp_send_json_success(array(
            'ceremony' => $ceremony,
            'imported' => $imported,
            'errors' => $errors,
            'skipped' => $skipped,
        ));
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Delta import failed: ' . $e->getMessage()));
    }
}

/**
 * Repair tables and rewrite rules.
 */
public function ajax_repair_schema() {
    $this->verify_admin_ajax_request();

    $this->maybe_upgrade_schema();
    $this->register_rewrite_rules();
    flush_rewrite_rules();

    wp_send_json_success(array(
        'message' => 'Schema and rewrite rules repaired.'
    ));
}

public function ajax_import_bundled_data() {
        check_ajax_referer('aat_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        if (!file_exists(AAT_BUNDLED_CSV_PATH)) {
            wp_send_json_error('Bundled oscars.csv not found.');
        }

        @set_time_limit(0);

        global $wpdb;
        $table_name = $wpdb->prefix . 'academy_awards';

        $chunk_size = apply_filters('aat_bundled_import_chunk_size', 500);
        $offset = isset($_POST['offset']) ? max(0, intval($_POST['offset'])) : 0;

        // Compute total rows (excluding header) once.
        $total_rows = intval(get_option('aat_bundled_total_rows', 0));
        if ($total_rows <= 0 || $offset === 0) {
            $f = new SplFileObject(AAT_BUNDLED_CSV_PATH, 'r');
            $f->seek(PHP_INT_MAX);
            // With a header at line 0, the last line index equals the number of data rows.
            $total_rows = max(0, intval($f->key()));
            update_option('aat_bundled_total_rows', $total_rows, false);
        }

        // First chunk: start clean.
        if ($offset === 0) {
            $wpdb->query("TRUNCATE TABLE $table_name");
        }

        $file = new SplFileObject(AAT_BUNDLED_CSV_PATH, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl("\t");

        // Read header
        $file->rewind();
        $headers = $file->fgetcsv();
        if (!is_array($headers) || empty($headers)) {
            wp_send_json_error('Bundled CSV header could not be read.');
        }
        $headers = array_map('trim', $headers);
        // Strip UTF-8 BOM from the first header field if present
        $headers[0] = preg_replace('/^\x{FEFF}/u', '', $headers[0]);
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

        // Seek to the correct line (header is line 0)
        $seek_line = $offset + 1;
        $file->seek($seek_line);

        $imported = 0;
        $errors = 0;
        $processed = 0;

        // Collect rows for batch INSERT
        $batch_rows = array();
        $db_columns = array('ceremony','year','class','canonical_category','category','film','film_id','name','nominees','nominee_ids','winner','detail','note','citation');

        for ($i = 0; $i < $chunk_size && !$file->eof(); $i++) {
            $row_values = $file->current();
            $file->next();
            $processed++;

            if (!is_array($row_values)) {
                continue;
            }

            // Handle empty trailing lines
            if (count($row_values) === 1 && trim((string) $row_values[0]) === '') {
                continue;
            }

            // Normalize column count.
            if (count($row_values) < count($headers)) {
                $row_values = array_pad($row_values, count($headers), '');
            } elseif (count($row_values) > count($headers)) {
                // If extra columns exist, merge them into the final field.
                $fixed = array_slice($row_values, 0, count($headers) - 1);
                $fixed[] = implode("\t", array_slice($row_values, count($headers) - 1));
                $row_values = $fixed;
            }

            $row = array_combine($headers, $row_values);
            if (!$row) {
                $errors++;
                continue;
            }

            $winner_raw = isset($row['Winner']) ? trim((string) $row['Winner']) : '';
            $winner = (!empty($winner_raw) && (strtolower($winner_raw) === 'true' || $winner_raw === '1')) ? 1 : 0;

            $batch_rows[] = array(
                intval($row['Ceremony'] ?? 0),
                sanitize_text_field($row['Year'] ?? ''),
                sanitize_text_field($row['Class'] ?? ''),
                sanitize_text_field($row['CanonicalCategory'] ?? ''),
                sanitize_text_field($row['Category'] ?? ''),
                sanitize_text_field($row['Film'] ?? ''),
                sanitize_text_field($row['FilmId'] ?? ''),
                sanitize_text_field($row['Name'] ?? ''),
                sanitize_text_field($row['Nominees'] ?? ''),
                sanitize_text_field($row['NomineeIds'] ?? ''),
                $winner,
                sanitize_textarea_field($row['Detail'] ?? ''),
                sanitize_textarea_field($row['Note'] ?? ''),
                sanitize_textarea_field($row['Citation'] ?? ''),
            );
        }

        // Batch INSERT in groups of 50 for efficiency
        $batch_insert_size = 50;
        $col_list = '`' . implode('`,`', $db_columns) . '`';

        for ($b = 0; $b < count($batch_rows); $b += $batch_insert_size) {
            $slice = array_slice($batch_rows, $b, $batch_insert_size);
            $placeholders = array();
            $values = array();

            foreach ($slice as $r) {
                $placeholders[] = '(%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,%d,%s,%s,%s)';
                foreach ($r as $v) {
                    $values[] = $v;
                }
            }

            $sql = "INSERT INTO $table_name ($col_list) VALUES " . implode(',', $placeholders);
            $result = $wpdb->query($wpdb->prepare($sql, $values));

            if ($result === false) {
                $errors += count($slice);
            } else {
                $imported += count($slice);
            }
        }

        $new_offset = $offset + $processed;
        $done = ($new_offset >= $total_rows);

        if ($done) {
            // Invalidate performance caches
            delete_transient('aat_records_total_v1');
            delete_transient('aat_awards_meta_v1');

            // Confirm how many rows actually ended up in the DB.
            $inserted_total = intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name"));
            $message = sprintf(
                __('Bundled import complete: %1$d rows now in the database. (%2$d rows processed; %3$d insert errors in the final batch.)', 'academy-awards-table'),
                $inserted_total,
                $new_offset,
                $errors
            );
        } else {
            $message = sprintf(__('Importing… %d of %d rows processed.', 'academy-awards-table'), $new_offset, $total_rows);
        }

        wp_send_json_success(array(
            'imported' => $imported,
            'errors' => $errors,
            'offset' => $new_offset,
            'total_rows' => $total_rows,
            'done' => $done,
            'message' => $message,
        ));
    }


    /**
     * Dedicated routed-page frame.
     * For block themes, render the header/footer template parts directly so
     * Oscar routes do not inherit constrained post-content wrappers.
     */
    public function render_route_template_start($extra_body_classes = array()) {
        if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
            if (!defined('AAT_ROUTE_TEMPLATE_ACTIVE')) {
                define('AAT_ROUTE_TEMPLATE_ACTIVE', true);
            }
            ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(array_merge(array('aat-route-template-body'), (array) $extra_body_classes)); ?>>
<?php wp_body_open(); ?>
<div class="aat-route-template aat-route-template--block">
    <?php
    if (function_exists('block_template_part')) {
        block_template_part('header');
    } else {
        get_header();
    }
    ?>
    <main id="primary" class="aat-route-main" role="main">
<?php
            return;
        }

        get_header();
        echo '<main id="primary" class="aat-route-main" role="main">';
    }

    /**
     * Close dedicated routed-page frame.
     */
    public function render_route_template_end() {
        echo '</main>';

        if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
            if (function_exists('block_template_part')) {
                block_template_part('footer');
            } else {
                get_footer();
            }
            echo '</div>';
            wp_footer();
            echo '</body></html>';
            return;
        }

        get_footer();
    }

    /**
     * AJAX: Clear all data
     */
    public function ajax_clear_data() {
        check_ajax_referer('aat_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'academy_awards';
        $wpdb->query("TRUNCATE TABLE $table_name");

        // Invalidate performance caches
        delete_transient('aat_records_total_v1');
        delete_transient('aat_awards_meta_v1');

        wp_send_json_success(array('message' => 'All data cleared.'));
    }
}

// Initialize plugin
Academy_Awards_Table::get_instance();
