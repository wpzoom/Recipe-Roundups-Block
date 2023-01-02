/* eslint-disable react/jsx-curly-spacing */
/* eslint-disable eqeqeq */
/* eslint-disable comma-dangle */
/* eslint-disable no-multi-spaces */
/* eslint-disable key-spacing */
/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import icon from './icon';

/**
 * Internal dependencies
 */


import { InspectorControls } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, ToggleControl, Placeholder } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
const { serverSideRender: ServerSideRender } = wp;

import MultipleValueTextInput from 'react-multivalue-text-input';

const recipeCardIcon = (
    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="#e15819">
    <path d="M16 31.942l-0.209 0.058v-0.115l-11.675-3.221v-28.664l11.884 3.278 11.883-3.278v28.664l-11.675 3.221v0.115zM5.506 3.16v6.252l6.252 1.668v-6.252zM15.095 22.199v-6.253l-6.252-1.668v6.253zM17.459 4.606v25.204l8.756-2.415v-25.204zM18.155 12.73v-7.404l7.364-1.964v7.404zM19.267 6.182v5.101l5.14-1.371v-5.102z" fill="#e15819"></path>
    </svg>
);

import './editor.scss';
import './style.scss';

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType( 'wpzoom-blocks/recipe-roundups', {
	title:       __( 'Recipe Roundup', 'recipe-roundups-block' ),
	description: __( 'Add URLs and display a list with one or more recipes.', 'recipe-roundups-block' ),
	icon:        {
        // Specifying a background color to appear with the icon e.g.: in the inserter.
        // background: '#FDA921',
        // Specifying a color for the icon (optional: if not set, a readable color will be automatically defined)
        foreground: '#e15819',
        // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
        src: icon,
    },
	category:    'wpzoom-recipe-card',
	supports:    { align: false, html: false, multiple: true },
	attributes:  {
		externalUrls: {
			type:    'array',
		},
		showTotal: {
			type: 'boolean',
			default: true
		},
		hasImage: {
			type: 'boolean',
			default: true
		},
		hasRating: {
			type: 'boolean',
			default: true
		},
		hasCuisine: {
			type: 'boolean',
			default: true
		},
		hasDifficulty: {
			type: 'boolean',
			default: true
		},
		hasButton: {
			type: 'boolean',
			default: false
		},
		hasSchema: {
			type: 'boolean',
			default: true
		}
	},
	example:     {},
	edit: withSelect( ( select ) => {
		const { getEntityRecords } = select( 'core' );
		return {
			posts: getEntityRecords( 'postType', 'wpzoom_rcb', { order: 'desc', orderby: 'date', per_page: -1, metaKey: '_wpzoom_rcb_has_parent', metaValue: '1' } )
		};
		
	} )( ( props ) => {
		const { attributes, posts, setAttributes } = props;
		const { source, externalUrls, postId, showTotal, hasImage, hasRating, hasCuisine, hasDifficulty, hasButton, hasSchema } = attributes;

		const externalUrlsInput = (
			<MultipleValueTextInput
				onItemAdded = { ( item, allItems ) => setAttributes( { externalUrls: allItems } ) }
				onItemDeleted = { ( item, allItems ) => setAttributes( { externalUrls: allItems } ) }
				values = { externalUrls }
			/>
		)

		const externalURLsDescription = <p className="wpzoom-external-urls-description">{ __( 'Add a link and press the ENTER button.', 'recipe-roundups-block' ) }</p>;
		
		return (
			// eslint-disable-next-line react/jsx-no-undef
			<React.Fragment>
				<InspectorControls>
					<PanelBody title={ __( 'External URLs', 'recipe-roundups-block' ) }>
						{ externalUrlsInput }
						{ externalURLsDescription }
					</PanelBody>
					<PanelBody title={ __( 'Options', 'recipe-roundups-block' ) }>
						<ToggleControl
							label={__( 'Show Image?', 'recipe-roundups-block' ) }
							checked={hasImage}
							onChange={ () => setAttributes({ hasImage: !hasImage } )}
						/>
						<ToggleControl
							label={__( 'Show Total Time?', 'recipe-roundups-block' ) }
							checked={showTotal}
							onChange={ () => setAttributes({ showTotal: !showTotal } )}
						/>
						<ToggleControl
							label={__( 'Show Difficulty?', 'recipe-roundups-block' ) }
							checked={hasDifficulty}
							onChange={ () => setAttributes({ hasDifficulty: !hasDifficulty } )}
						/>
						<ToggleControl
							label={__( 'Show Rating?', 'recipe-roundups-block' ) }
							checked={hasRating}
							onChange={ () => setAttributes({ hasRating: !hasRating } )}
						/>
						<ToggleControl
							label={__( 'Show Cuisine?', 'recipe-roundups-block' ) }
							checked={hasCuisine}
							onChange={ () => setAttributes({ hasCuisine: !hasCuisine } )}
						/>
						<ToggleControl
							label={__( 'Add ItemList schema?', 'recipe-roundups-block' ) }
							checked={ hasSchema }
							onChange={ () => setAttributes({ hasSchema: !hasSchema } )}
						/>
					</PanelBody>
				</InspectorControls>

				<Fragment>
					{ ( externalUrls )
						? <ServerSideRender
							block="wpzoom-blocks/recipe-roundups"
							attributes={ attributes }
						  />
						: <Placeholder
							icon={ recipeCardIcon }
							label={ __( 'Recipes Roundups', 'recipe-roundups-block' ) }
						  >	
						  <p>{ __( 'Please, add an URL with the recipe', 'recipe-roundups-block' ) }</p>
						  </Placeholder>
					}
				</Fragment>
			</React.Fragment>
		);
	} )
} );
