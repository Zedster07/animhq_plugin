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
$movies_table_name = 'movies';
$usersScreens_table_name = 'screens';
$loggins_table_name = 'loggins';
$plans_table_name = 'ahq_plans';

$charset_collate = $wpdb->get_charset_collate();


$episodes_table_sql = "CREATE TABLE IF NOT EXISTS $plans_table_name (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    plan_name VARCHAR(255),
    screens Int(11),
    candownload boolean,
    quality_720p boolean,
    quality_1080 boolean,
    quality_2k boolean,
    quality_4k boolean,
    PRIMARY KEY (id)
) $charset_collate;";
$pdo->exec($episodes_table_sql);

$episodes_table_sql = "CREATE TABLE IF NOT EXISTS $loggins_table_name (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(255),
    user_id Int(11),
    ipaddress varchar(255),
    PRIMARY KEY (id)
) $charset_collate;";
$pdo->exec($episodes_table_sql);

$episodes_table_sql = "CREATE TABLE IF NOT EXISTS $usersScreens_table_name (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(255),
    user_id Int(11),
    screens INT(11),
    subscriptionId INT(11),
    PRIMARY KEY (id)
) $charset_collate;";
$pdo->exec($episodes_table_sql);

// Create episodes table
$episodes_table_sql = "CREATE TABLE IF NOT EXISTS $episodes_table_name (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    video_720 varchar(2500),
    video_1080 varchar(2500),
    video_4k varchar(2500),
    video_2k varchar(2500),
    isFree boolean,
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

$seasons_table_sql = "CREATE TABLE IF NOT EXISTS $movies_table_name (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    postId INT(11) UNSIGNED NOT NULL,
    quality varchar(255) NOT NULL,
    isFree boolean,
    video_720 varchar(2500),
    video_1080 varchar(2500),
    video_4k varchar(2500),
    video_2k varchar(2500),
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
    $maxPIds = 0;
    
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


    $query = "SELECT id FROM ahq_plans order by id DESC LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $rowCount = $stmt->rowCount();
    if($rowCount > 0) {
        $maxPIds = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    } 
    
    ?>
    <?php if($post->post_type == "serie"){?>
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
                            <div class="animhq_Season_body_episodes">
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
                                    $episode_video_720 = $episode->video_720;
                                    $episode_video_1080 = $episode->video_1080;
                                    $episode_video_2k = $episode->video_2k;
                                    $episode_video_4k = $episode->video_4k;
                                    $episode_isFree = $episode->isFree;

                                    ?>
                                    <div class="animhq_Episode_tab" season="<?php echo $season_id; ?>">
                                         <div class="animhq_Episode_header">
                                            <span class="dropandInput">
                                                <div class="animhq_DropdownButton">▼</div>
                                                <input type="text" name="seasons[<?php echo $season_id; ?>][episodes][<?php echo $episode_id; ?>][name]" value="<?php echo $episode_name; ?>" placeholder="Episode Name" />
                                            </span>
                                            <span>
                                                <input type="checkbox" id="isFree<?php echo $episode_id; ?>" name="seasons[<?php echo $season_id; ?>][episodes][<?php echo $episode_id; ?>][isFree]" <?php echo($episode_isFree ? 'checked' : "" ); ?> > 
                                                <label style="color:white;" for="isFree<?php echo $season_id; ?>">Free Content</label>
                                            </span>
                                            
                                            <input type="hidden" name="seasons[<?php echo $season_id; ?>][episodes][<?php echo $episode_id; ?>][id]" value="<?php echo $episode_id; ?>" />
                                        </div>
                                        <div class="animhq_Episode_body">
                                            <input type="text" name="seasons[<?php echo $season_id; ?>][episodes][<?php echo $episode_id; ?>][order]" value="<?php echo $episode_order; ?>" placeholder="Episode Order" />
                                            <input type="text" name="seasons[<?php echo $season_id; ?>][episodes][<?php echo $episode_id; ?>][quality]" value="<?php echo $episode_quality; ?>" placeholder="Episode Quality" />
                                            <input type="text" name="seasons[<?php echo $season_id; ?>][episodes][<?php echo $episode_id; ?>][video_720]" value="<?php echo $episode_video_720; ?>" placeholder="Episode Stream Video 720p" />
                                            <input type="text" name="seasons[<?php echo $season_id; ?>][episodes][<?php echo $episode_id; ?>][video_1080]" value="<?php echo $episode_video_1080; ?>" placeholder="Episode Stream Video 1080p" />
                                            <input type="text" name="seasons[<?php echo $season_id; ?>][episodes][<?php echo $episode_id; ?>][video_2k]" value="<?php echo $episode_video_2k; ?>" placeholder="Episode Stream Video 2k" />
                                            <input type="text" name="seasons[<?php echo $season_id; ?>][episodes][<?php echo $episode_id; ?>][video_4k]" value="<?php echo $episode_video_4k; ?>" placeholder="Episode Stream Video 4k" />
                                        </div>
                                    </div>

                                    <?php
                                }
                            }
                            ?>

                            </div>
                           
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
    <?php } else if ($post->post_type == "movie") { 
        
        $query = "SELECT * FROM movies WHERE postId = :post_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':post_id', $post->ID, PDO::PARAM_INT);
        $stmt->execute();
        $movie = $stmt->fetch(PDO::FETCH_OBJ);

        ?>
        <div id="custom-tab" class="postbox postboxanimhhq" >
            <div class="postbox-header animhqPostHeader">
                <h2 class="hndle"><span>Movie Details</span></h2>
                <input type="checkbox" id="isFree" name="movie[isFree]" <?php echo ($movie->isFree ? 'checked' : ''); ?>> 
                    <label for="isFree">Free Content</label>
            </div>
            <div class="inside">
                <div class="animhq_Episode_body">
                    <?php if($movie) { ?><img src="<?php  echo($movie->cover); ?>" width="100" /> <?php } ?>
                    <Label>Movie Cover:</Label>
                    
                    <input type="hidden" name="movie[id]" value="<?php echo($movie ? $movie->id : "");?>" />
                    <input type="hidden" name="movie[oldcover]" value="<?php echo($movie ? $movie->cover:"") ?>" />
                    <input type="file" name="movie[cover]" accept="image/*" />
                    <input type="text" name="movie[quality]" value="<?php echo($movie ? $movie->quality : ""); ?>" placeholder="Movie Quality" />
                    <input type="text" name="movie[video_720]" value="<?php echo($movie ? $movie->video_720: ""); ?>" placeholder="Movie video 720p" />
                    <input type="text" name="movie[video_1080]" value="<?php echo($movie ? $movie->video_1080: ""); ?>" placeholder="Movie video 1080p" />
                    <input type="text" name="movie[video_2k]" value="<?php echo($movie ? $movie->video_2k: ""); ?>" placeholder="Movie video 2k" />
                    <input type="text" name="movie[video_4k]" value="<?php echo($movie ? $movie->video_4k: ""); ?>" placeholder="Movie video 4k" />
                </div>
            </div>
        </div>
    <?php } else if($post->post_type == "pms-subscription") {
        ?>
            <div id="custom-tab" class="postbox postboxanimhhq" >
                <div class="postbox-header animhqPostHeader">
                    <h2 class="hndle"><span>AnimeHQ Plans Settings</span></h2>
                    <div class="handle-actions animhq_Actions">
                        <div class='animhq_addButton' id="animhqAddPlan">+ Plan</div>
                    </div>
                </div>
                <div class="inside">
                    <input type="hidden" id="maxPids" value="<?php echo $maxPIds; ?>" />
                    <?php 

                        $query = "SELECT * FROM ahq_plans";
                        $stmt = $pdo->prepare($query);
                        //$stmt->bindParam(':post_id', $post->ID, PDO::PARAM_INT);
                        $stmt->execute();
                        $plans = $stmt->fetchAll(PDO::FETCH_OBJ);
                    if($plans) {
                        foreach ($plans as $plan) {
                            
                        
                    
                    ?>
                    <div class="animhq_Season_tab">
                        <div class="animhq_Season_header">
                            <span class="dropandInput">
                                <div class="animhq_DropdownButton">▼</div>
                                <input type="text" name="plans[<?php echo($plan->id); ?>][name]" value="<?php echo($plan->plan_name); ?>" placeholder="Plan Name" />
                            </span>
                            <input type="hidden" name="plans[<?php echo($plan->id); ?>][id]" value=<?php echo($plan->id); ?>"" />
                        </div>
                        <div class="animhq_Episode_body">
                            <input type="text" name="plans[<?php echo($plan->id); ?>][screens]" value="<?php echo($plan->screens); ?>" placeholder="Number of Screens" />

                            <lable for="candownload">Can Download </label>
                            <input type="checkbox" id="candownload" name="plans[<?php echo($plan->id); ?>][candownload]" <?php echo($plan->candownload ? 'checked' : ''); ?> />

                            <lable for="quality720">Have Quality 720p </label>
                            <input type="checkbox" id="quality720" name="plans[<?php echo($plan->id); ?>][quality_720p]" <?php echo($plan->quality_720p ? 'checked' : ''); ?> />

                            <lable for="quality1080">Have Quality 1080p </label>
                            <input type="checkbox" id="quality1080" name="plans[<?php echo($plan->id); ?>][quality_1080]" <?php echo($plan->quality_1080 ? 'checked' : ''); ?> />

                            <lable for="quality2k">Have Quality 2K </label>
                            <input type="checkbox" id="quality2k" name="plans[<?php echo($plan->id); ?>][quality_2k]" <?php echo($plan->quality_2k ? 'checked' : ''); ?> />

                            <lable for="quality4k">Have Quality 4K </label>
                            <input type="checkbox" id="quality4k" name="plans[<?php echo($plan->id); ?>][quality_4k]" <?php echo($plan->quality_4k ? 'checked' : ''); ?> />
                        </div>
                    </div>
                    <?php }} ?>
                </div>
            </div>
        
    <?php }
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
function checkExistingMovie($mid) {
    global $wpdb, $pdo;
    $query = "SELECT * FROM movies where id = :movie_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':movie_id', $mid, PDO::PARAM_INT);
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
                        
                        $file_path = $season_cover;

                        // Prepare the attachment data
                        $attachment = array(
                            'guid'           => $file_path,
                            'post_title'     => basename($file_path),
                            'post_status'    => 'inherit'
                        );

                        // Insert the attachment
                        $attachment_id = wp_insert_attachment($attachment, $file_path);

                        // Generate metadata for the attachment
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);

                        // Update the metadata for the attachment
                        wp_update_attachment_metadata($attachment_id, $attachment_data);
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
                    $episode_video_720 = isset($episode_data['video_720']) ? sanitize_text_field($episode_data['video_720']) : '';
                    $episode_video_1080 = isset($episode_data['video_1080']) ? sanitize_text_field($episode_data['video_1080']) : '';
                    $episode_video_2k = isset($episode_data['video_2k']) ? sanitize_text_field($episode_data['video_2k']) : '';
                    $episode_video_4k = isset($episode_data['video_4k']) ? sanitize_text_field($episode_data['video_4k']) : '';
                    $episode_quality = isset($episode_data['quality']) ? sanitize_text_field($episode_data['quality']) : '';
                    $episode_order = isset($episode_data['order']) ? intval($episode_data['order']) : 0;
                    $episode_isFree = isset($episode_data['isFree']) ? ($episode_data['isFree'] == 'on' ? 1 : 0):0;

                    if (checkExistingEpisode($episode_id)) {
                        $query = "UPDATE episodes SET name = :episode_name, isFree =:isFree , eorder = :episode_order, cover=:quality, video_720=:video_720, video_1080=:video_1080,video_2k=:video_2k,video_4k=:video_4k WHERE id = :episode_id";
                        $stmt = $pdo->prepare($query);
                        $stmt->bindParam(':episode_name', $episode_name, PDO::PARAM_STR);
                        $stmt->bindParam(':episode_order', $episode_order, PDO::PARAM_INT);
                        $stmt->bindParam(':video_720', $episode_video_720, PDO::PARAM_STR);
                        $stmt->bindParam(':video_1080', $episode_video_1080, PDO::PARAM_STR);
                        $stmt->bindParam(':video_2k', $episode_video_2k, PDO::PARAM_STR);
                        $stmt->bindParam(':video_4k', $episode_video_4k, PDO::PARAM_STR);
                        $stmt->bindParam(':quality', $episode_quality, PDO::PARAM_STR);
                        $stmt->bindParam(':episode_id', $episode_id, PDO::PARAM_INT);
                        $stmt->bindParam(':isFree', $episode_isFree, PDO::PARAM_BOOL);
                        $stmt->execute();
                    } else {
                        $query = "INSERT INTO episodes (isFree, id, name, video_720, video_1080,video_2k,video_4k, seasonId, eorder,cover) VALUES (:isFree,:episode_id ,:episode_name,:video_720 ,:video_1080 ,:video_2k ,:video_4k ,:season_id, :episode_order, :quality)";
                        $stmt = $pdo->prepare($query);
                        $stmt->bindParam(':episode_name', $episode_name, PDO::PARAM_STR);
                        $stmt->bindParam(':video_720', $episode_video_720, PDO::PARAM_STR);
                        $stmt->bindParam(':video_1080', $episode_video_1080, PDO::PARAM_STR);
                        $stmt->bindParam(':video_2k', $episode_video_2k, PDO::PARAM_STR);
                        $stmt->bindParam(':video_4k', $episode_video_4k, PDO::PARAM_STR);
                        $stmt->bindParam(':quality', $episode_quality, PDO::PARAM_STR);
                        $stmt->bindParam(':episode_id', $episode_id, PDO::PARAM_INT);
                        $stmt->bindParam(':season_id', $season_id, PDO::PARAM_INT);
                        $stmt->bindParam(':episode_order', $episode_order, PDO::PARAM_INT);
                        $stmt->bindParam(':isFree', $episode_isFree, PDO::PARAM_BOOL);
                        $stmt->execute();
                    }
                }
            
        }
    }

    if(isset($_POST['movie'])) {
        $movie = $_POST['movie'];
        $movie_cover = '';
        
        if (isset($_FILES['movie']) && isset($_FILES['movie']['tmp_name']['cover'])) {
            $file = $_FILES['movie']['tmp_name']['cover'];
            $ext = explode('/' , $_FILES['movie']['type']['cover'])[1];
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
                    $movie_cover = $upload_dir['url'] . '/' . $unique_filename;
                    $file_path = $movie_cover;

                        // Prepare the attachment data
                        $attachment = array(
                            'guid'           => $file_path,
                            'post_title'     => basename($file_path),
                            'post_status'    => 'inherit'
                        );

                        $attach_id = wp_insert_attachment( $attachment, $file_path, 37 );
                        $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
                        wp_update_attachment_metadata( $attach_id,  $attach_data );
                }
            }
        }

        if($movie_cover == '') $movie_cover = $movie['oldcover'];
        $movie_is_free = isset($movie['isFree']) ? ($movie['isFree'] == 'on' ? 1 : 0):0;
        if (checkExistingMovie($movie['id'])) {
             
            $query = "UPDATE movies SET isFree=:isFree, cover=:cover, video_720 = :video_720,video_1080 = :video_1080,video_2k = :video_2k,video_4k = :video_4k, quality = :quality WHERE id = :movie_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':cover', $movie_cover, PDO::PARAM_STR);
            $stmt->bindParam(':quality', $movie['quality'], PDO::PARAM_STR);
            $stmt->bindParam(':video_720', $movie['video_720'], PDO::PARAM_STR);
            $stmt->bindParam(':video_1080', $movie['video_1080'], PDO::PARAM_STR);
            $stmt->bindParam(':video_2k', $movie['video_2k'], PDO::PARAM_STR);
            $stmt->bindParam(':video_4k', $movie['video_4k'], PDO::PARAM_STR);
            $stmt->bindParam(':isFree',$movie_is_free , PDO::PARAM_BOOL);
            $stmt->bindParam(':movie_id', $movie['id'], PDO::PARAM_INT);

            $stmt->execute();

        } else {
            $query = "INSERT INTO movies (postId, video_720, video_1080,video_2k,video_4k, quality , cover, isFree) VALUES (:postId ,:video_720,:video_1080,:video_2k,:video_4k, :quality, :cover, :isFree)";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':postId', $post_id, PDO::PARAM_INT);
            $stmt->bindParam(':isFree', $movie_is_free , PDO::PARAM_BOOL);
            $stmt->bindParam(':quality', $movie['quality'], PDO::PARAM_STR);
            $stmt->bindParam(':video_720', $movie['video_720'], PDO::PARAM_STR);
            $stmt->bindParam(':video_1080', $movie['video_1080'], PDO::PARAM_STR);
            $stmt->bindParam(':video_2k', $movie['video_2k'], PDO::PARAM_STR);
            $stmt->bindParam(':video_4k', $movie['video_4k'], PDO::PARAM_STR);
            $stmt->bindParam(':cover', $movie_cover, PDO::PARAM_STR);
            $stmt->execute();   
        }
    }
    if(isset($_POST['plans'])){
        $plans = $_POST['plans'];
        //print_r($plans);
       //die("");
        foreach ($plans as $plan) {
            $planId = isset($plan['id']) ? intval($plan['id']) : 0;
            $plan_name = isset($plan['name']) ? sanitize_text_field($plan['name']) : '';
            $plan_720 = isset($plan['quality_720p']) ? ($plan['quality_720p'] == 'on' ? 1 : 0):0;
            $plan_1080 = isset($plan['quality_1080']) ? ($plan['quality_1080'] == 'on' ? 1 : 0):0;
            $plan_2k = isset($plan['quality_2k']) ? ($plan['quality_2k'] == 'on' ? 1 : 0):0;
            $plan_4k = isset($plan['quality_4k']) ? ($plan['quality_4k'] == 'on' ? 1 : 0):0;
            $candownload = isset($plan['candownload']) ? ($plan['candownload'] == 'on' ? 1 : 0):0;
            $screens = isset($plan['screens']) ? intval($plan['screens']) : 0;

            if (checkExistingPlan($planId)) {
                $query = "UPDATE ahq_plans SET plan_name = :plan_name, quality_720p =:plan_720 ,quality_1080 = :plan_1080, quality_2k=:plan_2k, quality_4k=:plan_4k, candownload=:candownload , screens = :screens WHERE id = :plan_id";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':plan_id', $planId, PDO::PARAM_INT);
                $stmt->bindParam(':plan_name', $plan_name, PDO::PARAM_STR);
                $stmt->bindParam(':plan_720', $plan_720, PDO::PARAM_BOOL);
                $stmt->bindParam(':plan_1080', $plan_1080, PDO::PARAM_BOOL);
                $stmt->bindParam(':plan_2k', $plan_2k, PDO::PARAM_BOOL);
                $stmt->bindParam(':plan_4k', $plan_4k, PDO::PARAM_BOOL);
                $stmt->bindParam(':candownload', $candownload, PDO::PARAM_BOOL);
                $stmt->bindParam(':screens', $screens, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $query = "INSERT INTO ahq_plans (plan_name, id,  quality_720p, quality_1080,quality_2k,quality_4k, screens, candownload) VALUES (:plan_name,:plan_id ,:plan_720,:plan_1080 ,:plan_2k ,:plan_4k ,:screens , :candownload)";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':plan_id', $planId, PDO::PARAM_INT);
                $stmt->bindParam(':plan_name', $plan_name, PDO::PARAM_STR);
                $stmt->bindParam(':plan_720', $plan_720, PDO::PARAM_BOOL);
                $stmt->bindParam(':plan_1080', $plan_1080, PDO::PARAM_BOOL);
                $stmt->bindParam(':plan_2k', $plan_2k, PDO::PARAM_BOOL);
                $stmt->bindParam(':plan_4k', $plan_4k, PDO::PARAM_BOOL);
                $stmt->bindParam(':candownload', $candownload, PDO::PARAM_BOOL);
                $stmt->bindParam(':screens', $screens, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }
}

function checkExistingPlan($pid) {
    global $wpdb, $pdo;
    $query = "SELECT * FROM ahq_plans where id = :plan_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':plan_id', $pid, PDO::PARAM_INT);
    $stmt->execute();
    if($stmt->rowCount() > 0) return true;
    return false;  
}

add_action('save_post', 'save_season_episode_data');

function getScreens($username) {
    global $wpdb, $pdo;
    $query = "SELECT screens FROM screens where username = :username";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $username, PDO::PARAM_INT);
    $stmt->execute();
    if($stmt->rowCount() > 0) return $stmt->fetch(PDO::FETCH_OBJ);
    return 1;
}

function getLoggins($username) {
    global $wpdb, $pdo;
    $query = "SELECT * FROM loggins where username = :username";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $username, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount();
}

function LoginUser($user) {
    global $wpdb, $pdo;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $query = "INSERT INTO loggins(username , user_id , ipaddress) VALUES (:username , :user_id, :ipaddress)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $user->user_login, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $user->ID, PDO::PARAM_INT);
    $stmt->bindParam(':ipaddress', $ip_address, PDO::PARAM_STR);
    $stmt->execute();
    $logId = $pdo->lastInsertId();
    createLogCookie($logId);
}



function LogOutUser($logId) {
    global $wpdb, $pdo;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $query = "DELETE FROM loggins where id = :logId";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':logId', $logId, PDO::PARAM_INT);
    $stmt->execute();
    
}
 

function checkScreens_login_function($username, $password) {
    $screens = getScreens($username)->screens;

    if (getLoggins($username) >= $screens) {

        wp_die(__(' هذا الاحساب وصل لعدد الشاشات المتاحة , يجب تسجيل الخروج من أحد الحسابات لتتمكن من الدخول'));
       
        exit;
    }
    
    // If the condition is not met, return the username and password to continue with the login process
    return [$username, $password];
}
add_filter('wp_authenticate', 'checkScreens_login_function', 10, 2);


function custom_login_function( $user_login, $user ) {
    LoginUser($user);
}

function member_logout_function() {
    $current_user = wp_get_current_user();
    if (isset($_COOKIE['logId'])) {
        $cookie_value = $_COOKIE['logId'];
        LogOutUser($cookie_value);
    }
}

add_filter('wp_authenticate', 'checkScreens_login_function', 10, 2);
add_action( 'wp_login', 'custom_login_function', 10, 2 );
add_action('clear_auth_cookie', 'member_logout_function');


function createLogCookie($logId) {
    $cookie_name = 'logId';
    $cookie_value = $logId;
    $expiration_time = time() + (86400 * 30); // 30 days

    setcookie($cookie_name, $cookie_value, $expiration_time, '/');
}