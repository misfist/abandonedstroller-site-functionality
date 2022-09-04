/**
 * External Dependencies
 */
import classnames from 'classnames';

/**
 * WordPress Dependencies
 */
const { __ } = wp.i18n;
const { addFilter } = wp.hooks;
const { Fragment } = wp.element;
const { createHigherOrderComponent } = wp.compose;
const { ToggleControl } = wp.components;

import {
	InspectorAdvancedControls,
} from '@wordpress/block-editor';

const allowedBlocks = [
    'core/cover',
    'core/image',
    'core/post-featured-image'
];

/**
 * Add custom attribute for parallax display
 * 
 * @see https://developer.wordpress.org/block-editor/reference-guides/filters/block-filters/
 *
 * @param {Object} settings Settings for the block.
 * @return {Object} settings Modified settings.
 */
function addAttributes(settings) {
    if (typeof settings.attributes !== 'undefined' && allowedBlocks.includes(settings.name)) {

        settings.attributes = Object.assign(settings.attributes, {
            isLightbox: {
                type: 'boolean',
                default: false,
            }
        });

    }

    return settings;
}

/**
 * Add parallax controls on Advanced Block Panel.
 * 
 * @see https://developer.wordpress.org/block-editor/reference-guides/filters/block-filters/
 *
 * @param {function} BlockEdit Block edit component.
 * @return {function} BlockEdit Modified block edit component.
 */
const withAdvancedControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {

        const {
            name,
            attributes,
            setAttributes,
            isSelected,
        } = props;

        const {
            isLightbox,
        } = attributes;


        return (
            <Fragment>
                <BlockEdit {...props} />
                {isSelected && allowedBlocks.includes(name) &&
                    <InspectorAdvancedControls>
                        <ToggleControl
                            label={__('Lightbox', 'abandoned-blocks')}
                            checked={!!isLightbox}
                            onChange={() => setAttributes({ isLightbox: !isLightbox })}
                            help={!!isLightbox ? __('Display in lightbox.', 'abandoned-blocks') : __('Don\'t display in lightbox.', 'abandoned-blocks')}
                        />
                    </InspectorAdvancedControls>
                }
            </Fragment>
        );
    };
}, 'withAdvancedControls');

/**
 * Add custom element class in save element.
 * 
 * @see https://developer.wordpress.org/block-editor/reference-guides/filters/block-filters/
 *
 * @param {Object} extraProps     Block element.
 * @param {Object} blockType      Blocks object.
 * @param {Object} attributes     Blocks attributes.
 * @return {Object} extraProps Modified block element.
 */
function applyExtraClass(extraProps, blockType, attributes) {

    const { isLightbox } = attributes;

    if (typeof isLightbox !== 'undefined' && isLightbox && allowedBlocks.includes(blockType.name)) {
        extraProps.className = classnames(extraProps.className, 'foobox');
    }

    return extraProps;
}

addFilter(
    'blocks.registerBlockType',
    'editorskit/custom-attributes',
    addAttributes
);

addFilter(
    'editor.BlockEdit',
    'editorskit/custom-advanced-control',
    withAdvancedControls
);

addFilter(
    'blocks.getSaveContent.extraProps',
    'editorskit/applyExtraClass',
    applyExtraClass
);