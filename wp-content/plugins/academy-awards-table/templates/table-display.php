<?php
/**
 * Academy Awards Table - Frontend Display Template
 * This template renders the interactive awards table
 */

if (!defined('ABSPATH')) {
    exit;
}

$layout = isset($atts['layout']) ? (string) $atts['layout'] : 'full';
$layout = in_array($layout, array('full', 'embedded'), true) ? $layout : 'full';
?>

<div
    class="aat-container<?php echo ($layout === 'embedded') ? ' aat-embedded' : ''; ?>"
    data-initial-category="<?php echo esc_attr($atts['category'] ?? ''); ?>"
    data-initial-class="<?php echo esc_attr($atts['class'] ?? ''); ?>"
    data-initial-year="<?php echo esc_attr($atts['year'] ?? ''); ?>"
    data-initial-ceremony="<?php echo esc_attr($atts['ceremony'] ?? ''); ?>"
    data-initial-winners-only="<?php echo esc_attr($atts['winners_only'] ?? 'false'); ?>"
>
    <?php
        // Footer scope values (dynamic, update automatically after imports)
        $aat_instance = Academy_Awards_Table::get_instance();
        global $wpdb;
        $aat_table = $wpdb->prefix . 'academy_awards';
        $aat_ceremony_count = intval($wpdb->get_var("SELECT COUNT(DISTINCT ceremony) FROM $aat_table"));
        $aat_min_ceremony = intval($wpdb->get_var("SELECT MIN(ceremony) FROM $aat_table"));
        $aat_max_ceremony = intval($wpdb->get_var("SELECT MAX(ceremony) FROM $aat_table"));
        $aat_span = '';
        if ($aat_min_ceremony > 0 && $aat_max_ceremony > 0) {
            $first_year = $aat_instance->get_ceremony_year($aat_min_ceremony);
            $last_year = $aat_instance->get_ceremony_year($aat_max_ceremony);
            if ($first_year && $last_year) {
                $aat_span = $first_year . '–' . $last_year;
            }
        }
    ?>
    <?php if ($layout === 'full') : ?>
    <!-- Header -->
    <div class="aat-header">
        <img
            class="aat-oscar-icon"
            src="<?php echo esc_url(AAT_PLUGIN_URL . 'assets/img/oscar.png'); ?>"
            alt="<?php echo esc_attr__('Oscar statuette', 'academy-awards-table'); ?>"
            loading="lazy"
        />
        <h2><?php esc_html_e('Academy Awards Database', 'academy-awards-table'); ?></h2>
        <p class="aat-subtitle"><?php echo esc_html(sprintf('Every nominee & winner in our dataset (%s)', $aat_span ? $aat_span : 'through 2024')); ?></p>
    </div>

    <!-- Stats Bar -->
    <div class="aat-stats-bar">
        <div class="aat-stat">
            <span class="aat-stat-number" id="aat-stat-total">—</span>
            <span class="aat-stat-label"><?php esc_html_e('Total Nominations', 'academy-awards-table'); ?></span>
        </div>
        <div class="aat-stat">
            <span class="aat-stat-number" id="aat-stat-winners">—</span>
            <span class="aat-stat-label"><?php esc_html_e('Winners', 'academy-awards-table'); ?></span>
        </div>
        <div class="aat-stat">
            <span class="aat-stat-number" id="aat-stat-categories">—</span>
            <span class="aat-stat-label"><?php esc_html_e('Categories', 'academy-awards-table'); ?></span>
        </div>
        <div class="aat-stat">
            <span class="aat-stat-number" id="aat-stat-ceremonies">—</span>
            <span class="aat-stat-label"><?php esc_html_e('Ceremonies', 'academy-awards-table'); ?></span>
        </div>
    </div>

    <!-- Quick Filters -->
    <div class="aat-quick-filters">
        <!-- Quick filters populated by JavaScript -->
    </div>
    <?php endif; ?>

    <!-- Advanced Filters -->
    <div class="aat-filters">
        <div class="aat-filter-group">
            <label for="aat-filter-category"><?php esc_html_e('Category', 'academy-awards-table'); ?></label>
            <select id="aat-filter-category">
                <option value=""><?php esc_html_e('All Categories', 'academy-awards-table'); ?></option>
            </select>
        </div>

        <div class="aat-filter-group">
            <label for="aat-filter-class"><?php esc_html_e('Type', 'academy-awards-table'); ?></label>
            <select id="aat-filter-class">
                <option value=""><?php esc_html_e('All Types', 'academy-awards-table'); ?></option>
            </select>
        </div>

        <div class="aat-filter-group">
            <label for="aat-filter-year"><?php esc_html_e('Year', 'academy-awards-table'); ?></label>
            <select id="aat-filter-year">
                <option value=""><?php esc_html_e('All Years', 'academy-awards-table'); ?></option>
            </select>
        </div>

        <div class="aat-filter-group">
            <label for="aat-filter-ceremony"><?php esc_html_e('Ceremony', 'academy-awards-table'); ?></label>
            <select id="aat-filter-ceremony">
                <option value=""><?php esc_html_e('All Ceremonies', 'academy-awards-table'); ?></option>
            </select>
        </div>

        <div class="aat-filter-group aat-checkbox-group">
            <input type="checkbox" id="aat-filter-winners">
            <label for="aat-filter-winners"><?php esc_html_e('Winners Only', 'academy-awards-table'); ?></label>
        </div>

        <div class="aat-filter-group aat-filter-actions">
            <button type="button" class="aat-btn aat-btn-secondary aat-btn-reset">
                <?php esc_html_e('Reset', 'academy-awards-table'); ?>
            </button>
        </div>
    </div>

    <!-- Data Table -->
    <div class="aat-table-wrapper">
        <div class="aat-loading">
            <div class="aat-loading-spinner"></div>
            <span class="aat-loading-text"><?php esc_html_e('Loading Academy Awards data...', 'academy-awards-table'); ?></span>
        </div>
    </div>

    <!-- Footer -->
    <div class="aat-footer">
        <p class="aat-footer-line">
            <?php esc_html_e('Data sourced from the Academy of Motion Picture Arts and Sciences.', 'academy-awards-table'); ?>
            <span class="aat-footer-sep">•</span>
            <?php esc_html_e('Structured, normalized, and maintained by Lunara Film (Dalton Johnson).', 'academy-awards-table'); ?>
        </p>
        <p class="aat-footer-line">
            <span class="aat-footer-sep"><?php echo esc_html(number_format_i18n($aat_ceremony_count)); ?> ceremonies<?php if ($aat_span) : ?> (<?php echo esc_html($aat_span); ?>)<?php endif; ?></span>
            <span class="aat-footer-sep">•</span>
            <?php esc_html_e('Click nominees and films to open Lunara profiles; IMDb links are provided for verification.', 'academy-awards-table'); ?>
            <span class="aat-footer-mobile-hint"><?php esc_html_e('On mobile, tap the + icon to view full details.', 'academy-awards-table'); ?></span>
        </p>
        <p class="aat-footer-links">
            <a href="<?php echo esc_url(Academy_Awards_Table::get_instance()->get_ceremonies_index_url()); ?>"><?php esc_html_e('Ceremonies', 'academy-awards-table'); ?></a>
            <span class="aat-footer-sep">•</span>
            <a href="<?php echo esc_url(Academy_Awards_Table::get_instance()->get_categories_index_url()); ?>"><?php esc_html_e('Categories', 'academy-awards-table'); ?></a>
            <span class="aat-footer-sep">•</span>
            <a href="<?php echo esc_url(Academy_Awards_Table::get_instance()->get_about_url()); ?>"><?php esc_html_e('About this database', 'academy-awards-table'); ?></a>
        </p>
    </div>
</div>
