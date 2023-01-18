<?php
/**
 * Plugin Name: Recipe Roundups Block for Gutenberg
 * Plugin URI: https://wpzoom.com/
 * Description: Recipe Roundups Block Plugin for Food Bloggers with Schema Markup.
 * Author: WPZOOM
 * Author URI: https://wpzoom.com/
 * Version: 1.0.0
 * Copyright: (c) 2021 WPZOOM
 * License: GPL2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: recipe-roundups-block
 * Domain Path: /languages
 *
 * @package   WPZOOM_Recipe_Roundups_Block
 * @author    WPZOOM
 * @license   GPL-2+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'WPZOOM_RRB_VER', get_file_data( __FILE__, [ 'Version' ] )[0] ); // phpcs:ignore

define( 'WPZOOM_RRB__FILE__', __FILE__ );
define( 'WPZOOM_RRB_ABSPATH', dirname( __FILE__ ) . '/' );
define( 'WPZOOM_RRB_PLUGIN_BASE', plugin_basename( WPZOOM_RRB__FILE__ ) );
define( 'WPZOOM_RRB_PLUGIN_DIR', dirname( WPZOOM_RRB_PLUGIN_BASE ) );

define( 'WPZOOM_RRB_PATH', plugin_dir_path( WPZOOM_RRB__FILE__ ) );
define( 'WPZOOM_RRB_URL', plugin_dir_url( WPZOOM_RRB__FILE__ ) );

class WPZOOM_Recipe_Roundups_Block {

	/**
	 * This plugin's instance.
	 *
	 * @var WPZOOM_Recipe_Roundups_Block
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Main WPZOOM_Recipe_Roundups_Block Instance.
	 *
	 * Insures that only one instance of WPZOOM_Recipe_Roundups_Block exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0.0
	 * @static
	 * @return object|WPZOOM_Recipe_Roundups_Block The one true WPZOOM_Recipe_Roundups_Block
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new WPZOOM_Recipe_Roundups_Block();
		}
		return self::$instance;
	}

	/**
	 * Plugin constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		add_action( 'init', array( $this, 'i18n' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'register_backend_assets' ), 10 );
		add_action( 'enqueue_block_assets', array( $this, 'register_assets' ), 10 );

		$this->init();
	
	}

	/**
	 * Load Textdomain
	 *
	 * Load plugin localization files.
	 *
	 * Fired by `init` action hook.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function i18n() {
		load_plugin_textdomain( 'recipe-roundups-block', false, WPZOOM_FB_PATTERNS_PLUGIN_DIR . '/languages' );
	}

	public function init() {
		$this->register_recipe_roundups_block();
	}

	/**
	 * Registers needed scripts and styles for use on the backend.
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function register_assets() {
		wp_register_style(
			'wpzoom-recipe-roundups-block-css',
			plugins_url( '/dist/style-recipe-roundups-block.css', __FILE__ ),
			array(),
			WPZOOM_RRB_VER
		);
	}

	/**
	 * Registers needed scripts and styles for use on the backend.
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function register_backend_assets() {
		wp_register_script(
			'wpzoom-recipe-roundups-block-backend',
			plugins_url( '/dist/recipe-roundups-block.js', __FILE__ ),
			array( 'wp-blocks' ),
			WPZOOM_RRB_VER,
			true
		);
		wp_register_style(
			'wpzoom-recipe-roundups-block-css-backend',
			plugins_url( '/dist/recipe-roundups-block.css', __FILE__ ),
			array(),
			WPZOOM_RRB_VER
		);
	}

	public function register_recipe_roundups_block() {
		register_block_type( 
			'wpzoom-blocks/recipe-roundups',
			array(
				'attributes'    => array(
					'externalUrls' => array(
						'type' => 'array',
					),
					'showTotal' => array(
						'type'    => 'boolean',
						'default' => true
					),
					'hasImage' => array(
						'type'    => 'boolean',
						'default' => true
					),
					'hasRating'  => array(
						'type'    => 'boolean',
						'default' => true
					),
					'hasCuisine' => array(
						'type'    => 'boolean',
						'default' => true
					),
					'hasDifficulty' => array(
						'type'    => 'boolean',
						'default' => true
					),
					'hasButton' => array(
						'type'    => 'boolean',
						'default' => false
					),
					'hasSchema' => array(
						'type'    => 'boolean',
						'default' => true
					)
				),
				'script'          => 'wpzoom-recipe-roundups-block-js',
				'style'           => 'wpzoom-recipe-roundups-block-css',
				'editor_script'   => 'wpzoom-recipe-roundups-block-backend',
				'editor_style'    => 'wpzoom-recipe-roundups-block-css-backend',
				'render_callback' => array( $this, 'recipe_roundups_block_render' )
			)
		 );
	}

	public function recipe_roundups_block_render( $attributes, $content, $block ) {

		$recipes = array();

		$recipes = $recipePosts = $external_urls = array();
		$json_ld = '';
		$json_ld_data = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'ItemList',
			'itemListElement' => array()
		);

		if( ! empty( $attributes['externalUrls'] ) ) {

			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

			foreach( $attributes['externalUrls'] as $external_url ) { 

				if ( filter_var( $external_url, FILTER_VALIDATE_URL ) === FALSE ) {
					$recipes[] = array(
						'noValidUrl' => true,
						'errorMessage' => esc_html__( 'Please, input a valid URL', 'recipe-roundups-block' )
					);
					continue;
				}	

				$wp_filesystem = new WP_Filesystem_Direct( null );
				$get_external_recipe = $wp_filesystem->get_contents( $external_url );
				
				// retrieve the JSON data
				$d = new DomDocument();
				@$d->loadHTML( $get_external_recipe );

				// parse the HTML to retrieve the "ld+json" only
				$xp = new domxpath( $d );
				
				$jsonScripts = $xp->query( '//script[@type="application/ld+json"]' );
				$count = count( $jsonScripts );
				$jsons = $validJsons = array();

				for ( $i = 0; $i < $count; $i++ ) {
					$jsons[] = trim( $jsonScripts->item($i)->nodeValue );
				}

				$decodedJson = $decoded = array();

				foreach( $jsons as $json ) {
					
					$json = preg_replace( '/[\x00-\x1F\x80-\xFF]/', '', $json );
					
					$decodedJson = json_decode( $json, true );

					$decoded = $this->search_array( $decodedJson, '@type', 'Recipe' );
					$decoded = isset( $decoded[0] ) ? $decoded[0] : $decoded;

					if( isset( $decoded['@type'] ) && 'Recipe' === $decoded['@type'] ) {

						$validJsons[] = array(
							'valid' => true
						);

						$total_time = isset( $decoded['totalTime'] ) ? $decoded['totalTime'] : '';
						$interval  = new DateInterval( $total_time );
						$hours   = !empty( $interval->format( '%h' ) ) ? $interval->format( '%h ' ) : null;
						$minutes = !empty( $interval->format( '%i' ) ) ? $interval->format( '%i ' ) : null;

						if( $hours ) {
							$hours = sprintf( 
								_nx( '%s hour ', '%s hours ', $hours, 'of hours', 'recipe-roundups-block' ), 
								number_format_i18n( $hours ) 
							);
						}

						if( $minutes ) {
							$minutes = sprintf( 
								_nx( '%s minute', '%s minutes', $minutes, 'of minutes', 'recipe-roundups-block' ), 
								number_format_i18n( $minutes ) 
							);
						}

						$total_time =  $hours . $minutes;
						$cuisine = $image = '';

						if( isset( $decoded['recipeCuisine'] ) ) {
							$cuisine = is_array( $decoded['recipeCuisine'] ) ? implode( ', ', $decoded['recipeCuisine'] ) : $decoded['recipeCuisine'];
						}

						if( isset( $decoded['image'] ) ) {
							if( is_array( $decoded['image'] ) ) {
								$image = isset( $decoded['image']['url'] ) ? $decoded['image']['url'] : $decoded['image'][0];
							}
							else {
								$image = $decoded['image'];
							}
						}

						if( !empty( $image ) ) {
							$image_url_scheme = parse_url( $image, PHP_URL_SCHEME );
							if ( empty( $image_url_scheme ) ) {
								$host = parse_url( $external_url, PHP_URL_HOST );
								$image = $host . $image;
							}
						}

						$ratingValue = $reviewCount = '';
						$ratingValue = isset( $decoded['aggregateRating']['ratingValue'] ) ? $decoded['aggregateRating']['ratingValue'] : '';
						
						if( isset( $decoded['aggregateRating']['reviewCount'] ) ) {
							$reviewCount = $decoded['aggregateRating']['reviewCount'];
						}
						elseif( isset( $decoded['aggregateRating']['ratingCount'] ) ) {
							$reviewCount = $decoded['aggregateRating']['ratingCount'];
						}
						
						$recipes[] = array(
							'recipeId'   => esc_url( $external_url ),
							'recipeUrl'  => esc_url( $external_url ),
							'title'      => isset( $decoded['name'] ) ? $decoded['name'] : esc_html__( 'Recipe Block' ),
							'image'      => $image,
							'excerpt'    => isset( $decoded['description'] ) ? $decoded['description'] : '',
							'difficulty' => isset( $decoded['recipeDifficulty'] ) ? $decoded['recipeDifficulty'] : '',
							'cuisine'    => $cuisine,
							'totalTime'  => array( 'value' => $total_time, 'unit'  => null ),
							'aggregateRating' => array(
								'ratingValue' => $ratingValue,
								'reviewCount' => $reviewCount
							),
						);
					}
				}
				if( empty( $validJsons ) ) {
					$recipes[] = array(
						'noValidRecipeJson' => true,
						'errorMessage' => esc_html__( 'Sorry! There is no any valid recipe json/schema on this page.', 'recipe-roundups-block' )
					);
					continue;
				}
			}

		};

		$recipe_list = '';
		if( !empty( $recipes ) ) {
			
			foreach ( $recipes as $key => $recipe_summary ) :

				if( isset( $recipe_summary['noValidUrl'] ) ) {
					$recipe_list .= '<li class="wpzoom-recipe-roundups-item error">' .  $recipe_summary['errorMessage'] . '</li>';
					continue;
				}

				if( isset( $recipe_summary['noValidRecipeJson'] ) ) {
					$recipe_list .= '<li class="wpzoom-recipe-roundups-item error">' .  $recipe_summary['errorMessage'] . '</li>';
					continue;
				}

				$recipe_list .= '<li id="wpzoom-recipe-summary-' . esc_attr( $recipe_summary['recipeId'] ) . '" class="wpzoom-recipe-roundups-item wpzoom-recipe-roundups-item-' . esc_attr( $recipe_summary['recipeId'] ) . '">';
					$recipe_list .= '<div class="wpzoom-recipe-card-roundups">';

						if( $attributes['hasImage'] ) {
							$recipe_list .= '<div class="wpzoom-recipe-roundups-media">';
								$recipe_list .= '<a href="' . esc_url( $this->get_link( $recipe_summary['recipeId'] ) ) . '" target="_blank"></a>';
								$recipe_list .=  $this->get_thumbnail( $recipe_summary['image'] );
							$recipe_list .= '</div>';
						}

						$recipe_list .= '<div class="wpzoom-recipe-roundups-content">';
						
							$recipe_list .= '<h3 class="wpzoom-recipe-roundups-title"><a href="' . esc_url( $this->get_link( $recipe_summary['recipeId'] ) ) . '" target="_blank">';
								$recipe_list .= $this->get_title( $recipe_summary['title'] );
							$recipe_list .= '</a></h3>';

							$recipe_list .= '<div class="wpzoom-recipe-roundups-info">';

								if( $attributes['showTotal'] && !empty( $recipe_summary['totalTime']['value'] ) ) {

									$total_time_unit  = isset( $recipe_summary['totalTime']['unit'] )  ? $recipe_summary['totalTime']['unit'] : '';
									$total_time_value = isset( $recipe_summary['totalTime']['value'] ) ? $recipe_summary['totalTime']['value'] : '';

									$recipe_list .= '<span class="wpzoom-recipe-roundups-total-time">';
										$recipe_list .= esc_html__( 'Cooks in', 'recipe-roundups-block' ) . ' <strong>' . $total_time_value . ' ' . $total_time_unit . '</strong>';
									$recipe_list .= '</span>';
								}
								if( $attributes['hasDifficulty'] && !empty( $recipe_summary['difficulty'] ) ) {
									$recipe_list .= '<span class="wpzoom-recipe-roundups-difficulty">';
										$recipe_list .= esc_html__( 'Difficulty:', 'recipe-roundups-block' ) . ' <strong>' . $recipe_summary['difficulty'] . '</strong>';
									$recipe_list .= '</span>';
								}
							$recipe_list .= '</div>';

							if( !empty( $recipe_summary['excerpt'] ) ) {
								$recipe_list .= '<div class="wpzoom-recipe-roundups-text">';
									$recipe_list .= $this->fix_content_tags_conflict( wp_kses_post( wpautop( $recipe_summary['excerpt'] ) ) );
								$recipe_list .= '</div>';
							}

							$recipe_list .= '<div class="wpzoom-recipe-roundups-footer">';

								if( $attributes['hasRating'] ) {
									$aggregateRating = isset( $recipe_summary['aggregateRating'] ) ? $recipe_summary['aggregateRating'] : '';
									$recipe_list .= $this->get_ratings( $recipe_summary['recipeId'], $aggregateRating );
								}
								
								if( $attributes['hasCuisine'] && !empty( $recipe_summary['cuisine'] ) ) {
									$recipe_list .= '<span class="wpzoom-recipe-roundups-cousine">';
										$recipe_list .= esc_html__( 'Cuisine:', 'recipe-roundups-block' ) . ' <strong>' . $recipe_summary['cuisine'] . '</strong>';
									$recipe_list .= '</span>';
								}
							$recipe_list .= '</div>';
						
						$recipe_list .=	'</div>';

					$recipe_list .=	'</div>';
				$recipe_list .= '</li>';
				
				$json_ld_data['itemListElement'][] = array(
					'@type'    => 'ListItem',
					'position' => $key + 1,
					'url'      => esc_url( $this->get_link( $recipe_summary['recipeId'] ) )
				);
								
			endforeach;
		}

		if( $attributes['hasSchema'] && !empty( $attributes['hasSchema'] ) ) {
			$json_ld .= '<script type="application/ld+json">';
			$json_ld .= wp_json_encode( $json_ld_data );
			$json_ld .= '</script>';

		}

		return sprintf(
			'<ul class="wpzoom-recipe-roundups-list">%s</ul>%s',
			$recipe_list,
			$json_ld
		);

	}

	/**
	 * Get rcb link to the page/post with the rcb
	 *
	 * @since 1.0.0
	 * @param string $post_id ID of the cpt.
	 */
	public function get_link( $post_id ) {

		if( empty( $post_id ) ) {
			return;
		}

		$link = '';

		if( preg_match( '/^\d+$/', $post_id ) ) {
		
			$parent_id = get_post_meta( $post_id, '_wpzoom_rcb_parent_post_id', true );
			$link = get_the_permalink( $parent_id );

		}
		else {
			$link = $post_id;
		}

		return $link;

	}

	/**
	 * Get rcb ratings
	 *
	 * @since 1.0.0
	 * @param string $post_id ID of the cpt.
	 * @param array $aggregate_rating for external recipes.
	 */
	public function get_ratings( $post_id, $aggregate_rating ) {

		$rating_average = isset( $aggregate_rating['ratingValue'] ) ? $aggregate_rating['ratingValue'] : '';
		$total_votes    = isset( $aggregate_rating['reviewCount'] ) ? $aggregate_rating['reviewCount'] : '';
		
		$ratings_html = '';

		if( !$total_votes ) {
			return;
		}

		$ratings_html .= '<span class="wpzoom-rcb-summary-ratings">';
			if( '1' == $total_votes ) {
				$ratings_html .= $total_votes . ' ' . esc_html__( 'vote', 'recipe-roundups-block' ) . ' <strong>' . $rating_average . ' <span class="dashicons dashicons-star-filled"></span></strong>';
			}
			else {
				$ratings_html .= $total_votes . ' ' . esc_html__( 'votes', 'recipe-roundups-block' ) . ' <strong>' . $rating_average . ' <span class="dashicons dashicons-star-filled"></span></strong>';
			}
		$ratings_html .= '</span>';

		return $ratings_html;
	
	}

	/**
	 * Get rcb thumbnail
	 *
	 * @since 1.0.0
	 * @param array $src recipe card image data.
	 */
	public function get_thumbnail( $src, $size = 'thumbnail' ) {

		if( empty( $src ) ) {
			return;
		}

		$thumbnail = '<img src="' . esc_url( $src ) . '" width="150" height="150" alt="" />';

		return $thumbnail;

	}

	public function search_array( $array, $key, $value ) {
		$results = array();
	
		if ( is_array( $array ) ) {
			if ( isset( $array[$key] ) && $array[$key] == $value ) {
				$results[] = $array;
			}
	
			foreach ( $array as $subarray ) {
				$results = array_merge( $results, $this->search_array( $subarray, $key, $value ) );
			}
		}
	
		return $results;
	}
	

	/**
	 * Get rcb title
	 *
	 * @since 1.0.0
	 * @param array $title recipe data.
	 */
	public function get_title( $title ) {

		if( empty( $title ) ) {
			return;
		}

		$title_html = sprintf(
			'%s',
			$title
		);

		return $title_html;

	}

	/**
	 * Fix tags convert '<' & '>' to unicode.
	 *
	 * @since 4.0.0
	 *
	 * @param string $content The content which should parse.
	 * @return string
	 */
	public function fix_content_tags_conflict( $content ) {
		$content = preg_replace_callback(
			'#(?<!\\\\)(u003c|u003e)#',
			function( $matches ) {
				if ( 'u003c' === $matches[1] ) {
					return '<';
				} else {
					return '>';
				}
			},
			$content
		);

		return $content;
	}

}

add_action( 'init', 'WPZOOM_Recipe_Roundups_Block::instance' );