<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!defined('WPINC')) exit; // Exit if accessed directly

require_once plugin_dir_path(__FILE__) . 'do-api-seo-calls.php';

function blogcopilot_io_keyword_rankings_page_content($active_tab = 1) {
    $keywords = null;
    $keywordsToAdd = null;
    $keywordsToCheck = null;

    if (isset($_GET['tab']) && $_GET['tab'] == '2') { $active_tab = 2; }
    if (isset($_GET['tab']) && $_GET['tab'] == '3') { $active_tab = 3; }
    if (isset($_GET['tab']) && $_GET['tab'] == '6') { $active_tab = 6; }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['blogcopilot_seo_add_keyword_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['blogcopilot_seo_add_keyword_nonce'])), 'blogcopilot_seo_add_keyword')) {
            if (isset($_POST['keywords']) && !empty($_POST['keywords'])) {
                $keywordsToAdd = explode(',', sanitize_text_field($_POST['keywords']));
                $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : '';

                $keywordsData = array_map(function($keyword) use ($language) {
                    return ['Keyword' => trim($keyword), 'LanguageName' => $language];
                }, $keywordsToAdd);
                $response = blogcopilot_io_call_keywords('addKeywords', $keywordsData);
                $active_tab = 3;

                if ($response == null) {
                    echo '
                    <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
                    <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">Error while adding new keywords.</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                    </button>
                    </div>
                    ';
                } else {
                    echo '
                    <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
                    <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">Keywords were added.</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                    </button>
                    </div>
                    ';
                }            
            }
        }
        if (isset($_POST['blogcopilot_seo_recommend_keyword_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['blogcopilot_seo_recommend_keyword_nonce'])), 'blogcopilot_seo_recommend_keyword')) {
            if (isset($_POST['keywords']) && !empty($_POST['keywords'])) {
                $keywordsToCheck = sanitize_text_field($_POST['keywords']);
                $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : get_option('blogcopilot_blog_location', '2840');
                $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : get_option('blogcopilot_blog_lang', 'English');  

                $keywords = blogcopilot_io_call_keywords('getProposedKeywords', $keywordsToCheck, null, $location, $language);

                // store last 10 researches
                $recent_keywords = get_option('blogcopilot_recent_keyword_research', []);
                array_unshift($recent_keywords, $keywordsToCheck);
                $recent_keywords = array_slice($recent_keywords, 0, 10);
                update_option('blogcopilot_recent_keyword_research', $recent_keywords);  

                $active_tab = 4;

                if ($keywords == null) {
                    echo '
                    <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
                    <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">No proposed keywords were found. Try another inspiration.</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                    </button>
                    </div>
                    ';
                } elseif (isset($keywords['error'])) {
                    echo '
                    <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
                    <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">'.esc_attr($keywords['error']).'</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                    </button>
                    </div>
                    ';               
                }           
            }
        }
    } elseif (isset($_GET['action'])) {
        if ($_GET['action'] == 'seo_keyword_delete' && isset($_GET['trackId'])) {
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'delete-seo-keyword-' . intval($_GET['trackId']))) {
                wp_die('Nonce verification failed, unauthorized request.');
            }
        
            $trackId = intval($_GET['trackId']);
            $response = blogcopilot_io_call_keywords('deleteKeyword', null, $trackId);
            $active_tab = 3;
        
            if ($response == null) {
                echo '
                <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
                <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">Error while deleting keyword.</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                </button>
                </div>
                ';
            } else {
                echo '
                <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
                <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">Keyword was deleted.</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                </button>
                </div>
                ';
            }
        }
        if ($_GET['action'] == 'seo_keyword_research' && isset($_GET['keywords'])) {
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'research-seo-keyword')) {
                wp_die('Nonce verification failed, unauthorized request.');
            }
            $keywordsToCheck = sanitize_text_field($_GET['keywords']);
            $location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : get_option('blogcopilot_blog_location', '2840');
            $language = isset($_GET['language']) ? sanitize_text_field($_GET['language']) : get_option('blogcopilot_blog_lang', 'English');  
            
            $keywords = blogcopilot_io_call_keywords('getProposedKeywords', $keywordsToCheck, null, $location, $language);
 
            $active_tab = 4;
    
            if ($keywords == null) {
                echo '
                <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
                <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">No proposed keywords were found. Try another inspiration.</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                </button>
                </div>
                ';
            } elseif (isset($keywords['error'])) {
                echo '
                <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
                <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">'.esc_attr($keywords['error']).'</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                </button>
                </div>
                ';               
            }         
        }
    }

?>

    <div id="blogcopilot-keyword-rankings-form-div">
    <div class="p-4 bg-light">
        <h4>SEO Assistant</h4>

        <ul class="nav nav-tabs" id="mySEOTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php if ($active_tab == 1) echo 'active';?>" id="seo-home-tab" data-bs-toggle="tab" data-bs-target="#seo-home-tab-pane" type="button" role="tab" aria-controls="seo-home-tab-pane" aria-selected="<?php if ($active_tab == 1) echo 'true'; else echo 'false';?>">SEO Home</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php if ($active_tab == 2) echo 'active';?>" id="rankings-tab" data-bs-toggle="tab" data-bs-target="#rankings-tab-pane" type="button" role="tab" aria-controls="rankings-tab-pane" aria-selected="<?php if ($active_tab == 2) echo 'true'; else echo 'false';?>">Rankings</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php if ($active_tab == 3) echo 'active';?>" id="keywords-tab" data-bs-toggle="tab" data-bs-target="#keywords-tab-pane" type="button" role="tab" aria-controls="keywords-tab-pane" aria-selected=" aria-selected="<?php if ($active_tab == 3) echo 'true'; else echo 'false';?>">Keywords</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php if ($active_tab == 4) echo 'active';?>" id="keywords-research-tab" data-bs-toggle="tab" data-bs-target="#keywords-research-tab-pane" type="button" role="tab" aria-controls="keywords-research-tab-pane" aria-selected=" aria-selected="<?php if ($active_tab == 4) echo 'true'; else echo 'false';?>">Keywords Research</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php if ($active_tab == 5) echo 'active';?>" id="seo-competition-tab" data-bs-toggle="tab" data-bs-target="#seo-competition-tab-pane" type="button" role="tab" aria-controls="seo-competition-tab-pane" aria-selected="<?php if ($active_tab == 5) echo 'true'; else echo 'false';?>">SEO Competition</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php if ($active_tab == 6) echo 'active';?>" id="seo-site-audit-tab" data-bs-toggle="tab" data-bs-target="#seo-site-audit-tab-pane" type="button" role="tab" aria-controls="seo-site-audit-tab-pane" aria-selected="<?php if ($active_tab == 6) echo 'true'; else echo 'false';?>">SEO Site Audit</button>
        </li>
        </ul>

        <div class="tab-content" id="mySEOTabContent">
            <div class="tab-pane fade<?php if ($active_tab == 1) echo ' show active';?>" id="seo-home-tab-pane" role="tabpanel" aria-labelledby="seo-home-tab" tabindex="0"><?php blogcopilot_io_seo_home_content(); ?></div>
            <div class="tab-pane fade<?php if ($active_tab == 2) echo ' show active';?>" id="rankings-tab-pane" role="tabpanel" aria-labelledby="rankings-tab" tabindex="0"><?php blogcopilot_io_rankings_content(); ?></div>
            <div class="tab-pane fade<?php if ($active_tab == 3) echo ' show active';?>" id="keywords-tab-pane" role="tabpanel" aria-labelledby="keywords-tab" tabindex="0"><?php blogcopilot_io_keywords_content(); ?></div>
            <div class="tab-pane fade<?php if ($active_tab == 4) echo ' show active';?>" id="keywords-research-tab-pane" role="tabpanel" aria-labelledby="keywords-research-tab" tabindex="0"><?php blogcopilot_io_keywords_research_content($keywordsToCheck, $keywords); ?></div>
            <div class="tab-pane fade<?php if ($active_tab == 5) echo ' show active';?>" id="seo-competition-tab-pane" role="tabpanel" aria-labelledby="seo-competition-tab" tabindex="0"><?php blogcopilot_io_seo_competition_content(); ?></div>
            <div class="tab-pane fade<?php if ($active_tab == 6) echo ' show active';?>" id="seo-site-audit-tab-pane" role="tabpanel" aria-labelledby="seo-site-audit-tab" tabindex="0"><?php blogcopilot_io_seo_site_audit_content(); ?></div>
        </div>

    </div>
    </div>
<?php
}

function blogcopilot_io_seo_home_check_plan($mode = 0) {
    $plan = get_option('blogcopilot_license_plan', 'Free');
    if ($mode == 0 && $plan == "Free") {
?>
        <div class="container card">
        <div class="row">
          <div class="col-md-12 m-auto my-3 ps-4">
            <h5>Not available in Free plan</h5><a href="https://blogcopilot.io" target="_blank">Upgrade to Silver or Gold!</a>
          </div>
        </div>
        </div>
<?php
        return false;
    }
    if ($mode == 1 && $plan != "Gold") {
?>
        <div class="container card">
        <div class="row">
            <div class="col-md-12 m-auto my-3 ps-4">
            <h5>Not available in <?php echo esc_html($plan);?> plan</h5><a href="https://blogcopilot.io" target="_blank">Upgrade to Gold!</a>
            </div>
        </div>
        </div>
<?php
        return false;
    }
    return true;    
}

function blogcopilot_io_getRank($data, $key) {
    return isset($data[$key]) ? $data[$key] : 0;
}

function blogcopilot_io_seo_home_content() {
    if (blogcopilot_io_seo_home_check_plan(0) == false) return;

    $rankingsData = blogcopilot_io_call_seo_dashboard();

    $weeks = [];
    foreach ($rankingsData as $entry) {
        $weeks[$entry['Week']] = $entry;
    }

    $top10ThisWeek = blogcopilot_io_getRank($weeks[0], 'Top10');
    $top10LastWeek = blogcopilot_io_getRank($weeks[1], 'Top10');
    $top10LastMonth = blogcopilot_io_getRank($weeks[4], 'Top10');

    $top50ThisWeek = blogcopilot_io_getRank($weeks[0], 'Top50');
    $top50LastWeek = blogcopilot_io_getRank($weeks[1], 'Top50');
    $top50LastMonth = blogcopilot_io_getRank($weeks[4], 'Top50');

    $top100ThisWeek = blogcopilot_io_getRank($weeks[0], 'Top100');
    $top100LastWeek = blogcopilot_io_getRank($weeks[1], 'Top100');
    $top100LastMonth = blogcopilot_io_getRank($weeks[4], 'Top100');

    $difference = $top10ThisWeek - $top10LastWeek;
    if ($difference > 0) {
        $formattedDifferenceTop10 = '<span style="color: green;">+' . esc_html($difference) . '</span>';
    } elseif ($difference < 0) {
        $formattedDifferenceTop10 = '<span style="color: red;">' . esc_html($difference) . '</span>';
    } else {
        $formattedDifferenceTop10 = '<span style="color: black;">' . esc_html($difference) . '</span>';
    }    
    $difference = $top50ThisWeek - $top50LastWeek;
    if ($difference > 0) {
        $formattedDifferenceTop50 = '<span style="color: green;">+' . esc_html($difference) . '</span>';
    } elseif ($difference < 0) {
        $formattedDifferenceTop50 = '<span style="color: red;">' . esc_html($difference) . '</span>';
    } else {
        $formattedDifferenceTop50 = '<span style="color: black;">' . esc_html($difference) . '</span>';
    }    
    $difference = $top100ThisWeek - $top100LastWeek;
    if ($difference > 0) {
        $formattedDifferenceTop100 = '<span style="color: green;">+' . esc_html($difference) . '</span>';
    } elseif ($difference < 0) {
        $formattedDifferenceTop100 = '<span style="color: red;">' . esc_html($difference) . '</span>';
    } else {
        $formattedDifferenceTop100 = '<span style="color: black;">' . esc_html($difference) . '</span>';
    } 

?>
    <div class="container card">
    <div class="row">
        <div class="col-md-12 m-auto my-3 ps-4">
        <h4>Rankings - statistics of your keywords</h4>
        <div class="row">
        <div class="col-md-9 m-auto my-3">
            <div class="table-responsive" style="padding: 0.2em">
            <table class="table align-items-center mb-0">
            <thead>
            <tr>
            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7"></th>
            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">This week</th>
            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Previous week</th>
            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Change</th>
            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Previous month</th>
            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Change</th>
            </tr>
            </thead>
            <tbody>
            <tr>
            <td><p class="text-secondary mb-0">Top10</p></td>
            <td><p class="text-secondary mb-0"><?php echo esc_html($top10ThisWeek); ?></p></td>
            <td><p class="text-secondary mb-0"><?php echo esc_html($top10LastWeek); ?></p></td>
            <td><p class="text-secondary mb-0"><?php echo wp_kses_post($formattedDifferenceTop10); ?></p></td>
            <td><p class="text-secondary mb-0"><?php echo esc_html($top10LastMonth); ?></p></td>
            <td><p class="text-secondary mb-0"><?php echo esc_html($top10ThisWeek - $top10LastMonth); ?></p></td>
            </tr>
            <tr>
            <td><p class="text-secondary mb-0">Top50</p></td>
            <td><p class="text-secondary mb-0"><?php echo esc_html($top50ThisWeek); ?></p></td>
            <td><p class="text-secondary mb-0"><?php echo esc_html($top50LastWeek); ?></p></td>
            <td><p class="text-secondary mb-0"><?php echo wp_kses_post($formattedDifferenceTop50); ?></p></td>
            <td><p class="text-secondary mb-0"><?php echo esc_html($top50LastMonth); ?></p></td>
            <td><p class="text-secondary mb-0"><?php echo esc_html($top50ThisWeek - $top50LastMonth); ?></p></td>
            </tr>
            <tr>
            <td><p class="text-secondary mb-0">Top100</p></td>
            <td><p class="text-secondary mb-0"><?php echo esc_html($top100ThisWeek); ?></p></td>
            <td><p class="text-secondary mb-0"><?php echo esc_html($top100LastWeek); ?></p></td>
            <td><p class="text-secondary mb-0"><?php echo wp_kses_post($formattedDifferenceTop100); ?></p></td>
            <td><p class="text-secondary mb-0"><?php echo esc_html($top100LastMonth); ?></p></td>
            <td><p class="text-secondary mb-0"><?php echo esc_html($top100ThisWeek - $top100LastMonth); ?></p></td>
            </tr>            
            </tbody>
            </table>
            </div>
        </div>
        </div>          
        </div>
    </div>          
    </div>
<?php
}

function blogcopilot_io_rankings_content() {
    if (blogcopilot_io_seo_home_check_plan(0) == false) return;

    $rankings = blogcopilot_io_call_dataforseo_api();
    $rankingKeywords = blogcopilot_io_call_keywords('getRankingsOfTracketKeywords');

    // Check for errors in the API response
    if (isset($rankings['error']) && $rankings['error']) {
        echo '<div class="alert alert-secondary fade show my-2" role="alert">';
        echo '<i class="bi bi-exclamation-diamond"></i>'; // Adding an error icon
        echo '<span class="alert-text"> Cannot display rankings: ' . esc_html($rankings['error']) . '</span>';
        echo '</div>';

        return; // Exit the function to prevent further processing
    }
?>
    <div class="container card">
    <div class="row">

        <div class="col-md-12 m-auto my-3 ps-4">
        <h4>Rankings - your keywords</h4>
<?php
        if (!empty($rankingKeywords)) {
        echo '<div class="card table-responsive" style="max-width: 100%; top-margin: 5px; padding: 0.2em">';
        echo '<table id="rankingTable1" class="table align-items-center mb-0 table-striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Keyword</th>';
        echo '<th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">URL</th>';
        echo '<th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Computer Position</th>';
        echo '<th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Mobile Position</th>';
        echo '<th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Location</th>';
        echo '<th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Last Checked</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($rankingKeywords as $ranking) {
            echo '<tr>';
            echo '<td><p class="text-xs text-secondary mb-0">' . esc_html($ranking['Keyword']) . '</p></td>';
            if ($ranking['url'] != null) {
                $displayUrl = (strlen($ranking['url']) > 50) ? substr($ranking['url'], 0, 50) . '...' : $ranking['url'];
                echo '<td><p class="text-xs text-secondary mb-0 text-truncate"><a href="' . esc_url($ranking['url']) . '" target="_blank">' . esc_html($displayUrl) . '</a></p></td>';            
            } else
                echo '<td><p class="text-xs text-secondary mb-0 text-truncate"></p></td>';
            
            $positionDesktop = $ranking['PositionDesktop'];
            $displayPositionDesktop = ($positionDesktop === 0 || $positionDesktop === null) ? '-' : $positionDesktop;
            $positionMobile = $ranking['PositionMobile'];
            $displayPositionMobile = ($positionMobile === 0 || $positionMobile === null) ? '-' : $positionMobile;

            echo '<td><p class="text-xs text-secondary mb-0">' . esc_html($displayPositionDesktop) . '</p></td>';
            echo '<td><p class="text-xs text-secondary mb-0">' . esc_html($displayPositionMobile) . '</p></td>';            
            echo '<td><p class="text-xs text-secondary mb-0">' . esc_html($ranking['LanguageName']) . '</p></td>';
            echo '<td><p class="text-xs text-secondary mb-0">' . esc_html($ranking['LastChecked']) . '</p></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else { ?>
        <div class="row">
        <div class="col-md-9 m-auto my-3">
            <div class="table-responsive" style="padding: 0.2em">
            <span class="alert-text">No ranking keywords found.</span>
            </div>
        </div>
        </div>
<?php } ?>

        </div>

        <div class="col-md-12 m-auto my-3 ps-4">
        <h4>Rankings - all found ranking keywords</h4>
<?php
        if (!empty($rankings)) {
        echo '<div class="card table-responsive" style="max-width: 100%; top-margin: 5px; padding: 0.2em">';
        echo '<table id="rankingTable2" class="table align-items-center mb-0 table-striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Keyword</th>';
        echo '<th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">URL</th>';
        echo '<th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">SERP Position</th>';
        echo '<th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Monthly<br/>Search<br/>Volume</th>';
        echo '<th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Keyword<br/>Difficulty</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($rankings as $ranking) {
            echo '<tr>';
            echo '<td><p class="text-xs text-secondary mb-0">' . esc_html($ranking['keyword']) . '</p></td>';
            $displayUrl = (strlen($ranking['url']) > 50) ? substr($ranking['url'], 0, 50) . '...' : $ranking['url'];
            echo '<td><p class="text-xs text-secondary mb-0 text-truncate"><a href="' . esc_url($ranking['url']) . '" target="_blank">' . esc_html($displayUrl) . '</a></p></td>';
            echo '<td><p class="text-xs text-secondary mb-0">' . esc_html($ranking['position']) . '</p></td>';
            echo '<td><p class="text-xs text-secondary mb-0">' . esc_html($ranking['search_volume']) . '</p></td>';
            // Determine the color of the keyword difficulty badge based on its value
            $keywordDifficulty = (int) $ranking['keyword_difficulty'];
            $badgeColor = 'easy'; 
            if ($keywordDifficulty > 40 && $keywordDifficulty <= 60) {
                $badgeColor = 'medium'; 
            } elseif ($keywordDifficulty > 60 && $keywordDifficulty <= 80) { 
                $badgeColor = 'hard';                 
            } elseif ($keywordDifficulty > 80) {
                $badgeColor = 'insane';
            }
            echo '<td><p class="text-xs text-secondary mb-0">' . esc_html($ranking['keyword_difficulty']) . ' (' . esc_attr($badgeColor). ')</p></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else { ?>
        <div class="row">
        <div class="col-md-9 m-auto my-3">
            <div class="table-responsive" style="padding: 0.2em">
            <span class="alert-text">No ranking keywords found.</span>
            </div>
        </div>
        </div>
<?php } ?>

        </div>


    </div>
    </div>
<?php
}

function blogcopilot_io_keywords_content() {
    if (blogcopilot_io_seo_home_check_plan(0) == false) return;

    $blog_location = get_option('blogcopilot_blog_location', '2840');
    $keywords = blogcopilot_io_call_keywords('getKeywords');

?>
    <div class="container card">
    <div class="row">

    <div class="col-md-12 m-auto my-3 ps-4">

    <h4>Add New Keywords</h4>
    <form id="addKeywordForm" class="mb-3" method="post">
        <?php wp_nonce_field('blogcopilot_seo_add_keyword', 'blogcopilot_seo_add_keyword_nonce'); ?>        
        <div class="input-group mb-3 row">
        <div class="col-md-7">            
            <textarea type="text" name="keywords" class="form-control" placeholder="Enter keywords separated by comma (,)" required></textarea>
        </div>
        <div class="col-md-3">
            <label for="language" style="padding-right: 10px"><?php esc_html_e('Seach location', 'blogcopilot-io'); ?></label>            
            <select name="language" class="custom-select">
                <option value="2840" <?php echo ($blog_location == "2840") ? 'selected' : ''; ?>>United States</option>
                <option value="2826" <?php echo ($blog_location == "2826") ? 'selected' : ''; ?>>United Kingdom</option>
                <option value="2356" <?php echo ($blog_location == "2356") ? 'selected' : ''; ?>>India</option>
                <option value="2724" <?php echo ($blog_location == "2724") ? 'selected' : ''; ?>>Spain</option>
                <option value="2032" <?php echo ($blog_location == "2032") ? 'selected' : ''; ?>>Argentina</option>
                <option value="2484" <?php echo ($blog_location == "2484") ? 'selected' : ''; ?>>Mexico</option>
                <option value="2760" <?php echo ($blog_location == "2760") ? 'selected' : ''; ?>>Germany</option>
                <option value="2250" <?php echo ($blog_location == "2250") ? 'selected' : ''; ?>>France</option>
                <option value="2284" <?php echo ($blog_location == "2284") ? 'selected' : ''; ?>>Portugal</option>
                <option value="2380" <?php echo ($blog_location == "2380") ? 'selected' : ''; ?>>Italy</option>
                <option value="2360" <?php echo ($blog_location == "2360") ? 'selected' : ''; ?>>Indonesia</option>
                <option value="2392" <?php echo ($blog_location == "2392") ? 'selected' : ''; ?>>Japan</option>
                <option value="2616" <?php echo ($blog_location == "2616") ? 'selected' : ''; ?>>Poland</option>
                <option value="2528" <?php echo ($blog_location == "2528") ? 'selected' : ''; ?>>Netherlands</option>
            </select>
        </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Add Keywords</button>
            </div>
        </div>
    </form>

    <h4>Keywords List <?php echo '('.esc_attr($keywords['keywords_count']).' out of '.esc_attr($keywords['serp_quota']).')'; ?></h4>
    
    <div class="card table-responsive" style="max-width: 100%; top-margin: 5px; padding: 0.2em">
    <table  id="keywordsTable" class="table table-striped mb-0">
        <thead class="thead-dark">
            <tr>
                <th class="text-uppercase text-secondary text-xs font-weight-bolder">Keyword</th>
                <th class="text-uppercase text-secondary text-xs font-weight-bolder">Location</th>
                <th class="text-uppercase text-secondary text-xs font-weight-bolder">Frequency</th>
                <th class="text-uppercase text-secondary text-xs font-weight-bolder">Actions</th>
            </tr>
        </thead>
        <tbody>
<?php
    if ($keywords) {
        $languageMapping = [
            "2840" => "United States",
            "2826" => "United Kingdom",
            "2356" => "India",
            "2724" => "Spain",
            "2032" => "Argentina",
            "2484" => "Mexico",
            "2760" => "Germany",
            "2250" => "France",
            "2284" => "Portugal",
            "2380" => "Italy",
            "2360" => "Indonesia",
            "2392" => "Japan",
            "2616" => "Poland",
            "2528" => "Netherlands"
        ];

        foreach ($keywords['keywords'] as $keyword) {
            // Retrieve the country name based on the LanguageName code
            $countryName = isset($languageMapping[$keyword['LanguageName']]) ? $languageMapping[$keyword['LanguageName']] : 'Unknown';
    
            echo '<tr>';
            echo '<td><p class="text-s text-secondary mb-0">' . esc_html(htmlspecialchars($keyword['Keyword'] ?? '')) . '</p></td>';
            echo '<td><p class="text-s text-secondary mb-0">' . esc_html(htmlspecialchars($countryName)) . '</p></td>';
            echo '<td><p class="text-s text-secondary mb-0">Every ' . esc_html(htmlspecialchars($keyword['UpdateFrequency'] ?? '')) . ' days</p></td>';
            echo '<td><a href="#" class="btn btn-outline-info btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#confirmationModal" data-delete-url="' . esc_url(admin_url("admin.php?page=blogcopilot-view-rankings&action=seo_keyword_delete&trackId=" . htmlspecialchars($keyword['TrackID'] ?? ''))) . '" data-nonce="' . esc_attr(wp_create_nonce('delete-seo-keyword-' . htmlspecialchars($keyword['TrackID'] ?? ''))) . '">Delete</a></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4"><p class="text-s text-secondary mb-0">No keywords found.</p></td></tr>';
    }
?>
        </tbody>
    </table>
    </div>
</div>
</div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
<div class="modal-dialog">
    <div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="confirmationModalLabel">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        Are you sure you want to delete this keyword?
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <button type="button" class="btn btn-danger" id="confirmDelete">Yes</button>
    </div>
    </div>
</div>
</div>

<?php
}

function blogcopilot_io_keywords_research_content($entered_keyword = null, $keywords = null) {
    if (blogcopilot_io_seo_home_check_plan(0) == false) return;

    $blog_location = get_option('blogcopilot_blog_location', '2840');
    $blog_lang = get_option('blogcopilot_blog_lang', 'English');    

?>
    <div class="container card">
    <div class="row">

    <div class="col-md-12 m-auto my-3 ps-4">

    <h4>Get inspirations</h4>
    <form id="recommendKeywordForm" class="mb-3" method="post">
        <?php wp_nonce_field('blogcopilot_seo_recommend_keyword', 'blogcopilot_seo_recommend_keyword_nonce'); ?>        
        <div class="input-group mb-3 row">
            <div class="col-md-6">
                <textarea type="text" name="keywords" class="form-control" placeholder="Enter keywords separated by comma (,)" required></textarea>
            </div>
            <div class="col-md-2">
            <label for="location" style="padding-right: 10px"><?php esc_html_e('Seach location', 'blogcopilot-io'); ?></label><br/>
            <label for="language" style="padding-right: 10px"><?php esc_html_e('Language', 'blogcopilot-io'); ?></label>
            </div>
            <div class="col-md-2">
                <select name="location" class="custom-select">
                    <option value="2840" <?php echo ($blog_location == "2840") ? 'selected' : ''; ?>>United States</option>
                    <option value="2826" <?php echo ($blog_location == "2826") ? 'selected' : ''; ?>>United Kingdom</option>
                    <option value="2356" <?php echo ($blog_location == "2356") ? 'selected' : ''; ?>>India</option>
                    <option value="2724" <?php echo ($blog_location == "2724") ? 'selected' : ''; ?>>Spain</option>
                    <option value="2032" <?php echo ($blog_location == "2032") ? 'selected' : ''; ?>>Argentina</option>
                    <option value="2484" <?php echo ($blog_location == "2484") ? 'selected' : ''; ?>>Mexico</option>
                    <option value="2760" <?php echo ($blog_location == "2760") ? 'selected' : ''; ?>>Germany</option>
                    <option value="2250" <?php echo ($blog_location == "2250") ? 'selected' : ''; ?>>France</option>
                    <option value="2284" <?php echo ($blog_location == "2284") ? 'selected' : ''; ?>>Portugal</option>
                    <option value="2380" <?php echo ($blog_location == "2380") ? 'selected' : ''; ?>>Italy</option>
                    <option value="2360" <?php echo ($blog_location == "2360") ? 'selected' : ''; ?>>Indonesia</option>
                    <option value="2392" <?php echo ($blog_location == "2392") ? 'selected' : ''; ?>>Japan</option>
                    <option value="2616" <?php echo ($blog_location == "2616") ? 'selected' : ''; ?>>Poland</option>
                    <option value="2528" <?php echo ($blog_location == "2528") ? 'selected' : ''; ?>>Netherlands</option>
                </select>

                <select name="language" class="custom-select">
                    <option value="English" <?php echo ($blog_lang == "English") ? 'selected' : ''; ?>>English</option>
                    <option value="Spanish" <?php echo ($blog_lang == "Spanish") ? 'selected' : ''; ?>>Spanish</option>
                    <option value="German" <?php echo ($blog_lang == "German") ? 'selected' : ''; ?>>German</option>
                    <option value="French" <?php echo ($blog_lang == "French") ? 'selected' : ''; ?>>French</option>
                    <option value="Portuguese" <?php echo ($blog_lang == "Portuguese") ? 'selected' : ''; ?>>Portuguese</option>
                    <option value="Russian" <?php echo ($blog_lang == "Russian") ? 'selected' : ''; ?>>Russian</option>
                    <option value="Italian" <?php echo ($blog_lang == "Italian") ? 'selected' : ''; ?>>Italian</option>
                    <option value="Indonesian" <?php echo ($blog_lang == "Indonesian") ? 'selected' : ''; ?>>Indonesian</option>
                    <option value="Japanese" <?php echo ($blog_lang == "Japanese") ? 'selected' : ''; ?>>Japanese</option>
                    <option value="Polish" <?php echo ($blog_lang == "Polish") ? 'selected' : ''; ?>>Polish</option>
                    <option value="Dutch" <?php echo ($blog_lang == "Dutch") ? 'selected' : ''; ?>>Dutch</option>
                </select>                
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Generate new proposals</button>
            </div>
        </div>
    </form>

    <h4>Proposed new keywords <?php if ($entered_keyword) echo '(for '.esc_attr($entered_keyword).')'; ?></h4>
<?php
   $recent_keywords = get_option('blogcopilot_recent_keyword_research', []);

   if (!empty($recent_keywords)) {
       echo '<p>Recent checks: ';
       foreach ($recent_keywords as $keyword) {
           $display_keyword = (strlen($keyword) > 15) ? substr($keyword, 0, 15) . '...' : $keyword;
           $keyword = htmlspecialchars($keyword ?? '');
           $nonce = wp_create_nonce('research-seo-keyword');
           $url = esc_url(add_query_arg([
               'page' => 'blogcopilot-view-rankings',
               'action' => 'seo_keyword_research',
               'keywords' => $keyword,
               '_wpnonce' => $nonce
           ], admin_url('admin.php')));

           echo '<span><a href="' . esc_url($url) . '" data-nonce="' . esc_attr(wp_create_nonce('research-seo-keyword')) . '">' . esc_html($display_keyword) . ' </a> | </span>';
       }
       echo '</p>';     
   }
?>    
    
    <div class="card table-responsive" style="max-width: 100%; top-margin: 5px; padding: 0.2em">
    <table id="keywordReasearchTable" class="table table-striped mb-0">
    <thead class="thead-dark">
        <tr>
            <th class="text-uppercase text-secondary text-xs font-weight-bolder">Keyword</th>
            <th class="text-uppercase text-secondary text-xs font-weight-bolder">Last Month Search</th>
            <th class="text-uppercase text-secondary text-xs font-weight-bolder">CPC</th>
            <th class="text-uppercase text-secondary text-xs font-weight-bolder">Competition</th>
            <th class="text-uppercase text-secondary text-xs font-weight-bolder">Competition Index</th>
            <th class="text-uppercase text-secondary text-xs font-weight-bolder">Search Volume</th>
            <th class="text-uppercase text-secondary text-xs font-weight-bolder"></th>
        </tr>
    </thead>
    <tbody>
    <?php
    if ($keywords) {
        foreach ($keywords as $keyword) {
            $keywordText = esc_html(htmlspecialchars($keyword['Keyword'] ?? ''));
            $monthlySearchVolume = esc_html(htmlspecialchars($keyword['MonthlySearchVolume'] ?? ''));
            $cpc = esc_html(htmlspecialchars($keyword['CPC'] ?? ''));
            $competition = esc_html(htmlspecialchars($keyword['Competition'] ?? ''));
            $competitionIndex = esc_html(htmlspecialchars($keyword['CompetitionIndex'] ?? ''));
            $searchVolume = esc_html(htmlspecialchars($keyword['SearchVolume'] ?? ''));
    
            echo '<tr>';
            echo '<td><p class="text-s text-secondary mb-0">' . esc_attr($keywordText) . '</p></td>';
            echo '<td><p class="text-s text-secondary mb-0">' . esc_attr($monthlySearchVolume) . '</p></td>';
            echo '<td><p class="text-s text-secondary mb-0">' . esc_attr($cpc) . '</p></td>';
            echo '<td><p class="text-s text-secondary mb-0">' . esc_attr($competition) . '</p></td>';
            echo '<td><p class="text-s text-secondary mb-0">' . esc_attr($competitionIndex) . '</p></td>';
            echo '<td><p class="text-s text-secondary mb-0">' . esc_attr($searchVolume) . '</p></td>';
            echo '<td><a href="#" class="btn btn-outline-info btn-sm ms-2 generate-article" data-keyword="' . esc_attr(htmlspecialchars($keyword['Keyword'] ?? '')) . '" data-nonce="' . esc_attr(wp_create_nonce('create-article-' . htmlspecialchars($keyword['Keyword'] ?? ''))) . '">Generate&nbspArticle</a></td>';
            echo '</tr>';
        }
    }
    ?>
    </tbody>
    </table>

    </div>
</div>
</div>
</div>
<?php
}


function blogcopilot_io_seo_competition_content() {
    if (blogcopilot_io_seo_home_check_plan(1) == false) return;

    $competitorsData = blogcopilot_io_call_competitors(); 

    if (empty($competitorsData) || isset($competitorsData['error'])) {
        echo "<div class='alert alert-warning'>Unable to fetch competitor data or no data available.</div>";
        return;
    }
?>
    <div class="container card">
        <div class="row">
            <div class="col-md-12 m-auto my-3 ps-4">
                <h4>List of Competing Domains</h4>
                <div class="table-responsive" style="padding: 0.2em">
                    <table id="competitorsTable" class="table align-items-center mb-0">
                        <thead>
                            <tr>
                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Competitor</th>
                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7 text-right">Intersections</th>
                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7 text-right">Top 3</th>
                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7 text-right">Top 10</th>
                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7 text-right">Top 100</th>
                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7 text-right">Avg Position</th>
                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7 text-right">Estimated Traffic</th>
                            </tr>
                        </thead>
                        <tbody>
<?php
                        foreach ($competitorsData as $competitor) {
                            echo '<tr>';
                            echo '<td><p class="text-secondary mb-0">' . esc_html($competitor['CompetitorDomain']) . '</p></td>';
                            echo '<td class="text-right"><p class="text-secondary mb-0">' . number_format(intval($competitor['Intersections']), 0, '.', ' ') . '</p></td>';
                            echo '<td class="text-right"><p class="text-secondary mb-0">' . number_format(intval($competitor['RankingKeywords_Top3']), 0, '.', ' ') . '</p></td>';
                            echo '<td class="text-right"><p class="text-secondary mb-0">' . number_format(intval($competitor['RankingKeywords_Top10']), 0, '.', ' ') . '</p></td>';
                            echo '<td class="text-right"><p class="text-secondary mb-0">' . number_format(intval($competitor['RankingKeywords_Top100']), 0, '.', ' ') . '</p></td>';
                            echo '<td class="text-right"><p class="text-secondary mb-0">' . number_format(round(floatval($competitor['AvgPosition'])), 0, '.', ' ') . '</p></td>';
                            echo '<td class="text-right"><p class="text-secondary mb-0">' . number_format(intval($competitor['EstimatedTraffic']), 0, '.', ' ') . '</p></td>';
                
                            echo '</tr>';
                        }
?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php
}

function blogcopilot_io_seo_site_audit_content() {
    if (blogcopilot_io_seo_home_check_plan(1) == false) return;

    $auditData = blogcopilot_io_call_on_page_summary();

    $display_message = "";

    if (empty($auditData) || !isset($auditData['status'])) {
        $display_message = "Unable to fetch website audit data, license problem or no data available. Contact our support please.";
    } elseif (isset($auditData['status']) && $auditData['status'] == 'error') {
        $display_message = "Unable to fetch website audit data or no data available.";
        $display_message .= esc_html($auditData['error']);     
    } elseif (isset($auditData['status']) && $auditData['status'] == 'in progress') {
        $display_message = "Your website analysis is in progress. It was submitted at ".esc_html($auditData['data']['DateRecorded']).". Please check in a moment (usually analysis takes about 1 hour).";
    } elseif (isset($auditData['status']) && $auditData['status'] == 'submitted') {
        $display_message = "Your website analysis was sumbitted and is in progress. Please check in a moment (usually analysis takes about 1 hour).";
    } 
?>
    <div class="container card">
        <div class="row">
            <div class="col-md-12 m-auto my-3 ps-4">
                <h4>Website Audit Overview (main domain)</h4>
                <div class="table-responsive" style="padding: 0.2em">
<?php 
    if ($display_message != "") {
        echo esc_attr($display_message);
    } else {
        $seoData = json_decode($auditData['data']['SEOData'], true);
        $summaryData = $seoData['summary'];
        $pagesData = $seoData['pages'];
?>                        
    <h5 class="mt-4">Domain and URL Information</h5>
    <table class="table table-striped">
        <tbody>
            <tr>
                <th scope="row">Domain</th>
                <td><?php echo esc_html($summaryData['domain_info']['name']); ?></td>
            </tr>
            <tr>
                <th scope="row">URL</th>
                <td><?php echo esc_html($pagesData[0]['url']); ?></td>
            </tr>
            <tr>
                <th scope="row">On-Page Score</th>
                <td><?php echo esc_html($summaryData['page_metrics']['onpage_score']); ?></td>
            </tr>
            <tr>
                <th scope="row">Title</th>
                <td><?php echo esc_html($seoData['pages'][0]['meta']['title']); ?></td>
            </tr>
            <tr>
                <th scope="row">Description</th>
                <td><?php echo esc_html($seoData['pages'][0]['meta']['description']); ?></td>
            </tr>
            <tr>
                <th scope="row">Canonical</th>
                <td><?php echo esc_html($seoData['pages'][0]['meta']['canonical']); ?></td>
            </tr>
            <tr>
                <th scope="row">Favicon</th>
                <td><img src="<?php echo esc_url($seoData['pages'][0]['meta']['favicon']); ?>" alt="Favicon"></td>
            </tr>            
            <tr>
                <th scope="row">Date Checked</th>
                <td><?php echo esc_html($summaryData['domain_info']['crawl_start']); ?></td>
            </tr>
        </tbody>
    </table>

    <h5 class="mt-4">Server and SSL Information</h5>
    <table class="table table-striped">
        <tbody>
            <tr>
                <th scope="row">Server</th>
                <td><?php echo esc_html($summaryData['domain_info']['server']); ?></td>
            </tr>
            <tr>
                <th scope="row">IP Address</th>
                <td><?php echo esc_html($summaryData['domain_info']['ip']); ?></td>
            </tr>
            <tr>
                <th scope="row">SSL Valid</th>
                <td><?php echo esc_html($summaryData['domain_info']['ssl_info']['valid_certificate'] ? 'Yes' : 'No'); ?></td>
            </tr>
            <tr>
                <th scope="row">SSL Issuer</th>
                <td><?php echo esc_html($summaryData['domain_info']['ssl_info']['certificate_issuer']); ?></td>
            </tr>
            <tr>
                <th scope="row">SSL Expiration Date</th>
                <td><?php echo esc_html($summaryData['domain_info']['ssl_info']['certificate_expiration_date']); ?></td>
            </tr>
        </tbody>
    </table>

    <h5 class="mt-4">Technical and Performance Metrics</h5>
    <table class="table table-striped">
        <tbody>
            <tr>
                <th scope="row">Time to Interactive</th>
                <td><?php echo esc_html($pagesData[0]['page_timing']['time_to_interactive']); ?> ms</td>
            </tr>
            <tr>
                <th scope="row">DOM Complete</th>
                <td><?php echo esc_html($pagesData[0]['page_timing']['dom_complete']); ?> ms</td>
            </tr>
            <tr>
                <th scope="row">Largest Contentful Paint</th>
                <td><?php echo esc_html($pagesData[0]['page_timing']['largest_contentful_paint']); ?> ms</td>
            </tr>
            <tr>
                <th scope="row">First Input Delay</th>
                <td><?php echo esc_html($pagesData[0]['page_timing']['first_input_delay']); ?> ms</td>
            </tr>
            <tr>
                <th scope="row">Total DOM Size</th>
                <td><?php echo esc_html($pagesData[0]['total_dom_size']); ?> bytes</td>
            </tr>
            <tr>
                <th scope="row">Total Transfer Size</th>
                <td><?php echo esc_html($pagesData[0]['total_transfer_size']); ?> bytes</td>
            </tr>
        </tbody>
    </table>

    <h5 class="mt-4">SEO and Content Quality</h5>
    <table class="table table-striped">
        <tbody>
            <tr>
                <th scope="row">Internal Links</th>
                <td><?php echo esc_html($pagesData[0]['meta']['internal_links_count']); ?></td>
            </tr>
            <tr>
                <th scope="row">External Links</th>
                <td><?php echo esc_html($pagesData[0]['meta']['external_links_count']); ?></td>
            </tr>
            <tr>
                <th scope="row">Plain Text Word Count</th>
                <td><?php echo esc_html($pagesData[0]['meta']['content']['plain_text_word_count']); ?></td>
            </tr>
            <tr>
                <th scope="row">Duplicate Titles</th>
                <td><?php echo esc_html($seoData['summary']['page_metrics']['duplicate_title']); ?></td>
            </tr>
            <tr>
                <th scope="row">Broken Links</th>
                <td><?php echo esc_html($seoData['summary']['page_metrics']['broken_links']); ?></td>
            </tr>
            <tr>
                <th scope="row">Duplicate Content</th>
                <td><?php echo esc_html($seoData['summary']['page_metrics']['duplicate_content']); ?></td>
            </tr>            
            <tr>
                <th scope="row">Automated Readability Index</th>
                <td><?php echo esc_html($pagesData[0]['meta']['content']['automated_readability_index']); ?></td>
            </tr>
            <tr>
                <th scope="row">Flesch-Kincaid Index</th>
                <td><?php echo esc_html($pagesData[0]['meta']['content']['flesch_kincaid_readability_index']); ?></td>
            </tr>
        </tbody>
    </table>

    <h5 class="mt-4">Compliance and Optimization Checks</h5>
    <table class="table table-striped">
        <tbody>
            <tr>
                <th scope="row">SEO Friendly URL</th>
                <td><?php echo esc_html($pagesData[0]['checks']['seo_friendly_url'] ? 'Yes' : 'No'); ?></td>
            </tr>
            <tr>
                <th scope="row">No H1 Tag</th>
                <td><?php echo esc_html($pagesData[0]['checks']['no_h1_tag'] ? 'No' : 'Yes'); ?></td>
            </tr>
            <tr>
                <th scope="row">No Image Alt Tag</th>
                <td><?php echo esc_html($pagesData[0]['checks']['no_image_alt'] ? 'No' : 'Yes'); ?></td>
            </tr>
            <tr>
                <th scope="row">No Image Title</th>
                <td><?php echo esc_html($pagesData[0]['checks']['no_image_title'] ? 'No' : 'Yes'); ?></td>
            </tr>
            <tr>
                <th scope="row">Canonicalization</th>
                <td><?php echo esc_html($pagesData[0]['checks']['canonical'] ? 'Yes' : 'No'); ?></td>
            </tr>
            <tr>
                <th scope="row">Has Render Blocking Resources</th>
                <td><?php echo esc_html($pagesData[0]['checks']['has_render_blocking_resources'] ? 'Yes' : 'No'); ?></td>
            </tr>
            <tr>
                <th scope="row">High Loading Time</th>
                <td><?php echo esc_html($pagesData[0]['checks']['high_loading_time'] ? 'Yes' : 'No'); ?></td>
            </tr>
        </tbody>
    </table>

    <h5 class="mt-4">Links Information</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th scope="col">Type</th>
                <th scope="col">Link To</th>
                <th scope="col">Direction</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $limit = 100;
                $currentLinks = array_slice($seoData['links'], 0, $limit);
                foreach ($currentLinks as $link) : ?>
                <tr>
                    <td><?php echo esc_html($link['type']); ?></td>
                    <td><?php echo esc_html($link['link_to']); ?></td>
                    <td><?php echo esc_html($link['direction']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>    

<?php 
    }
?>  
                </div>
            </div>
        </div>
    </div>
<?php
}

?>
