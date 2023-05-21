<?php
/*
Plugin Name: AnimHQ Series/Movies Addon
Description: Add Custom fields to the post edit page to easily add episodes and seasons for series 
Version: 1.0
Author: Dada
Author URI: AnimHQ
*/


global $wpdb;

$db_name = $wpdb->dbname;
$db_user = $wpdb->dbuser;
$db_password = $wpdb->dbpassword;
$db_host = $wpdb->dbhost;

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$seasons_table_name = 'seasons';
$episodes_table_name = 'episodes';

$charset_collate = $wpdb->get_charset_collate();

// Create episodes table
$episodes_table_sql = "CREATE TABLE IF NOT EXISTS $episodes_table_name (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    video VARCHAR(255) NOT NULL,
    seasonId INT(11) UNSIGNED NOT NULL,
    eorder INT(11) UNSIGNED NOT NULL,
    cover VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
) $charset_collate;";
$pdo->exec($episodes_table_sql);

// Create seasons table
$seasons_table_sql = "CREATE TABLE IF NOT EXISTS $seasons_table_name (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    serieId INT(11) UNSIGNED NOT NULL,
    sorder INT(11) UNSIGNED NOT NULL,
    cover VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
) $charset_collate;";
$pdo->exec($seasons_table_sql);


// Add custom tab and fields to the post form
function add_custom_tab_and_fields() {
    add_action('edit_form_after_editor', 'render_custom_tab_fields');
    add_action('admin_enqueue_scripts', 'enqueue_custom_tab_scripts');
}
add_action('load-post.php', 'add_custom_tab_and_fields');
add_action('load-post-new.php', 'add_custom_tab_and_fields');

// Render custom tab fields
function render_custom_tab_fields() {
    global $post, $pdo;

    // Add a nonce field for security
    wp_nonce_field('custom_tab_nonce', 'custom_tab_nonce');

    // Output custom fields
    $maxSIds = 0;
    $maxEIds = 0;
    
    // get max Season id
    $query = "SELECT id FROM seasons order by id DESC LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $rowCount = $stmt->rowCount();
    if($rowCount > 0) {
        $maxSIds = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    } 

     // get max Episode id
    $query = "SELECT id FROM episodes order by id DESC LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $rowCount = $stmt->rowCount();
    if($rowCount > 0) {
        $maxEIds = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    } 
    
    
    ?>
    <div id="custom-tab" class="postbox postboxanimhhq" >
        <div class="postbox-header animhqPostHeader">
            <h2 class="hndle"><span>Seasons & Episodes</span></h2>
            <div class="handle-actions animhq_Actions">
                <div class='animhq_addButton' id="animhqAddSeason">+ Season</div>
            </div>
        </div>
        <div class="inside">
            <input type="hidden" id="maxSids" value="<?php echo $maxSIds; ?>" />
        <input type="hidden" id="maxEids" value="<?php echo $maxEIds; ?>" />
            <?php
            // Retrieve seasons for the current post
            $query = "SELECT * FROM seasons WHERE serieId = :post_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':post_id', $post->ID, PDO::PARAM_INT);
            $stmt->execute();
            $seasons = $stmt->fetchAll(PDO::FETCH_OBJ);

            if ($seasons) {
                foreach ($seasons as $season) {
                    $season_id = $season->id;
                    $season_name = $season->name;
                    $season_order = $season->sorder;
                    $season_cover = $season->cover;

                    ?>
                    <div class="animhq_Season_tab" id="season_<?php echo $season_id; ?>">
                        <div class="animhq_Season_header">
                            <span class="dropandInput">
                                <div class="animhq_DropdownButton">▼</div>
                                <input type="text" name="seasons[<?php echo $season_id; ?>][name]" value="<?php echo $season_name; ?>" placeholder="Season Name" />
                            </span>
                            <input type="hidden" name="seasons[<?php echo $season_id; ?>][id]" value="<?php echo $season_id; ?>" />
                            <div class='animhq_addButton animhqAddEpisode'>+ Episode</div>
                        </div>
                        <div class="animhq_Season_body">
                            <input type="hidden" name="seasons[<?php echo $season_id; ?>][oldcover]" value="<?php echo $season_cover; ?>" />
                            <div class="animhq_Season_body_fields">
                                <Label>Season Order:</Label>
                                <input type="text" name="seasons[<?php echo $season_id; ?>][order]" value="<?php echo $season_order; ?>" placeholder="Season Order" />
                                <Label>Season Cover:</Label>
                                <input type="file" name="seasons[<?php echo $season_id; ?>][cover]" accept="image/*" />
                            <img src="<?php  echo $season_cover ?>" width="100" />
                            </div>
                            <h3>Episodes:</h3>
                            
                            <?php
                            // Retrieve episodes for the current season
                            $query = "SELECT * FROM episodes WHERE seasonId = :season_id";
                            $stmt = $pdo->prepare($query);
                            $stmt->bindParam(':season_id', $season_id, PDO::PARAM_INT);
                            $stmt->execute();
                            $episodes = $stmt->fetchAll(PDO::FETCH_OBJ);

                            if ($episodes) {
                                foreach ($episodes as $episode) {
                                    $episode_id = $episode->id;
                                    $episode_name = $episode->name;
                                    $episode_order = $episode->eorder;
                                    $episode_quality = $episode->cover;
                                    $episode_video = $episode->video;

                                    ?>
                                    <div class="animhq_Episode_tab" season="<?php echo $season_id; ?>">
                                         <div class="animhq_Episode_header">
                                            <span class="dropandInput">
                                            <div class="animhq_DropdownButton">▼</div>
                                             <input type="text" name="seasons[<?php echo $season_id; ?>][episodes][<?php echo $episode_id; ?>][name]" value="<?php echo $episode_name; ?>" placeholder="Episode Name" />
                                            </span>
                                            <input type="hidden" name="seasons[<?php echo $season_id; ?>][episodes][<?php echo $episode_id; ?>][id]" value="<?php echo $episode_id; ?>" />
                                        </div>
                                        <div class="animhq_Episode_body">
                                            <input type="text" name="seasons[<?php echo $season_id; ?>][episodes][<?php echo $episode_id; ?>][order]" value="<?php echo $episode_order; ?>" placeholder="Episode Order" />
                                            <input type="text" name="seasons[<?php echo $season_id; ?>][episodes][<?php echo $episode_id; ?>][quality]" value="<?php echo $episode_quality; ?>" placeholder="Episode Quality" />
                                            <input type="text" name="seasons[<?php echo $season_id; ?>][episodes][<?php echo $episode_id; ?>][video]" value="<?php echo $episode_video; ?>" placeholder="Episode video" />
                                        </div>
                                    </div>

                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
    <?php
}

// Enqueue scripts and styles for the custom tab
function enqueue_custom_tab_scripts() {
    wp_enqueue_style('custom-tab-styles', plugins_url('animhqPlug.css', __FILE__));
    wp_enqueue_script('custom-tab-script', plugins_url('animhqPlug.js', __FILE__), array('jquery'), '', true);
}


function checkExistingSeason($sid) {
    global $wpdb, $pdo;
    $query = "SELECT * FROM seasons where id = :season_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':season_id', $sid, PDO::PARAM_INT);
    $stmt->execute();
    if($stmt->rowCount() > 0) return true;
    return false;
}

function checkExistingEpisode($eid) {
    global $wpdb, $pdo;
    $query = "SELECT * FROM episodes where id = :season_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':season_id', $eid, PDO::PARAM_INT);
    $stmt->execute();
    if($stmt->rowCount() > 0) return true;
    return false;
}

// Save season and episode data
function save_season_episode_data($post_id) {
    global $wpdb, $pdo;

    // Verify the nonce
    if (!isset($_POST['custom_tab_nonce']) || !wp_verify_nonce($_POST['custom_tab_nonce'], 'custom_tab_nonce')) {
        return;
    }

    // Check if the current user has permission to save the data
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }


    // Save seasons
    if (isset($_POST['seasons'])) {
        $seasons = $_POST['seasons'];
        
        foreach ($seasons as $season_data) {
            $season_id = isset($season_data['id']) ? intval($season_data['id']) : 0;
            $season_name = isset($season_data['name']) ? sanitize_text_field($season_data['name']) : '';
            $season_order = isset($season_data['order']) ? intval($season_data['order']) : 0;
            
            $season_cover = '';

            if (isset($_FILES['seasons']) && isset($_FILES['seasons']['tmp_name'][$season_id]['cover'])) {
                $file = $_FILES['seasons']['tmp_name'][$season_id]['cover'];
                $ext = explode('/' , $_FILES['seasons']['type'][$season_id]['cover'])[1];
                if (!empty($file) && file_exists($file)) {
                    $tmpfile = $file;
                    $file = explode('.',$file)[0];
                    $upload_dir = wp_upload_dir();
                    $target_dir = $upload_dir['path'] . '/';
                    $target_file = $target_dir . basename($file).'.'.$ext;
                
                    // Generate a unique filename
                    $unique_filename = wp_unique_filename($target_dir, basename($target_file));
                
                    // Set the final file path
                    $target_file = $target_dir . $unique_filename;
                    
                    // Move the uploaded file to the target location
                    if (move_uploaded_file($tmpfile, $target_file)) {
                        // Set the season cover value to the target file path
                        $season_cover = $upload_dir['url'] . '/' . $unique_filename;
                    }
                }
            }

            if($season_cover == '') $season_cover = $season_data['oldcover'];

            if (checkExistingSeason($season_id)) {
             
                $query = "UPDATE seasons SET cover=:cover, name = :season_name, sorder = :season_order WHERE id = :season_id";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':season_name', $season_name, PDO::PARAM_STR);
                $stmt->bindParam(':season_order', $season_order, PDO::PARAM_INT);
                $stmt->bindParam(':season_id', $season_id, PDO::PARAM_INT);
                $stmt->bindParam(':cover', $season_cover, PDO::PARAM_STR);
                $stmt->execute();
            } else {
                $query = "INSERT INTO seasons (id, name, serieId, sorder, cover) VALUES (:season_id ,:season_name, :serie_id, :season_order, :cover)";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':season_name', $season_name, PDO::PARAM_STR);
                $stmt->bindParam(':season_id', $season_id, PDO::PARAM_INT);
                $stmt->bindParam(':serie_id', $post_id, PDO::PARAM_INT);
                $stmt->bindParam(':season_order', $season_order, PDO::PARAM_INT);
                $stmt->bindParam(':cover', $season_cover, PDO::PARAM_STR);
                $stmt->execute();
                $season_id = $pdo->lastInsertId();
            }

       
          
                $episodes = $season_data['episodes'];

                foreach ($episodes as $episode_data) {
                    $episode_id = isset($episode_data['id']) ? intval($episode_data['id']) : 0;
                    $episode_name = isset($episode_data['name']) ? sanitize_text_field($episode_data['name']) : '';
                    $episode_video = isset($episode_data['video']) ? sanitize_text_field($episode_data['video']) : '';
                    $episode_quality = isset($episode_data['quality']) ? sanitize_text_field($episode_data['quality']) : '';
                    $episode_order = isset($episode_data['order']) ? intval($episode_data['order']) : 0;

                    if (checkExistingEpisode($episode_id)) {
                        $query = "UPDATE episodes SET name = :episode_name, eorder = :episode_order, cover=:quality, video=:video WHERE id = :episode_id";
                        $stmt = $pdo->prepare($query);
                        $stmt->bindParam(':episode_name', $episode_name, PDO::PARAM_STR);
                        $stmt->bindParam(':episode_order', $episode_order, PDO::PARAM_INT);
                        $stmt->bindParam(':video', $episode_video, PDO::PARAM_STR);
                        $stmt->bindParam(':quality', $episode_quality, PDO::PARAM_STR);
                        $stmt->bindParam(':episode_id', $episode_id, PDO::PARAM_INT);
                        $stmt->execute();
                    } else {
                        $query = "INSERT INTO episodes (id, name, video, seasonId, eorder,cover) VALUES (:episode_id ,:episode_name,:video ,:season_id, :episode_order, :quality)";
                        $stmt = $pdo->prepare($query);
                        $stmt->bindParam(':episode_name', $episode_name, PDO::PARAM_STR);
                        $stmt->bindParam(':video', $episode_video, PDO::PARAM_STR);
                        $stmt->bindParam(':quality', $episode_quality, PDO::PARAM_STR);
                        $stmt->bindParam(':episode_id', $episode_id, PDO::PARAM_INT);
                        $stmt->bindParam(':season_id', $season_id, PDO::PARAM_INT);
                        $stmt->bindParam(':episode_order', $episode_order, PDO::PARAM_INT);
                        $stmt->execute();
                    }
                }
            
        }
    }
}
add_action('save_post', 'save_season_episode_data');