<?php
/*
Plugin Name: Jump Start Banners
Description: Restores the banner functionality from Jump Start v2.0, when updating to v2.1+.
Version: 1.0.0
Author: Jason Bobich
Author URI: http://jasonbobich.com
License: GPL2

    Copyright 2016  Jason Bobich

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The license for this software can likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html

*/

/**
 * Setup the plugin
 *
 * @since 1.0.0
 */
function jumpstart_banners() {

	if ( ! defined('TB_FRAMEWORK_VERSION') || version_compare(TB_FRAMEWORK_VERSION, '2.6.0', '<') ) {
		return;
	}

	// Add Meta Box
	add_action('admin_init', 'jumpstart_banners_add_meta_box');

	// Add banner data to themeblvd global config array
	add_filter('themeblvd_frontend_config', 'jumpstart_banners_frontend_config');

	// Add layout builder compat
	add_filter('themeblvd_builder_section_start_count', 'jumpstart_banners_section_start_count', 20);

	// Add required CSS for banner output
	add_action('wp_enqueue_scripts', 'jumpstart_banners_style');
	add_action('wp_enqueue_scripts', 'jumpstart_banners_inline_styles', 25);

	// Output banner
	add_action('themeblvd_header_after', 'jumpstart_banners_output');

}
add_action('after_setup_theme', 'jumpstart_banners');

/**
 * Add meta box.
 *
 * @since 1.0.6
 */
function jumpstart_banners_add_meta_box() {

	global $_jumpstart_banner_meta_box;

	$meta = apply_filters('themeblvd_banner_meta', array(
		'config' => array(
			'id' 		=> 'tb_banner_options',						// make it unique
			'title' 	=> __('Banner', 'jumpstart-banners'),		// title to show for entire meta box
			'page'		=> array('page', 'post'),					// can contain post, page, link, or custom post type's slug
			'context' 	=> 'normal',								// normal, advanced, or side
			'priority'	=> 'core',									// high, core, default, or low
			'group'		=> '_tb_banner',							// save all option to single meta entry "_tb_banner"
			'textures'	=> true										// uses texture browser in options
		),
		'options' => array(
			'subgroup_start_1' => array(
				'type'		=> 'subgroup_start',
				'class'		=> 'show-hide-toggle'
			),
			'bg_type' => array(
				'id'		=> 'bg_type',
				'name'		=> __('Apply Banner', 'jumpstart-banners'),
				'desc'		=> __('Select if you\'d like to apply a custom banner and how you want to set it up.', 'jumpstart-banners'),
				'std'		=> 'none',
				'type'		=> 'select',
				'options'	=> themeblvd_get_bg_types('banner'),
				'class'		=> 'trigger'
			),
			'bg_color' => array(
				'id'		=> 'bg_color',
				'name'		=> __('Background Color', 'jumpstart-banners'),
				'desc'		=> __('Select a background color.', 'jumpstart-banners'),
				'std'		=> '#202020',
				'type'		=> 'color',
				'class'		=> 'hide receiver receiver-color receiver-texture receiver-image'
			),
			'bg_texture' => array(
				'id'		=> 'bg_texture',
				'name'		=> __('Background Texture', 'jumpstart-banners'),
				'desc'		=> __('Select a background texture.', 'jumpstart-banners'),
				'type'		=> 'select',
				'select'	=> 'textures',
				'class'		=> 'hide receiver receiver-texture'
			),
			'apply_bg_texture_parallax' => array(
				'id'		=> 'apply_bg_texture_parallax',
				'name'		=> null,
				'desc'		=> __('Apply parallax scroll effect to background texture.', 'jumpstart-banners'),
				'type'		=> 'checkbox',
				'class'		=> 'hide receiver receiver-texture'
			),
			'subgroup_start_2' => array(
				'type'		=> 'subgroup_start',
				'class'		=> 'select-parallax hide receiver receiver-image'
			),
			'bg_image' => array(
				'id'		=> 'bg_image',
				'name'		=> __('Background Image', 'jumpstart-banners'),
				'desc'		=> __('Select a background image.', 'jumpstart-banners'),
				'type'		=> 'background',
				'std'		=> array(
					'color'			=> '',
					'image'			=> '',
					'repeat'		=> 'no-repeat',
					'position'		=> 'center center',
					'attachment'	=> 'scroll',
					'size'			=> '100% auto'
				),
				'color'		=> false,
				'parallax'	=> true
			),
			'subgroup_end_2' => array(
				'type'		=> 'subgroup_end'
			),
			'bg_video' => array(
				'id'		=> 'bg_video',
				'name'		=> __('Background Video', 'jumpstart-banners'),
				'desc'		=> __('You can upload a web-video file (mp4, webm, ogv), or input a URL to a video page on YouTube or Vimeo. Your fallback image will display on mobile devices.', 'jumpstart-banners').'<br><br>'.__('Examples:', 'jumpstart-banners').'<br>https://vimeo.com/79048048<br>http://www.youtube.com/watch?v=5guMumPFBag',
				'type'		=> 'background_video',
				'class'		=> 'hide receiver receiver-video'
			),
			'subgroup_start_3' => array(
				'type'		=> 'subgroup_start',
				'class'		=> 'show-hide hide receiver receiver-image receiver-slideshow receiver-video'
			),
			'apply_bg_shade' => array(
				'id'		=> 'apply_bg_shade',
				'name'		=> null,
				'desc'		=> __('Shade background with transparent color.', 'jumpstart-banners'),
				'std'		=> 0,
				'type'		=> 'checkbox',
				'class'		=> 'trigger'
			),
			'bg_shade_color' => array(
				'id'		=> 'bg_shade_color',
				'name'		=> __('Shade Color', 'jumpstart-banners'),
				'desc'		=> __('Select the color you want overlaid on your background.', 'jumpstart-banners'),
				'std'		=> '#000000',
				'type'		=> 'color',
				'class'		=> 'hide receiver'
			),
			'bg_shade_opacity' => array(
				'id'		=> 'bg_shade_opacity',
				'name'		=> __('Shade Opacity', 'jumpstart-banners'),
				'desc'		=> __('Select the opacity of the shade color overlaid on your background.', 'jumpstart-banners'),
				'std'		=> '0.5',
				'type'		=> 'select',
				'options'	=> array(
					'0.05'	=> '5%',
					'0.1'	=> '10%',
					'0.15'	=> '15%',
					'0.2'	=> '20%',
					'0.25'	=> '25%',
					'0.3'	=> '30%',
					'0.35'	=> '35%',
					'0.4'	=> '40%',
					'0.45'	=> '45%',
					'0.5'	=> '50%',
					'0.55'	=> '55%',
					'0.6'	=> '60%',
					'0.65'	=> '65%',
					'0.7'	=> '70%',
					'0.75'	=> '75%',
					'0.8'	=> '80%',
					'0.85'	=> '85%',
					'0.9'	=> '90%',
					'0.95'	=> '95%'
				),
				'class'		=> 'hide receiver'
			),
			'subgroup_end_3' => array(
				'type'		=> 'subgroup_end'
			),
			'subgroup_start_4' => array(
				'type'		=> 'subgroup_start',
				'class'		=> 'show-hide-toggle hide receiver receiver-color receiver-texture receiver-image receiver-video'
			),
			'headline' => array(
				'id'		=> 'headline',
				'name'		=> __('Banner Headline (optional)', 'jumpstart-banners'),
				'desc'		=> __('Select if you\'d like the banner to contain a headline.', 'jumpstart-banners'),
				'std'		=> 'none',
				'type'		=> 'select',
				'options'	=> array(
					'none'		=> __('No headline', 'jumpstart-banners'),
					'title'		=> __('Display current title', 'jumpstart-banners'),
					'custom'	=> __('Display custom text', 'jumpstart-banners'),
				),
				'class'		=> 'trigger'
			),
			'headline_custom' => array(
				'id'		=> 'headline_custom',
				'name'		=> __('Custom Headline', 'jumpstart-banners'),
				'desc'		=> __('Enter the text for the headline.', 'jumpstart-banners'),
				'std'		=> '',
				'type'		=> 'text',
				'class'		=> 'hide receiver receiver-custom'
			),
			'tagline' => array(
				'id'		=> 'tagline',
				'name'		=> __('Banner Tagline (optional)', 'jumpstart-banners'),
				'desc'		=> __('If you want a brief tagline to appear below the headline, enter it here.', 'jumpstart-banners'),
				'std'		=> '',
				'type'		=> 'text',
				'class'		=> 'hide receiver receiver-title receiver-custom'
			),
			'text_color' => array(
				'id'		=> 'text_color',
				'name'		=> __('Text Color', 'jumpstart-banners'),
				'desc'		=> __('If you\'re using a dark background color, select to show light text, and vice versa.', 'jumpstart-banners'),
				'std'		=> 'light',
				'type'		=> 'select',
				'options'	=> array(
					'dark'		=> __('Dark Text', 'jumpstart-banners'),
					'light'		=> __('Light Text', 'jumpstart-banners')
				),
				'class'		=> 'hide receiver receiver-title receiver-custom'
			),
			'text_align' => array(
				'id'		=> 'text_align',
				'name'		=> __('Text Align', 'jumpstart-banners'),
				'desc'		=> __('Select how to align the text of the headline and tagline.', 'jumpstart-banners'),
				'std'		=> 'left',
				'type'		=> 'select',
				'options'	=> array(
					'left'		=> __('Left', 'jumpstart-banners'),
					'center'	=> __('Center', 'jumpstart-banners'),
					'right'		=> __('Right', 'jumpstart-banners')
				),
				'class'		=> 'hide receiver receiver-title receiver-custom'
			),
			'subgroup_end_4' => array(
				'type' 		=> 'subgroup_end'
			),
			'subgroup_start_5' => array(
				'type'		=> 'subgroup_start',
				'class'		=> 'show-hide hide receiver receiver-color receiver-texture receiver-image receiver-video'
			),
			'height' => array(
				'id'		=> 'height',
				'name' 		=> null,
				'desc' 		=> __('Apply custom banner height.', 'jumpstart-banners'),
				'std'		=> 0,
				'type'		=> 'checkbox',
				'class'		=> 'trigger'
		    ),
			'height_desktop' => array(
				'id'		=> 'height_desktop',
				'name' 		=> __('Desktop Height', 'jumpstart-banners'),
				'desc' 		=> __('Banner height (in pixels) when displayed at the standard desktop viewport range.', 'jumpstart-banners'),
				'std'		=> '200',
				'type'		=> 'text',
				'class'		=> 'hide receiver'
		    ),
		    'height_tablet' => array(
				'id'		=> 'height_tablet',
				'name' 		=> __('Tablet Height', 'jumpstart-banners'),
				'desc' 		=> __('Banner height (in pixels) when displayed at the tablet viewport range.', 'jumpstart-banners'),
				'std'		=> '120',
				'type'		=> 'text',
				'class'		=> 'hide receiver'
		    ),
		    'height_mobile' => array(
				'id'		=> 'height_mobile',
				'name' 		=> __('Mobile Height', 'jumpstart-banners'),
				'desc' 		=> __('Banner height (in pixels) when displayed at the mobile viewport range.', 'jumpstart-banners'),
				'std'		=> '100',
				'type'		=> 'text',
				'class'		=> 'hide receiver'
		    ),
			'subgroup_end_5' => array(
				'type'		=> 'subgroup_end'
			),
			'subgroup_end_1' => array(
				'type'		=> 'subgroup_end'
			)
		)
	));

	$_jumpstart_banner_meta_box = new Theme_Blvd_Meta_Box( $meta['config']['id'], $meta['config'], $meta['options'] );

}

/**
 * Register text domain for localization.
 *
 * @since 1.0.0
 */
function jumpstart_banners_frontend_config( $config ) {

	$config['banner'] = false;

	if ( is_singular() ) {

		$banner = get_post_meta( $config['id'], '_tb_banner', true );

		if ( $banner && ! empty($banner['bg_type']) && $banner['bg_type'] != 'none' ) {
			$config['banner'] = $banner;
		}
	}

	return $config;
}

/**
 * Bump layout builder section count up 1, if banner
 * applied. Basically, then the banner becomes the
 * first secton of the layout.
 *
 * @since 1.0.0
 */
function jumpstart_banners_section_start_count( $count ) {

	if ( themeblvd_config('banner') ) {
		$count++;
	}

	return $count;
}

/**
 * Get featured banner
 *
 * @since 1.0.0
 *
 * @param array $args
 * @return string $output Final HTML to output
 */
function themeblvd_banners_get_output( $args = array() ) {

	if ( ! $args ) {
		$args = themeblvd_config('banner');
	}

	if ( ! $args ) {
		return null;
	}

	$defaults = array(
		'id'						=> 'featured-banner',
		'post_id'					=> themeblvd_config('id'),
		'bg_type' 					=> 'none',
	    'bg_color' 					=> '#202020',
	    'bg_texture' 				=> 'arches',
	    'apply_bg_texture_parallax'	=> '0',
	    'bg_image' 					=> array(),
		'bg_video' 					=> array(),
		'apply_bg_shade'			=> '0',
		'bg_shade_color'			=> '#000000',
		'bg_shade_opacity'			=> '0.5',
	    'headline' 					=> 'none',
	    'headline_custom' 			=> '',
	    'tagline'					=> '',
	    'text_color'				=> 'light',
	    'text_align'				=> 'left'
	);
	$args = wp_parse_args( $args, $defaults );

	$style = themeblvd_get_display_inline_style($args);

	$output = sprintf('<div id="%s" class="tb-featured-banner %s" style="%s">', esc_attr($args['id']), esc_attr( implode( ' ', themeblvd_get_display_class($args) ) ), esc_attr($style) );

	// Parallax
	if ( themeblvd_do_parallax($args) ) {
		$output .= themeblvd_get_bg_parallax($args);
	}

	// Background video
	if ( $args['bg_type'] == 'video' ) {
		$output .= themeblvd_get_bg_video($args['bg_video']);
	}

	// Banner color shade
	if ( ( $args['bg_type'] == 'image' || $args['bg_type'] == 'video' ) && $args['apply_bg_shade'] ) {
		$output .= sprintf( '<div class="bg-shade" style="background-color: %s;"></div>', esc_attr( themeblvd_get_rgb( $args['bg_shade_color'], $args['bg_shade_opacity'] ) ) );
	}

	$output .= '<div class="wrap">';

	// Banner content
	$content = '';

	if ( $args['headline'] && $args['headline'] != 'none' ) {

		$class = sprintf( 'banner-content text-%s text-%s', esc_attr($args['text_color']), esc_attr($args['text_align']) );

		if ( $args['headline'] == 'title' ) {
			$content .= sprintf( '<h1 class="banner-title">%s</h1>', esc_html( get_the_title($args['post_id'] ) ) );
		} else if ( $args['headline'] == 'custom' ) {
			$content .= sprintf( '<h1 class="banner-title">%s</h1>', themeblvd_kses( $args['headline_custom'] ) );
		}

		if ( $args['tagline'] ) {
			$class .= ' has-tagline';
			$content .= sprintf( '<span class="banner-tagline ">%s</span>', themeblvd_kses( $args['tagline'] ) );
		}

	}

	if ( $content ) {
		$output .= sprintf( '<div class="%s">%s</div>', $class, $content );
	}

	$output .= '</div><!-- .wrap (end) -->';
	$output .= '</div><!-- .tb-featured-banner (end) -->';

	return apply_filters( 'themeblvd_featured_banner', $output, $args );
}

/**
 * Display featured banner
 *
 * @since 1.0.0
 */
function jumpstart_banners_output() {
	if ( themeblvd_config('banner') ) {
		echo themeblvd_banners_get_output();
	}
}

/**
 * Enqueue CSS file.
 *
 * @since 1.0.0
 */
function jumpstart_banners_style() {

	wp_enqueue_style( 'jumpstart-banners', esc_url( plugins_url('' , __FILE__) . '/banner-style.css' ) );

}

/**
 * Display featured banner's inline custom height CSS
 *
 * @since 1.0.0
 */
function jumpstart_banners_inline_styles() {

	if ( $args = themeblvd_config('banner') ) {

		$print = "/* Page Banner */\n";

		// Mobile
		if ( ! empty($args['height']) && ! empty($args['height_mobile']) ) {

			$args['height_mobile'] = str_replace('px', '', $args['height_mobile']); // double check formatting

			$print .= ".tb-featured-banner > .wrap {\n";
			$print .= sprintf( "\tmin-height: %spx;\n", esc_attr( $args['height_mobile'] ) );
			$print .= "}\n";

		}

		// Tablet
		$print .= "@media (min-width: 768px) {\n";

		$print .= "\t.tb-featured-banner {\n";
		$print .= sprintf("\t\tpadding-top: %spx;\n", themeblvd_config('top_height_tablet'));
		$print .= "\t}\n";

		if ( ! empty($args['height']) && ! empty($args['height_tablet']) ) {

			$args['height_tablet'] = str_replace('px', '', $args['height_tablet']); // double check formatting

			$print .= "\t.tb-featured-banner > .wrap {\n";
			$print .= sprintf( "\t\tmin-height: %spx;\n", esc_attr( $args['height_tablet'] ) );
			$print .= "\t}\n";

		}

		$print .= "}\n";

		// Desktop
		$print .= "@media (min-width: 992px) {\n";

		$print .= "\t.tb-featured-banner {\n";
		$print .= sprintf("\t\tpadding-top: %spx;\n", themeblvd_config('top_height'));
		$print .= "\t}\n";

		if ( ! empty($args['height']) && ! empty($args['height_desktop']) ) {

			$args['height_desktop'] = str_replace('px', '', $args['height_desktop']); // double check formatting

			$print .= "\t.tb-featured-banner > .wrap {\n";
			$print .= sprintf( "\t\tmin-height: %spx;\n", esc_attr($args['height_desktop'] ) );
			$print .= "\t}\n";
		}

		$print .= "}\n";

		// Print after style.css
		wp_add_inline_style( 'themeblvd-theme', apply_filters('themeblvd_banner_css_output', $print, $args) );

	}

}

/**
 * Register text domain for localization.
 *
 * @since 1.0.0
 */
function jumpstart_banners_localize() {
	load_plugin_textdomain('jumpstart_banners');
}
add_action('init', 'jumpstart_banners_localize');
