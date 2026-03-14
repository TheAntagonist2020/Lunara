<?php
/**
 * Academy Awards Table - Hub Page Template
 *
 * Routes:
 *   /{base}/ceremonies/
 *   /{base}/categories/
 *   /{base}/about/
 *   /{base}/ceremony/{N}/
 *   /{base}/category/{slug}/
 */

if (!defined('ABSPATH')) {
    exit;
}

$aat = Academy_Awards_Table::get_instance();
$hub = sanitize_text_field(get_query_var('aat_hub'));
$hub_id = sanitize_text_field(get_query_var('aat_hub_id'));

global $wpdb;
$table_name = $wpdb->prefix . 'academy_awards';

// Common dynamic scope values
$total_records = intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name"));
$total_winners = intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE winner = 1"));
$total_categories = intval($wpdb->get_var("SELECT COUNT(DISTINCT canonical_category) FROM $table_name WHERE canonical_category != ''"));
$total_ceremonies = intval($wpdb->get_var("SELECT COUNT(DISTINCT ceremony) FROM $table_name"));
$min_ceremony = intval($wpdb->get_var("SELECT MIN(ceremony) FROM $table_name"));
$max_ceremony = intval($wpdb->get_var("SELECT MAX(ceremony) FROM $table_name"));
$span = '';
if ($min_ceremony > 0 && $max_ceremony > 0) {
    $first_year = $aat->get_ceremony_year($min_ceremony);
    $last_year = $aat->get_ceremony_year($max_ceremony);
    if ($first_year && $last_year) {
        $span = $first_year . '–' . $last_year;
    }
}

// Helper: mark 404 and show friendly page
$mark_404 = function() {
    global $wp_query;
    if (is_object($wp_query)) {
        $wp_query->set_404();
    }
    status_header(404);
    nocache_headers();
};

$db_url = $aat->get_database_url();

// Optional: if the site owner created WordPress pages for these hubs (as recommended),
// pull their editor content in as the intro copy so they can control tone/voice.
$wp_hub_page = null;
$wp_hub_content = '';
if (in_array($hub, array('ceremonies','categories','about'), true)) {
    $wp_hub_page = $aat->get_hub_page_post($hub);
    if ($wp_hub_page instanceof WP_Post) {
        $wp_hub_content = trim((string) $wp_hub_page->post_content);
    }
}

$aat->render_route_template_start(array('aat-route-template', 'aat-route-template--hub'));
?>
<div class="aat-route-shell alignfull">
<?php
?>

<div class="aat-container aat-hub-page">

    <p class="aat-hub-breadcrumbs">
        <a href="<?php echo esc_url($db_url); ?>"><?php echo esc_html__('Academy Awards Database', 'academy-awards-table'); ?></a>
        <span class="aat-footer-sep">›</span>
        <?php echo esc_html(ucfirst($hub)); ?>
        <?php if (!empty($hub_id)) : ?>
            <span class="aat-footer-sep">›</span>
            <?php echo esc_html($hub_id); ?>
        <?php endif; ?>
    </p>

    <?php
        // CEREMONIES INDEX
        if ($hub === 'ceremonies') :
            $rows = $wpdb->get_results(
                "SELECT ceremony, MIN(year) AS year_label FROM $table_name GROUP BY ceremony ORDER BY ceremony DESC",
                ARRAY_A
            );
    ?>
        <div class="aat-hub-header">
            <h1 class="aat-hub-title"><?php echo esc_html__('Ceremonies', 'academy-awards-table'); ?></h1>
            <p class="aat-hub-subtitle"><?php echo esc_html__('Explore every Academy Awards ceremony in the Lunara database.', 'academy-awards-table'); ?></p>

            <?php if ($wp_hub_page instanceof WP_Post && $wp_hub_content !== '') : ?>
                <div class="aat-hub-wp-content">
                    <?php echo apply_filters('the_content', $wp_hub_page->post_content); ?>
                </div>
            <?php endif; ?>

            <div class="aat-hub-actions">
                <a class="aat-btn aat-btn-secondary" href="<?php echo esc_url($aat->get_categories_index_url()); ?>"><?php echo esc_html__('Browse Categories', 'academy-awards-table'); ?></a>
                <a class="aat-btn aat-btn-primary" href="<?php echo esc_url($db_url); ?>"><?php echo esc_html__('Open Full Database', 'academy-awards-table'); ?></a>
            </div>
        </div>

        <div class="aat-stats-bar aat-entity-stats">
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_ceremonies)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Ceremonies', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_records)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Nominations', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_winners)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Wins', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_categories)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Categories', 'academy-awards-table'); ?></span></div>
        </div>

        <div class="aat-hub-grid">
            <?php if (!empty($rows)) : foreach ($rows as $r) :
                $c = intval($r['ceremony'] ?? 0);
                if ($c <= 0) continue;
                $year_label = (string) ($r['year_label'] ?? '');
                $url = $aat->get_ceremony_url($c);
            ?>
                <a class="aat-hub-card" href="<?php echo esc_url($url); ?>">
                    <h3 class="aat-hub-card-title"><?php echo esc_html($aat->ordinal($c)); ?> <?php echo esc_html__('Academy Awards', 'academy-awards-table'); ?></h3>
                    <p class="aat-hub-card-meta"><?php echo esc_html($year_label); ?></p>
                </a>
            <?php endforeach; endif; ?>
        </div>

    <?php
        // CATEGORIES INDEX
        elseif ($hub === 'categories') :
            $cats = $wpdb->get_results(
                "SELECT canonical_category, MIN(class) AS class_label FROM $table_name WHERE canonical_category != '' GROUP BY canonical_category ORDER BY MIN(class) ASC, canonical_category ASC",
                ARRAY_A
            );
            $grouped = array();
            if (is_array($cats)) {
                foreach ($cats as $r) {
                    $cat = (string) ($r['canonical_category'] ?? '');
                    if ($cat === '') continue;
                    $cls = (string) ($r['class_label'] ?? '');
                    if ($cls === '') $cls = 'Other';
                    if (!isset($grouped[$cls])) $grouped[$cls] = array();
                    $grouped[$cls][] = $cat;
                }
            }
            ksort($grouped);
    ?>
        <div class="aat-hub-header">
            <h1 class="aat-hub-title"><?php echo esc_html__('Categories', 'academy-awards-table'); ?></h1>
            <p class="aat-hub-subtitle"><?php echo esc_html__('Browse every canonical Oscar category in the Lunara database.', 'academy-awards-table'); ?></p>

            <?php if ($wp_hub_page instanceof WP_Post && $wp_hub_content !== '') : ?>
                <div class="aat-hub-wp-content">
                    <?php echo apply_filters('the_content', $wp_hub_page->post_content); ?>
                </div>
            <?php endif; ?>

            <div class="aat-hub-actions">
                <a class="aat-btn aat-btn-secondary" href="<?php echo esc_url($aat->get_ceremonies_index_url()); ?>"><?php echo esc_html__('Browse Ceremonies', 'academy-awards-table'); ?></a>
                <a class="aat-btn aat-btn-primary" href="<?php echo esc_url($db_url); ?>"><?php echo esc_html__('Open Full Database', 'academy-awards-table'); ?></a>
            </div>
        </div>

        <div class="aat-stats-bar aat-entity-stats">
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_categories)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Categories', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_records)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Nominations', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_winners)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Wins', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_ceremonies)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Ceremonies', 'academy-awards-table'); ?></span></div>
        </div>

        <?php foreach ($grouped as $cls => $list) : ?>
            <div class="aat-hub-section">
                <h2><?php echo esc_html($cls); ?></h2>
                <div class="aat-hub-grid">
                    <?php foreach ($list as $cat) :
                        $url = $aat->get_category_url($cat);
                        $label = $aat->format_category_display($cat);
                    ?>
                        <a class="aat-hub-card" href="<?php echo esc_url($url); ?>">
                            <h3 class="aat-hub-card-title"><?php echo esc_html($label); ?></h3>
                            <p class="aat-hub-card-meta"><?php echo esc_html($cat); ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

    <?php
        // ABOUT
        elseif ($hub === 'about') :
    ?>
        <div class="aat-hub-header">
            <h1 class="aat-hub-title"><?php echo esc_html__('About This Database', 'academy-awards-table'); ?></h1>
            <p class="aat-hub-subtitle"><?php echo esc_html__('A bespoke, normalized Academy Awards dataset compiled for Lunara Film by Dalton Johnson.', 'academy-awards-table'); ?></p>

            <?php if ($wp_hub_page instanceof WP_Post && $wp_hub_content !== '') : ?>
                <div class="aat-hub-wp-content">
                    <?php echo apply_filters('the_content', $wp_hub_page->post_content); ?>
                </div>
            <?php endif; ?>

            <div class="aat-hub-actions">
                <a class="aat-btn aat-btn-primary" href="<?php echo esc_url($db_url); ?>"><?php echo esc_html__('Open Full Database', 'academy-awards-table'); ?></a>
            </div>
        </div>

        <div class="aat-stats-bar aat-entity-stats">
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_records)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Nominations', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_winners)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Wins', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_categories)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Categories', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_ceremonies)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Ceremonies', 'academy-awards-table'); ?></span></div>
        </div>

        <div class="aat-hub-section">
            <h2><?php echo esc_html__('Scope', 'academy-awards-table'); ?></h2>
            <p class="aat-hub-copy">
                <?php echo esc_html__('This database spans the full history of the Academy Awards as represented in our dataset.', 'academy-awards-table'); ?>
                <?php if ($span) : ?>
                    <?php echo esc_html(sprintf(__('Coverage: %s.', 'academy-awards-table'), $span)); ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="aat-hub-section">
            <h2><?php echo esc_html__('Method', 'academy-awards-table'); ?></h2>
            <p class="aat-hub-copy">
                <?php echo esc_html__('We treat Oscar history as structured data: categories are normalized, nominee and title credits are linked, and each record is curated to support search, filtering, and internal discovery.', 'academy-awards-table'); ?>
            </p>
            <p class="aat-hub-copy">
                <?php echo esc_html__('Primary factual sourcing: Academy of Motion Picture Arts and Sciences. This dataset is independently structured, compiled, and maintained by Lunara Film.', 'academy-awards-table'); ?>
            </p>
        </div>

        <div class="aat-hub-section">
            <h2><?php echo esc_html__('Explore', 'academy-awards-table'); ?></h2>
            <div class="aat-hub-chips">
                <a class="aat-hub-chip" href="<?php echo esc_url($aat->get_ceremonies_index_url()); ?>"><?php echo esc_html__('Ceremonies', 'academy-awards-table'); ?></a>
                <a class="aat-hub-chip" href="<?php echo esc_url($aat->get_categories_index_url()); ?>"><?php echo esc_html__('Categories', 'academy-awards-table'); ?></a>
            </div>
        </div>

    <?php
        // CEREMONY PAGE
        elseif ($hub === 'ceremony') :
            $ceremony = intval($hub_id);
            if ($ceremony <= 0) {
                $mark_404();
            }
            $year_label = $aat->get_ceremony_year($ceremony);
            $noms = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE ceremony = %d", $ceremony)));
            $wins = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE ceremony = %d AND winner = 1", $ceremony)));
            $cats_count = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT canonical_category) FROM $table_name WHERE ceremony = %d AND canonical_category != ''", $ceremony)));
            $cats = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT canonical_category FROM $table_name WHERE ceremony = %d AND canonical_category != '' ORDER BY canonical_category ASC", $ceremony));
    ?>
        <div class="aat-hub-header">
            <h1 class="aat-hub-title"><?php echo esc_html($aat->ordinal($ceremony)); ?> <?php echo esc_html__('Academy Awards', 'academy-awards-table'); ?></h1>
            <p class="aat-hub-subtitle"><?php echo esc_html($year_label); ?></p>

            <div class="aat-hub-actions">
                <a class="aat-btn aat-btn-secondary" href="<?php echo esc_url($aat->get_ceremonies_index_url()); ?>"><?php echo esc_html__('All Ceremonies', 'academy-awards-table'); ?></a>
                <a class="aat-btn aat-btn-primary" href="<?php echo esc_url($db_url); ?>"><?php echo esc_html__('Open Full Database', 'academy-awards-table'); ?></a>
            </div>
        </div>

        <div class="aat-stats-bar aat-entity-stats">
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($noms)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Nominations', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($wins)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Wins', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($cats_count)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Categories', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo $span ? esc_html($span) : '—'; ?></span><span class="aat-stat-label"><?php echo esc_html__('Database span', 'academy-awards-table'); ?></span></div>
        </div>

        <?php if (!empty($cats)) : ?>
            <div class="aat-hub-section">
                <h2><?php echo esc_html__('Categories in this ceremony', 'academy-awards-table'); ?></h2>
                <div class="aat-hub-chips">
                    <?php foreach ($cats as $cat) :
                        $url = $aat->get_category_url($cat);
                        $label = $aat->format_category_display($cat);
                    ?>
                        <a class="aat-hub-chip" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="aat-hub-section">
            <?php
                // Embedded table with initial ceremony filter
                echo $aat->render_shortcode(array(
                    'ceremony' => (string) $ceremony,
                    'layout' => 'embedded',
                ));
            ?>
        </div>

    <?php
        // CATEGORY PAGE
        elseif ($hub === 'category') :
            $canonical = $aat->resolve_category_slug($hub_id);
            if (empty($canonical)) {
                $mark_404();
            }

            $label = $aat->format_category_display($canonical);
            $noms = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE canonical_category = %s", $canonical)));
            $wins = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE canonical_category = %s AND winner = 1", $canonical)));
            $cers = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT ceremony) FROM $table_name WHERE canonical_category = %s", $canonical)));
            $first_cer = intval($wpdb->get_var($wpdb->prepare("SELECT MIN(ceremony) FROM $table_name WHERE canonical_category = %s", $canonical)));
            $last_cer = intval($wpdb->get_var($wpdb->prepare("SELECT MAX(ceremony) FROM $table_name WHERE canonical_category = %s", $canonical)));
            $first_year = $first_cer ? $aat->get_ceremony_year($first_cer) : '';
            $last_year = $last_cer ? $aat->get_ceremony_year($last_cer) : '';
    ?>
        <div class="aat-hub-header">
            <h1 class="aat-hub-title"><?php echo esc_html($label); ?></h1>
            <p class="aat-hub-subtitle"><?php echo esc_html($canonical); ?></p>

            <div class="aat-hub-actions">
                <a class="aat-btn aat-btn-secondary" href="<?php echo esc_url($aat->get_categories_index_url()); ?>"><?php echo esc_html__('All Categories', 'academy-awards-table'); ?></a>
                <a class="aat-btn aat-btn-primary" href="<?php echo esc_url($db_url); ?>"><?php echo esc_html__('Open Full Database', 'academy-awards-table'); ?></a>
            </div>
        </div>

        <div class="aat-stats-bar aat-entity-stats">
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($noms)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Nominations', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($wins)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Wins', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($cers)); ?></span><span class="aat-stat-label"><?php echo esc_html__('Ceremonies', 'academy-awards-table'); ?></span></div>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html($first_year && $last_year ? ($first_year . '–' . $last_year) : '—'); ?></span><span class="aat-stat-label"><?php echo esc_html__('Span', 'academy-awards-table'); ?></span></div>
        </div>

        <div class="aat-hub-section">
            <?php
                // Embedded table with initial category filter
                echo $aat->render_shortcode(array(
                    'category' => $canonical,
                    'layout' => 'embedded',
                ));
            ?>
        </div>

    <?php
        // Unknown hub
        else :
            $mark_404();
    ?>
        <div class="aat-hub-header">
            <h1 class="aat-hub-title"><?php echo esc_html__('Not Found', 'academy-awards-table'); ?></h1>
            <p class="aat-hub-subtitle"><?php echo esc_html__('This page does not exist in the Academy Awards Database.', 'academy-awards-table'); ?></p>
            <div class="aat-hub-actions">
                <a class="aat-btn aat-btn-primary" href="<?php echo esc_url($db_url); ?>"><?php echo esc_html__('Open Full Database', 'academy-awards-table'); ?></a>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php
$aat->render_route_template_end();
?>
