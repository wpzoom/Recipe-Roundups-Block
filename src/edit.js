import { InspectorControls } from '@wordpress/block-editor';
import { registerBlockType, updateCategory } from '@wordpress/blocks';
import { Disabled, PanelBody, Placeholder } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import SearchableSelectControl from './searchable-select';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-block-editor/#useBlockProps
 */
 import { useBlockProps } from '@wordpress/block-editor';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit() {
	return (
		withSelect( ( select ) => {
			const { getEntityRecords } = select( 'core' );
	
			return {
				posts: getEntityRecords( 'postType', 'wpzoom_rcb', { order: 'asc', orderby: 'title', per_page: -1 } )
			};
		} )( ( props ) => {
			const { attributes, posts, setAttributes } = props;
			const { postId } = attributes;
			const _postId = postId && String( postId ).trim() != '' ? String( postId ) : '-1';
			const recipePosts = posts && posts.length > 0 ? posts.map( ( x ) => { return { key: String( x.id ), name: x.title.raw } } ) : [];
	
			const postSelect = (
				<SearchableSelectControl
					label={ __( 'Recipe Posts', 'zoom-recipePosts' ) }
					selectPlaceholder={ recipePosts.length < 1 ? __( 'No posts exist...', 'zoom-recipePosts' ) : __( 'Select a Post...', 'zoom-recipePosts' ) }
					searchPlaceholder={ __( 'Search...', 'zoom-recipePosts' ) }
					noResultsLabel={ __( 'Nothing found...', 'zoom-recipePosts' ) }
					options={ recipePosts }
					value={ recipePosts.find( x => x.key == _postId ) }
					onChange={ ( value ) => setAttributes( { postId: String( value.selectedItem.key ) } ) }
				/>
			);
	
			return (
				<>
					<InspectorControls>
						<PanelBody title={ __( 'Options', 'zoom-recipePosts' ) }>
							{ recipePosts.length > 0 ? postSelect : <Disabled>{ postSelect }</Disabled> }
						</PanelBody>
					</InspectorControls>
	
					<Fragment>
						{ '-1' != _postId
							? <ServerSideRender
								block="wpzoom-recipe-card/recipe-cards-from-posts"
								attributes={ attributes }
							  />
							: <Placeholder
								icon={ zoomFormsIcon }
								label={ __( 'Recipe Posts', 'zoom-forms' ) }
							  >
								{ recipePosts.length > 0 ? postSelect : <Disabled>{ postSelect }</Disabled> }
							  </Placeholder>
						}
					</Fragment>
				</>
			);
		} )
	);
}
