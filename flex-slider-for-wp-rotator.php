<?php
/*
Plugin Name: Flex Slider for WP Rotator
Plugin URI: http://wordpress.org/extend/plugins/flex-slider-for-wp-rotator/
Description: Turns WP Rotator into FlexSlider, a fully responsive jQuery slider.
Version: 1.1
Author: Bill Erickson
Author URI: http://www.billerickson.net/blog/wordpress-guide
*/

class BE_Flex_Slider {
	var $instance;
	
	function __construct() {
		$this->instance =& $this;
		register_activation_hook( __FILE__, array( $this, 'activation_hook' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ) );	
	}
	
	/**
	 * Activation Hook
	 * Confirm WP Rotator is currently active
	 */
	function activation_hook() {
		if( !function_exists( 'wp_rotator_option' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( sprintf( __( 'Sorry, you can&rsquo;t activate unless you have installed <a href="%s">WP Rotator</a>', 'flex-slider-for-wp-rotator'), 'http://wordpress.org/extend/plugins/wp-rotator/' ) );
		}
	}
	
	function init() {
		// Remove original scripts and styles
		remove_action('wp_head','wp_rotator_css');
		remove_action('admin_head','wp_rotator_css');
		remove_action('wp_head','wp_rotator_javascript');
		remove_action('admin_head','wp_rotator_javascript');
		remove_action('init','wp_rotator_add_jquery');
		remove_action('admin_init','wp_rotator_add_jquery');
		
		// Enqueue Scripts and Styles
		add_action( 'init', array( $this, 'enqueue_scripts_and_styles' ) );
		
		// Remove original outer markup
		remove_action( 'wp_rotator', 'wp_rotator' );
		
		// Add new markup
		add_action( 'wp_rotator', array( $this, 'flex_slider' ) );
		remove_shortcode( 'wp_rotator' );
		add_shortcode( 'wp_rotator', array( $this, 'flex_slider_markup' ) );
	}
	
	function enqueue_scripts_and_styles() {
		// Use this filter to limit where the scripts are enqueued.
		$show = apply_filters( 'be_flex_slider_show_scripts', true );
		if ( true === $show ) {
			wp_enqueue_style( 'flex-slider', plugins_url( 'flexslider.css', __FILE__ ) );
			wp_enqueue_script( 'jquery ');
			wp_enqueue_script( 'flex-slider', plugins_url( 'jquery.flexslider-min.js', __FILE__ ), array( 'jquery' ) );
			add_action( 'wp_head', array( $this, 'flex_slider_settings' ) );
		}
	}
	
	function flex_slider_settings() {
		?>
		<script type="text/javascript" charset="utf-8">
		  jQuery(window).load(function() {
		    jQuery('.flexslider').flexslider({
		    	<?php
		    	$flex_settings = array(
		    		'animation' => '"' . wp_rotator_option( 'animate_style' ) . '"',
		    		'slideshowSpeed' => wp_rotator_option( 'rest_ms' ),
		    		'animationDuration' => wp_rotator_option( 'animate_ms' ),
		    	);
		    	
		    	$flex_slide_settings = array(
		    		'controlsContainer' => '".flex-container"'
		    	);
		    	
		    	if( 'slide' == wp_rotator_option( 'animate_style' ) )
		    		$flex_settings = array_merge( $flex_settings, $flex_slide_settings );
		    	
		    	$flex_settings = apply_filters( 'be_flex_slider_settings', $flex_settings );
		    	foreach ( $flex_settings as $field => $value ) {
		    		echo $field . ': ' . $value . ', ';
		    	}
		    	?>
		    });
		  });
		</script>
		<?php
	}
	
	function flex_slider_markup() {
		$output = '';
		
		if( 'slide' == wp_rotator_option( 'animate_style' ) )
			$output .= '<div class="flex-container">';
			
		$output .= '<div class="flexslider"><ul class="slides">';
		
		$loop = new WP_Query( esc_attr( wp_rotator_option('query_vars') ) );
		while ( $loop->have_posts() ): $loop->the_post(); global $post;

			$url = esc_url ( get_post_meta( $post->ID, 'wp_rotator_url', true ) );
			if ( empty( $url ) ) $url = get_permalink($post->ID);
			$show_info = esc_attr( get_post_meta( $post->ID, 'wp_rotator_show_info', true ) );
			if ( true == $show_info ) {
				$title = get_the_title();
				if ( get_the_excerpt() ) $excerpt = get_the_excerpt(); 
				else $excerpt = '';
				$caption = $title . ' <span class="excerpt">' . $excerpt . '</span>';
				$info = '<p class="flex-caption">' . apply_filters( 'be_flex_slider_caption', $caption, $title, $excerpt ) . '</p>';
			} else {
				$info = '';
			}
			$image =  wp_get_attachment_image_src( get_post_thumbnail_id(), 'wp_rotator' );

			$slide = '<li><a href="' . $url . '"><img src="' . $image[0] . '" /></a>' . $info . '</li>';
			$output .= apply_filters( 'be_flex_slider_slide', $slide );
			
		endwhile; wp_reset_query();
		
		$output .= '</ul></div>';
		
		if( 'slide' == wp_rotator_option( 'animate_style' ) )
			$output .= '</div>';
		
		return $output;
	}
	
	function flex_slider() {
		echo $this->flex_slider_markup();
	}

}

new BE_Flex_Slider;
?>