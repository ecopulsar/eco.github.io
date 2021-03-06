<?php if( !defined('WPINC') ) die;
/**
 * Leyka template shortcodes
 * 
 **/


/**
 * Scale shortcode
 **/

add_shortcode('leyka_scale', 'leyka_scale_screen' );
function leyka_scale_screen($atts) {

    $a = shortcode_atts(array(
        'id'          => 0,
        'show_button' => 0,
    ), $atts);

    $campaign = ($a['id'] > 0) ? get_post($a['id']) : get_post();

	if($campaign->post_type != Leyka_Campaign_Management::$post_type) { // Wrong campaign data
		return '';
    }

	return "<div id='".esc_attr('leyka_scale_standalone-'.uniqid())."'>".leyka_get_scale($campaign, $a)."</div>";
}

function leyka_get_scale($campaign = null, $args = array()) {

	$current_post = get_post();

	$defaults = array(
		'show_button' => 0,
		'embed_mode'  => 0
	);

	$args = wp_parse_args($args, $defaults);

	if( !$campaign ) {
		$campaign = $current_post;
    } elseif(is_int($campaign)) {
		$campaign = get_post($campaign);
    }

	if($campaign->post_type != Leyka_Campaign_Management::$post_type) { // Wrong campaign data
		return '';
    }

	$campaign = new Leyka_Campaign($campaign);

	$css_class = 'leyka-scale';
	if($args['show_button'] == 1 && (int)$campaign->target == 0) {
		$css_class .= ' has-button-alone';
	} elseif($args['show_button'] == 1) {
		$css_class .= ' has-button';
	}

	ob_start();?>

	<div class="<?php echo esc_attr($css_class);?>">
		<?php leyka_scale_compact($campaign);?>
	<?php if($args['show_button'] == 1 && !$campaign->is_finished) {?>
		<div class="leyka-scale-button">
			<a href='<?php echo trailingslashit(get_permalink($campaign->ID)).'#leyka-payment-form';?>' <?php echo $campaign->ID == $current_post->ID ? 'class="leyka-scroll"' : '';?><?php echo $args['embed_mode'] === 1 ? ' target="_blank"' : '';?>>
                <?php echo leyka_get_scale_button_label();?>
            </a>
		</div>
	<?php }?>
	</div>
<?php $out = ob_get_clean();

	return apply_filters('leyka_scale_html', $out, $campaign, $args);
}

function leyka_get_scale_button_label(){

	return apply_filters('leyka_scale_button_label', _x('Support', '«Support» label at scale button', 'leyka'));
}


/**
 * Campaign card shortcode
 **/

add_shortcode('leyka_campaign_card', 'leyka_campaign_card_screen' );
function leyka_campaign_card_screen($atts) {

    $a = shortcode_atts(array(
        'id' => 0,
        'show_title' => 1,
		'show_thumb' => 1,
		'show_excerpt' => 1,
		'show_scale' => 1,
		'show_button' => 1,
    ), $atts);

    $campaign_post = $a['id'] > 0 ? get_post($a['id']) : get_post();
	
	if($campaign_post->post_type != Leyka_Campaign_Management::$post_type) { // Wrong campaign data
		return '';
    }

    $campaign = new Leyka_Campaign($campaign_post);
    $campaign->increase_views_counter();

	return '<div id="'.esc_attr('leyka_campaign_card_standalone-'.uniqid()).'">'
           .leyka_get_campaign_card($campaign_post, $a).'</div>';
}

function leyka_get_campaign_card($campaign = null, $args = array()) {

	$current_post = get_post();

	$defaults = array(
		'show_title' => 1,
		'show_thumb' => 1,
		'show_excerpt' => 1,
		'show_scale' => 1,
		'show_button' => 1,
        'increase_counters' => 0,
		'embed_mode' => 0,
	);

	$args = wp_parse_args($args, $defaults);

	if( !$campaign ) {
		$campaign = $current_post;
	} elseif((int)$campaign > 0) {
		$campaign = get_post($campaign);
	} elseif( !is_a($campaign, 'WP_Post') ) {
        return false;
    }

	if($campaign->post_type != Leyka_Campaign_Management::$post_type) { // Wrong campaign data
		return '';
    }

	$target = $args['embed_mode'] == 1 ? ' target="_blank"' : '';
	$thumbnail_size = apply_filters('leyka_campaign_card_thumbnail_size', 'post-thumbnail', $campaign, $args);
	$css_class = apply_filters('leyka_campaign_card_class', 'leyka-campaign-card', $campaign, $args);	
	if($args['show_thumb'] == 1 && has_post_thumbnail($campaign->ID)) {
        $css_class .= ' has-thumb';
    }

	ob_start(); // Do we have some content ?>

	<div class="<?php echo esc_attr($css_class);?>">
		<?php if($args['show_thumb'] == 1 && has_post_thumbnail($campaign->ID)) {?>
			<div class="lk-thumbnail">
				<a href="<?php echo get_permalink($campaign);?>"<?php echo $target;?>>
					<?php echo get_the_post_thumbnail(
                        $campaign->ID,
                        $thumbnail_size,
                        array('alt' => esc_attr(sprintf(__('Thumbnail for - %s', 'leyka'), $campaign->post_title)),)
                    );?>
				</a>
			</div>
		<?php }?>

		<?php if($args['show_title'] == 1 || $args['show_excerpt'] == 1) {?>
			<div class="lk-info">
				<?php if($args['show_title'] == 1) {?>
					<h4 class="lk-title"><a href="<?php echo get_permalink($campaign);?>"<?php echo $target;?>>
						<?php echo get_the_title($campaign);?>
					</a></h4>
				<?php }?>
				
				<?php if($args['show_excerpt'] == 1) {
					// Default excerpt filters:
					add_filter('leyka_get_the_excerpt', 'wptexturize');
					add_filter('leyka_get_the_excerpt', 'convert_smilies');
					add_filter('leyka_get_the_excerpt', 'convert_chars');
					add_filter('leyka_get_the_excerpt', 'wp_trim_excerpt');?>
					<p>
                    <?php if(has_excerpt($campaign->ID)) {
                        $text = $campaign->post_excerpt;
                    } else {
                        $text = substr(strip_tags(strip_shortcodes(
                            $campaign->post_content ? $campaign->post_content : ' ' // So wp_trim_excerpt work correctly
                        )), 0, 350);
                    }
                    echo apply_filters('leyka_get_the_excerpt', $text, $campaign);?>
                    </p>
                <?php }?>
			</div>
		<?php }?>

		<?php if($args['show_scale'] == 1) {

            echo leyka_get_scale($campaign,	array(
                'show_button' => $args['show_button'],
                'embed_mode' => $args['embed_mode']
            ));

		} elseif($args['show_button'] == 1 && !$campaign->is_finished) {

			$url = trailingslashit(get_permalink($campaign->ID)).'#leyka-payment-form'.
                ( !!$args['increase_counters'] ? '?increase_counters=1' : '' );?>

			<div class="leyka-scale-button-alone">
				<a href="<?php echo $url;?>" <?php echo $campaign->ID == $current_post->ID ? 'class="leyka-scroll"' : '';?><?php echo $target;?>><?php echo leyka_get_scale_button_label();?></a>
			</div>

		<?php }?>
	</div>
<?php
	$out = ob_get_clean();
	return apply_filters('leyka_campaign_card_html', $out, $campaign, $args);
}


/**
 * Payment form shortcode 
 **/
add_shortcode('leyka_payment_form', 'leyka_payment_form_screen');
function leyka_payment_form_screen($atts) {

    $a = shortcode_atts(array(
        'id'          => 0,
        'template'    => null,		
    ), $atts);

    $campaign = $a['id'] > 0 ? get_post($a['id']) : get_post();

	if($campaign->post_type != Leyka_Campaign_Management::$post_type) {
		return '';
    }

	return leyka_get_payment_form($campaign, $a);
}

function leyka_get_payment_form($campaign = null, $args = array()) {

	$defaults = array(
		'template'  => null, // Ex. "radios" or "toggles"
	);

	$args = wp_parse_args($args, $defaults);

	if( !$campaign ) {
		$campaign = get_post();
	} elseif(is_int($campaign)){
		$campaign = get_post($campaign);
	}

	if($campaign->post_type != Leyka_Campaign_Management::$post_type) {
		return '';
    }

    $campaign = new Leyka_Campaign($campaign);
    $campaign->increase_views_counter();

	return get_leyka_payment_form_template_html($campaign, $args['template']);
}


/**
 * Donation tickers shortcode
 **/
add_shortcode('leyka_donors_list', 'leyka_donors_list_screen' );
function leyka_donors_list_screen($atts) {		
	
    $a = shortcode_atts( array(
        'id'           => 'all', //could be also 0 (obtained from context) or real ID
        'num'          => leyka_get_donors_list_per_page(),
		'show_purpose' => 1,
		'show_name'    => 1,
		'show_date'    => 1,
    ), $atts );
    
	if($a['id'] != 'all')
		$a['id'] = (int)$a['id'];
		
	return leyka_get_donors_list($a['id'], $a);
}

function leyka_get_donors_list_per_page() {
	
	return apply_filters('leyka_donors_list_per_page', 25);
}

function leyka_get_donors_list($campaign_id = 'all', $args = array()) {

	$defaults = array(
		'num'          => leyka_get_donors_list_per_page(),
		'show_purpose' => 1,
		'show_name'    => 1,
		'show_date'    => 1,
	);

	$args = wp_parse_args($args, $defaults);

	if($campaign_id === 0) {
		$campaign_id = get_post()->ID;
	}

	// Get donations: funded amount > 0
	$d_args = array(
		'post_type' => Leyka_Donation_Management::$post_type,
		'post_status' => 'funded',
		'posts_per_page' => $args['num'],
		'meta_query' => array(
			array(
				'key'     => 'leyka_donation_amount',
				'value'   => 0,
				'compare' => '!=',
				'type'    => 'NUMERIC'
			)
		)
	);

	if($campaign_id != 'all') {

		$d_args['meta_query']['relation'] = 'AND';
		$d_args['meta_query'][] = array(
			'key'   => 'leyka_campaign_id',
			'value' => $campaign_id
		);		
	}

	$donations = get_posts($d_args);

	if( !$donations ) {
		return '';
    }

	ob_start();?>

	<div id="<?php echo esc_attr('leyka_donors_list-'.uniqid());?>" class="leyka-donors-list">
	<?php
		foreach($donations as $donation) {

			$donation = new Leyka_Donation($donation);

			$amount = number_format($donation->sum, 0, '.', ' ');

			$html = "<div class='ldl-item'>";
			$html .= "<div class='amount'>{$amount} {$donation->currency_label}</div>";

			if($args['show_purpose'] == 1) {
				$html .= "<div class='purpose'><a href='".get_permalink($donation->campaign_id)."'>".$donation->campaign_payment_title."</a></div>";
			}

			$meta = array();
			if($args['show_name'] == 1) {

				$name = $donation->donor_name;
				$name = (!empty($name)) ? $name : __('Anonymous', 'leyka');				
				$meta[] = '<span>'.$name.'</span>';
			}

			if($args['show_date'] == 1) {

                if($donation->type == 'correction') {

                    $time = date('H:i:s', $donation->date_timestamp) == '00:00:00' ? '' : date(' '.get_option('time_format'), $donation->date_timestamp);
                    $date = date(get_option('date_format').$time, $donation->date_timestamp);

                } else {
                    $date = $donation->date_funded;
                }

                if($date) {
                    $meta[] = '<time>'.$date.'</time>';
                }
			}

			if($meta) {
				$html .= "<div class='meta'>".implode(' / ', $meta)."</div>"; 
			}

			$html .= "</div>";

			echo apply_filters('leyka_donors_list_item_html', $html, $campaign_id, $args);
        }?>
	</div>
<?php
	$out = ob_get_clean();
	return $out;
}

/**
 * Terms of service shortcode
 **/
add_shortcode('leyka_service_terms_text', 'leyka_get_terms_text');
function leyka_get_terms_text() {
    return apply_filters('leyka_terms_of_service_text', leyka_options()->opt('terms_of_service_text'));
}