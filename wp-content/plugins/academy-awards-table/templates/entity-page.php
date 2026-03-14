<?php
/**
 * Academy Awards Table - Entity Page Template
 * Richer Film / Person / Company pages for Lunara Film
 */

if (!defined('ABSPATH')) {
    exit;
}

$aat = Academy_Awards_Table::get_instance();

$entity = sanitize_text_field(get_query_var('aat_entity'));
$id = sanitize_text_field(get_query_var('aat_entity_id'));

$rows = $aat->get_entity_rows($entity, $id);
$label = sanitize_text_field($aat->get_entity_display_name($entity, $id));
$label = trim((string) $label);

$aat_entity_not_found = false;
if (empty($rows)) {
    $aat_entity_not_found = true;
    status_header(404);
    nocache_headers();
}

$ordinal = function($n) {
    $n = intval($n);
    if ($n <= 0) return '';
    $s = array('th', 'st', 'nd', 'rd');
    $v = $n % 100;
    return $n . ($s[($v - 20) % 10] ?? $s[$v] ?? $s[0]);
};

$format_category = function($cat) {
    $cat = trim((string) $cat);
    if ($cat === '') return '';
    $map = array(
        'ACTOR IN A LEADING ROLE' => 'Best Actor',
        'ACTRESS IN A LEADING ROLE' => 'Best Actress',
        'ACTOR IN A SUPPORTING ROLE' => 'Best Supporting Actor',
        'ACTRESS IN A SUPPORTING ROLE' => 'Best Supporting Actress',
        'BEST PICTURE' => 'Best Picture',
        'DIRECTING' => 'Best Director',
        'WRITING (ORIGINAL SCREENPLAY)' => 'Original Screenplay',
        'WRITING (ADAPTED SCREENPLAY)' => 'Adapted Screenplay',
    );
    return $map[$cat] ?? ucwords(strtolower($cat));
};

$build_entity_url = function($id) use ($aat) {
    $id = trim((string) $id);
    if ($id === '') return '';
    $base = trailingslashit($aat->get_entity_base_url());
    if (preg_match('/^tt\d+$/', $id)) return esc_url($base . 'title/' . $id . '/');
    if (preg_match('/^nm\d+$/', $id)) return esc_url($base . 'name/' . $id . '/');
    if (preg_match('/^co\d+$/', $id)) return esc_url($base . 'company/' . $id . '/');
    return '';
};

$build_imdb_url = function($id) {
    $id = trim((string) $id);
    if ($id === '') return '';
    if (preg_match('/^tt\d+$/', $id)) return 'https://www.imdb.com/title/' . $id . '/';
    if (preg_match('/^nm\d+$/', $id)) return 'https://www.imdb.com/name/' . $id . '/';
    if (preg_match('/^co\d+$/', $id)) return 'https://www.imdb.com/company/' . $id . '/';
    return '';
};

$render_linked_pipe = function($value_list, $id_list) use ($build_entity_url) {
    $values = array_values(array_filter(array_map('trim', explode('|', (string) $value_list)), 'strlen'));
    $ids = array_values(array_filter(array_map('trim', explode('|', (string) $id_list)), 'strlen'));

    if (empty($values)) {
        return '<span class="aat-no-film">—</span>';
    }

    if (!empty($ids) && count($ids) === count($values)) {
        $out = array();
        foreach ($values as $i => $val) {
            $url = $build_entity_url($ids[$i] ?? '');
            if ($url) {
                $out[] = '<a class="aat-entity-link" href="' . esc_url($url) . '">' . esc_html($val) . '</a>';
            } else {
                $out[] = '<span class="aat-entity-text">' . esc_html($val) . '</span>';
            }
        }
        return implode('<span class="aat-sep"> · </span>', $out);
    }

    return implode('<span class="aat-sep"> · </span>', array_map(function($v) {
        return '<span class="aat-entity-text">' . esc_html($v) . '</span>';
    }, $values));
};

$total_nominations = is_array($rows) ? count($rows) : 0;
$total_wins = 0;
$categories_set = array();
$ceremonies_set = array();
$ceremony_year_map = array();
$distinct_films = array();
$timeline = array();
$latest_year = '';
$latest_ceremony = 0;

// Deduplicate rows: remove entries with identical key fields
$seen_keys = array();
if (is_array($rows)) {
    $rows = array_filter($rows, function($r) use (&$seen_keys) {
        $key = implode('|', array(
            (string) ($r['ceremony'] ?? ''),
            (string) ($r['canonical_category'] ?? $r['category'] ?? ''),
            (string) ($r['film_id'] ?? ''),
            (string) ($r['nominee_ids'] ?? ''),
            (string) ($r['winner'] ?? ''),
        ));
        if (isset($seen_keys[$key])) {
            return false;
        }
        $seen_keys[$key] = true;
        return true;
    });
}

if (is_array($rows)) {
    foreach ($rows as $r) {
        $winner = (!empty($r['winner']) && (int) $r['winner'] === 1);
        if ($winner) $total_wins++;

        $cat = (string) ($r['canonical_category'] ?? $r['category'] ?? '');
        if ($cat !== '') {
            $categories_set[$cat] = true;
        }

        $cer = intval($r['ceremony'] ?? 0);
        $year = (string) ($r['year'] ?? '');
        if ($cer > 0) {
            $ceremonies_set[$cer] = true;
            $ceremony_year_map[$cer] = $year;
            if ($cer > $latest_ceremony) {
                $latest_ceremony = $cer;
                $latest_year = $year;
            }

            if (!isset($timeline[$cer])) {
                $timeline[$cer] = array(
                    'year' => $year,
                    'rows' => array(),
                );
            }
            $timeline[$cer]['rows'][] = $r;
        }

        if ($entity !== 'title') {
            $film_ids = array_filter(array_map('trim', explode('|', (string) ($r['film_id'] ?? ''))), 'strlen');
            foreach ($film_ids as $fid) {
                $distinct_films[$fid] = true;
            }
        }
    }
}

krsort($timeline, SORT_NUMERIC);

$total_categories = count($categories_set);
$total_ceremonies = count($ceremonies_set);
$span = '';
if (!empty($ceremony_year_map)) {
    $years = array_values(array_filter($ceremony_year_map, 'strlen'));
    sort($years);
    if (!empty($years)) {
        $first_year = reset($years);
        $last_year = end($years);
        $span = $first_year === $last_year ? $first_year : ($first_year . '–' . $last_year);
    }
}

$imdb_url = $build_imdb_url($id);
$search_url = home_url('/?s=' . rawurlencode($label ? $label : $id));
$database_url = home_url('/oscars/');
$type_label = $entity === 'title' ? 'Film' : ($entity === 'company' ? 'Company' : 'Person');
$summary = '';
$tmdb = ($entity === 'title' && method_exists($aat, 'get_tmdb_data_for_imdb_id')) ? $aat->get_tmdb_data_for_imdb_id($id) : array();
if ($total_nominations > 0) {
    $summary = sprintf(
        '%s appears in the Lunara Oscar Database with %s nomination%s and %s win%s across %s ceremony%s.',
        $label ? $label : strtoupper($id),
        number_format_i18n($total_nominations),
        $total_nominations === 1 ? '' : 's',
        number_format_i18n($total_wins),
        $total_wins === 1 ? '' : 's',
        number_format_i18n($total_ceremonies),
        $total_ceremonies === 1 ? '' : 'ies'
    );
}

$aat_review_ids = array();
if ($entity === 'title') {
    $aat_review_ids = $aat->get_review_ids_for_title_id($id, 3);
}

$aat_poster_html = '';
if ($entity === 'title') {
    $aat_poster_html = $aat->get_poster_img_html_for_title($id, 'large', array('class' => 'aat-entity-poster'));
}
$aat_has_hero_media = ($entity === 'title' && (!empty($tmdb['poster_full']) || !empty($aat_poster_html)));

$aat->render_route_template_start(array('aat-route-template', 'aat-route-template--entity'));
?>
<div class="aat-route-shell alignfull">
<?php

if ($aat_entity_not_found) : ?>
<div class="aat-container aat-entity-page">
    <nav class="aat-breadcrumbs" aria-label="Breadcrumb">
        <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
        <span class="aat-sep">/</span>
        <a href="<?php echo esc_url($database_url); ?>">Oscars</a>
        <span class="aat-sep">/</span>
        <span><?php echo esc_html($type_label); ?></span>
    </nav>
    <div class="aat-no-results">
        <div class="aat-no-results-icon">🎬</div>
        <h3>No Oscar Records Found</h3>
        <p>This <?php echo esc_html(strtolower($type_label)); ?><?php echo $label ? ' (' . esc_html($label) . ')' : ''; ?> does not have any nominations or wins in the Lunara Oscar Database.</p>
        <div class="aat-entity-actions" style="justify-content:center;margin-top:18px;">
            <?php if (!empty($imdb_url)) : ?>
                <a class="aat-btn aat-btn-secondary" href="<?php echo esc_url($imdb_url); ?>" target="_blank" rel="noopener noreferrer">View on IMDb</a>
            <?php endif; ?>
            <a class="aat-btn aat-btn-primary" href="<?php echo esc_url($database_url); ?>">Browse the Database</a>
        </div>
    </div>
    <div class="aat-footer">
        <p>Data sourced from the Academy of Motion Picture Arts and Sciences. Structured dataset compiled and maintained by Lunara Film.</p>
    </div>
</div>
<?php
?></div><?php
$aat->render_route_template_end();
return;
endif;
?>
<div class="aat-container aat-entity-page">
    <nav class="aat-breadcrumbs" aria-label="Breadcrumb">
        <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
        <span class="aat-sep">/</span>
        <a href="<?php echo esc_url($database_url); ?>">Oscars</a>
        <span class="aat-sep">/</span>
        <span><?php echo esc_html($type_label); ?></span>
    </nav>

    <section class="aat-entity-hero<?php echo $aat_has_hero_media ? "" : " aat-entity-hero--no-media"; ?>">
        <?php if ($entity === 'title') : ?>
            <?php if (!empty($aat_poster_html) || !empty($tmdb['poster_full'])) : ?>
                <div class="aat-entity-poster-wrap">
                    <?php if (!empty($aat_poster_html)) : ?>
                        <?php echo $aat_poster_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php elseif (!empty($tmdb['poster_full'])) : ?>
                        <img class="aat-entity-poster" src="<?php echo esc_url($tmdb['poster_full']); ?>" alt="<?php echo esc_attr($label ? $label : strtoupper($id)); ?> poster" loading="lazy" decoding="async" />
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="aat-entity-hero-copy">
            <div class="aat-entity-kicker"><?php echo esc_html($type_label); ?> Profile</div>
            <h1 class="aat-entity-title"><?php echo esc_html($label ? $label : strtoupper($id)); ?></h1>
            <p class="aat-entity-subtitle">Academy Awards Database</p>
            <?php if ($summary) : ?>
                <p class="aat-entity-summary"><?php echo esc_html($summary); ?></p>
            <?php endif; ?>
            <?php if ($entity === 'title' && !empty($tmdb)) : ?>
                <?php $director_name = ''; if (!empty($tmdb['credits']['crew']) && is_array($tmdb['credits']['crew'])) { foreach ($tmdb['credits']['crew'] as $crew) { if (($crew['job'] ?? '') === 'Director') { $director_name = $crew['name']; break; } } } ?>
                <div class="aat-entity-meta-line">
                    <?php if (!empty($tmdb['release_date'])) : ?><span><?php echo esc_html(substr($tmdb['release_date'], 0, 4)); ?></span><?php endif; ?>
                    <?php if (!empty($director_name)) : ?><span><?php echo esc_html($director_name); ?></span><?php endif; ?>
                    <?php if (!empty($tmdb['runtime'])) : ?><span><?php echo esc_html(intval($tmdb['runtime'])); ?> min</span><?php endif; ?>
                </div>
                <?php if (!empty($tmdb['overview'])) : ?><p class="aat-entity-overview"><?php echo esc_html($tmdb['overview']); ?></p><?php endif; ?>
            <?php endif; ?>

            <div class="aat-entity-actions">
                <?php if (!empty($imdb_url)) : ?>
                    <a class="aat-btn aat-btn-primary" href="<?php echo esc_url($imdb_url); ?>" target="_blank" rel="noopener noreferrer">IMDb Reference</a>
                <?php endif; ?>
                <a class="aat-btn aat-btn-secondary" href="<?php echo esc_url($search_url); ?>">Search Lunara</a>
                <a class="aat-btn aat-btn-secondary" href="<?php echo esc_url($database_url); ?>">Return to Oscars</a>
            </div>
        </div>
    </section>

    <div class="aat-stats-bar aat-entity-stats">
        <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_nominations)); ?></span><span class="aat-stat-label">Nominations</span></div>
        <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_wins)); ?></span><span class="aat-stat-label">Wins</span></div>
        <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_categories)); ?></span><span class="aat-stat-label">Categories</span></div>
        <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n($total_ceremonies)); ?></span><span class="aat-stat-label">Ceremonies</span></div>
        <?php if ($entity !== 'title') : ?>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html(number_format_i18n(count($distinct_films))); ?></span><span class="aat-stat-label">Films</span></div>
        <?php endif; ?>
        <?php if ($span) : ?>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html($span); ?></span><span class="aat-stat-label">Span</span></div>
        <?php endif; ?>
        <?php if ($latest_year) : ?>
            <div class="aat-stat"><span class="aat-stat-number"><?php echo esc_html($latest_year); ?></span><span class="aat-stat-label">Most Recent</span></div>
        <?php endif; ?>
    </div>

    <?php if ($entity === 'title' && !empty($aat_review_ids)) : ?>
        <?php
        $aat_primary_review_id = (int) $aat_review_ids[0];
        $aat_review_url = get_permalink($aat_primary_review_id);
        $aat_review_title = get_the_title($aat_primary_review_id);
        $aat_review_excerpt = get_the_excerpt($aat_primary_review_id);
        $aat_review_thumb = get_the_post_thumbnail_url($aat_primary_review_id, 'medium');
        ?>
        <section class="aat-lunara-review-module" aria-label="Lunara Film review">
            <div class="aat-lunara-review-inner">
                <?php if (!empty($aat_review_thumb)) : ?>
                    <a class="aat-lunara-review-poster" href="<?php echo esc_url($aat_review_url); ?>">
                        <img src="<?php echo esc_url($aat_review_thumb); ?>" alt="<?php echo esc_attr($aat_review_title); ?>" loading="lazy" decoding="async" />
                    </a>
                <?php endif; ?>
                <div class="aat-lunara-review-content">
                    <div class="aat-lunara-review-kicker">LUNARA FILM REVIEW</div>
                    <h2 class="aat-lunara-review-title"><a href="<?php echo esc_url($aat_review_url); ?>"><?php echo esc_html($aat_review_title); ?></a></h2>
                    <?php if (!empty($aat_review_excerpt)) : ?>
                        <p class="aat-lunara-review-excerpt"><?php echo esc_html($aat_review_excerpt); ?></p>
                    <?php endif; ?>
                    <div class="aat-lunara-review-actions">
                        <a class="aat-btn aat-btn-primary" href="<?php echo esc_url($aat_review_url); ?>">Read the Review</a>
                        <a class="aat-btn aat-btn-secondary" href="<?php echo esc_url(home_url('/reviews/')); ?>">All Reviews</a>
                    </div>
                    <?php if (count($aat_review_ids) > 1) : ?>
                        <div class="aat-lunara-review-more">
                            <span class="aat-lunara-review-more-label">Also on Lunara:</span>
                            <?php
                            $more_links = array();
                            foreach (array_slice($aat_review_ids, 1) as $rid) {
                                $rid = (int) $rid;
                                $more_links[] = '<a href="' . esc_url(get_permalink($rid)) . '">' . esc_html(get_the_title($rid)) . '</a>';
                            }
                            echo implode('<span class="aat-sep"> · </span>', $more_links); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="aat-entity-section aat-entity-timeline">
        <div class="aat-section-head">
            <h2 class="aat-section-title">Oscar History</h2>
            <p class="aat-section-description">Every recorded Oscar nomination and win for this <?php echo esc_html(strtolower($type_label)); ?> in the Lunara database.</p>
        </div>

        <?php if (empty($rows)) : ?>
            <div class="aat-no-results">
                <div class="aat-no-results-icon">🎬</div>
                <h3>No records found</h3>
                <p>This profile doesn’t yet have Oscar records in the database.</p>
            </div>
        <?php else : ?>
            <div class="aat-timeline-list">
                <?php foreach ($timeline as $cer => $group) : ?>
                    <section class="aat-timeline-card">
                        <div class="aat-timeline-meta">
                            <div class="aat-timeline-ceremony"><?php echo esc_html($ordinal($cer)); ?> Ceremony</div>
                            <div class="aat-timeline-year"><?php echo esc_html($group['year']); ?></div>
                        </div>
                        <div class="aat-timeline-body">
                            <?php foreach ($group['rows'] as $r) :
                                $cat = (string) ($r['canonical_category'] ?? $r['category'] ?? '');
                                $cat_url = $aat->get_category_url($cat);
                                $cat_label = $format_category($cat);
                                $is_winner = (!empty($r['winner']) && (int) $r['winner'] === 1);
                            ?>
                                <article class="aat-history-item <?php echo $is_winner ? 'is-winner' : ''; ?>">
                                    <div class="aat-history-main">
                                        <div class="aat-history-category">
                                            <?php if ($cat_url) : ?>
                                                <a class="aat-hub-link" href="<?php echo esc_url($cat_url); ?>"><span class="aat-category-pill"><?php echo esc_html($cat_label); ?></span></a>
                                            <?php else : ?>
                                                <span class="aat-category-pill"><?php echo esc_html($cat_label); ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="aat-history-detail">
                                            <?php if ($entity === 'title') : ?>
                                                <?php
                                                $title_nominee_value = '';
                                                if (!empty($r['nominees'])) {
                                                    $title_nominee_value = (string) $r['nominees'];
                                                } elseif (!empty($r['name'])) {
                                                    $title_nominee_value = (string) $r['name'];
                                                } elseif (!empty($r['detail']) && stripos((string) $r['detail'], 'producer') !== false) {
                                                    $title_nominee_value = (string) $r['detail'];
                                                } elseif (!empty($r['note']) && stripos((string) $r['note'], 'producer') !== false) {
                                                    $title_nominee_value = (string) $r['note'];
                                                }
                                                ?>
                                                <div class="aat-history-line"><strong>Nominee<?php echo (strpos($title_nominee_value, '|') !== false) ? 's' : ''; ?>:</strong> <?php echo $title_nominee_value !== '' ? $render_linked_pipe($title_nominee_value, $r['nominee_ids'] ?? '') : esc_html('—'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                                            <?php else : ?>
                                                <div class="aat-history-line"><strong>Film:</strong> <?php echo $render_linked_pipe($r['film'] ?? '', $r['film_id'] ?? ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                                            <?php endif; ?>

                                            <?php if (!empty($r['detail'])) : ?>
                                                <div class="aat-history-line"><strong>Detail:</strong> <?php echo esc_html((string) $r['detail']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($r['citation'])) : ?>
                                                <div class="aat-history-line"><strong>Citation:</strong> <?php echo esc_html((string) $r['citation']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($r['note'])) : ?>
                                                <div class="aat-history-line"><strong>Note:</strong> <?php echo esc_html((string) $r['note']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="aat-history-status">
                                        <?php if ($is_winner) : ?>
                                            <span class="aat-winner-badge">Winner</span>
                                        <?php else : ?>
                                            <span class="aat-nominee-badge">Nominee</span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>


    <?php if ($entity !== 'title' && !empty($distinct_films)) : ?>
        <section class="aat-entity-section aat-filmography-section">
            <div class="aat-section-head">
                <h2 class="aat-section-title">Nominated Films</h2>
                <p class="aat-section-description">Poster-first navigation through the films connected to this Oscar history.</p>
            </div>
            <div class="aat-filmography-grid">
                <?php foreach (array_keys($distinct_films) as $fid) :
                    $fid = trim((string) $fid);
                    if (!$fid) { continue; }
                    $film_label = $aat->lookup_title_label($fid);
                    if (!$film_label) { $film_label = strtoupper($fid); }
                    $poster_html = $aat->get_poster_img_html_for_title($fid, 'medium_large', array('class' => 'aat-filmography-poster'));
                    $tmdb_item = method_exists($aat, 'get_tmdb_data_for_imdb_id') ? $aat->get_tmdb_data_for_imdb_id($fid) : array();
                    $film_url = $build_entity_url($fid);
                ?>
                    <article class="aat-filmography-card">
                        <a class="aat-filmography-link" href="<?php echo esc_url($film_url ? $film_url : $database_url); ?>">
                            <div class="aat-filmography-poster-wrap">
                                <?php if ($poster_html) : ?>
                                    <?php echo $poster_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php elseif (!empty($tmdb_item['poster_full'])) : ?>
                                    <img class="aat-filmography-poster" src="<?php echo esc_url($tmdb_item['poster_full']); ?>" alt="<?php echo esc_attr($film_label); ?> poster" loading="lazy" decoding="async" />
                                <?php else : ?>
                                    <div class="aat-filmography-poster-placeholder"><span><?php echo esc_html($film_label); ?></span></div>
                                <?php endif; ?>
                            </div>
                            <h3 class="aat-filmography-title"><?php echo esc_html($film_label); ?></h3>
                            <?php if (!empty($tmdb_item['release_date'])) : ?><p class="aat-filmography-meta"><?php echo esc_html(substr($tmdb_item['release_date'], 0, 4)); ?></p><?php endif; ?>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <div class="aat-footer">
        <p>Data sourced from the Academy of Motion Picture Arts and Sciences. Structured dataset compiled and maintained by Lunara Film.</p>
        <p>Profiles are generated directly from the Lunara Film Oscars dataset. New nominations and winners appear automatically after each annual import.</p>
    </div>
</div>
</div>
<?php $aat->render_route_template_end();
