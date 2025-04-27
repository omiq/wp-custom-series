import { registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { 
    PanelBody, 
    TextControl, 
    ToggleControl,
    RangeControl,
    ColorPicker,
    SelectControl,
    __experimentalUnitControl as UnitControl
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('custom-series/series-block', {
    title: __('Series List', 'custom-series'),
    icon: 'editor-ol',
    category: 'widgets',
    attributes: {
        seriesName: {
            type: 'string',
            default: ''
        },
        // Layout attributes
        alignment: {
            type: 'string',
            default: 'none'
        },
        // Display options
        showTitle: {
            type: 'boolean',
            default: true
        },
        showDescription: {
            type: 'boolean',
            default: true
        },
        // Spacing
        padding: {
            type: 'object',
            default: {
                top: '20px',
                right: '20px',
                bottom: '20px',
                left: '20px'
            }
        },
        margin: {
            type: 'object',
            default: {
                top: '2em',
                right: '0',
                bottom: '2em',
                left: '0'
            }
        },
        // Colors
        backgroundColor: {
            type: 'string',
            default: '#f9f9f9'
        },
        textColor: {
            type: 'string',
            default: ''
        },
        titleColor: {
            type: 'string',
            default: ''
        },
        // Border
        borderWidth: {
            type: 'number',
            default: 0
        },
        borderColor: {
            type: 'string',
            default: '#ddd'
        },
        borderRadius: {
            type: 'number',
            default: 4
        }
    },
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();
        
        // Get all unique series names
        const seriesList = useSelect((select) => {
            const posts = select('core').getEntityRecords('postType', 'post', {
                per_page: -1,
                _fields: ['meta']
            });
            
            if (!posts) return [];
            
            const seriesSet = new Set();
            posts.forEach(post => {
                if (post.meta && post.meta._series) {
                    seriesSet.add(post.meta._series);
                }
            });
            
            return Array.from(seriesSet).sort();
        }, []);

        // Update individual padding/margin values
        const updatePadding = (value, position) => {
            const newPadding = { ...attributes.padding };
            newPadding[position] = value;
            setAttributes({ padding: newPadding });
        };

        const updateMargin = (value, position) => {
            const newMargin = { ...attributes.margin };
            newMargin[position] = value;
            setAttributes({ margin: newMargin });
        };

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Series Settings', 'custom-series')}>
                        <TextControl
                            label={__('Series Name', 'custom-series')}
                            value={attributes.seriesName}
                            onChange={(value) => setAttributes({ seriesName: value })}
                            help={__('Leave empty to use the series from the current post', 'custom-series')}
                        />
                        {seriesList.length > 0 && (
                            <div className="series-suggestions">
                                <p>{__('Existing Series:', 'custom-series')}</p>
                                <ul>
                                    {seriesList.map((series) => (
                                        <li key={series}>
                                            <button
                                                onClick={() => setAttributes({ seriesName: series })}
                                                className="button button-small"
                                            >
                                                {series}
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </PanelBody>

                    <PanelBody title={__('Display Options', 'custom-series')} initialOpen={false}>
                        <ToggleControl
                            label={__('Show Series Title', 'custom-series')}
                            checked={attributes.showTitle}
                            onChange={(value) => setAttributes({ showTitle: value })}
                        />
                        <ToggleControl
                            label={__('Show Series Description', 'custom-series')}
                            checked={attributes.showDescription}
                            onChange={(value) => setAttributes({ showDescription: value })}
                        />
                        <SelectControl
                            label={__('Alignment', 'custom-series')}
                            value={attributes.alignment}
                            options={[
                                { label: __('None', 'custom-series'), value: 'none' },
                                { label: __('Left', 'custom-series'), value: 'left' },
                                { label: __('Center', 'custom-series'), value: 'center' },
                                { label: __('Right', 'custom-series'), value: 'right' }
                            ]}
                            onChange={(value) => setAttributes({ alignment: value })}
                        />
                    </PanelBody>

                    <PanelBody title={__('Colors', 'custom-series')} initialOpen={false}>
                        <div className="color-picker-control">
                            <p>{__('Background Color', 'custom-series')}</p>
                            <ColorPicker
                                color={attributes.backgroundColor}
                                onChangeComplete={(color) => setAttributes({ backgroundColor: color.hex })}
                            />
                        </div>
                        <div className="color-picker-control">
                            <p>{__('Text Color', 'custom-series')}</p>
                            <ColorPicker
                                color={attributes.textColor}
                                onChangeComplete={(color) => setAttributes({ textColor: color.hex })}
                            />
                        </div>
                        <div className="color-picker-control">
                            <p>{__('Title Color', 'custom-series')}</p>
                            <ColorPicker
                                color={attributes.titleColor}
                                onChangeComplete={(color) => setAttributes({ titleColor: color.hex })}
                            />
                        </div>
                    </PanelBody>

                    <PanelBody title={__('Spacing', 'custom-series')} initialOpen={false}>
                        <p>{__('Padding', 'custom-series')}</p>
                        <div className="spacing-controls">
                            <UnitControl
                                label={__('Top', 'custom-series')}
                                value={attributes.padding.top}
                                onChange={(value) => updatePadding(value, 'top')}
                                units={[
                                    { value: 'px', label: 'px' },
                                    { value: 'em', label: 'em' },
                                    { value: '%', label: '%' }
                                ]}
                            />
                            <UnitControl
                                label={__('Right', 'custom-series')}
                                value={attributes.padding.right}
                                onChange={(value) => updatePadding(value, 'right')}
                                units={[
                                    { value: 'px', label: 'px' },
                                    { value: 'em', label: 'em' },
                                    { value: '%', label: '%' }
                                ]}
                            />
                            <UnitControl
                                label={__('Bottom', 'custom-series')}
                                value={attributes.padding.bottom}
                                onChange={(value) => updatePadding(value, 'bottom')}
                                units={[
                                    { value: 'px', label: 'px' },
                                    { value: 'em', label: 'em' },
                                    { value: '%', label: '%' }
                                ]}
                            />
                            <UnitControl
                                label={__('Left', 'custom-series')}
                                value={attributes.padding.left}
                                onChange={(value) => updatePadding(value, 'left')}
                                units={[
                                    { value: 'px', label: 'px' },
                                    { value: 'em', label: 'em' },
                                    { value: '%', label: '%' }
                                ]}
                            />
                        </div>

                        <p>{__('Margin', 'custom-series')}</p>
                        <div className="spacing-controls">
                            <UnitControl
                                label={__('Top', 'custom-series')}
                                value={attributes.margin.top}
                                onChange={(value) => updateMargin(value, 'top')}
                                units={[
                                    { value: 'px', label: 'px' },
                                    { value: 'em', label: 'em' },
                                    { value: '%', label: '%' }
                                ]}
                            />
                            <UnitControl
                                label={__('Right', 'custom-series')}
                                value={attributes.margin.right}
                                onChange={(value) => updateMargin(value, 'right')}
                                units={[
                                    { value: 'px', label: 'px' },
                                    { value: 'em', label: 'em' },
                                    { value: '%', label: '%' }
                                ]}
                            />
                            <UnitControl
                                label={__('Bottom', 'custom-series')}
                                value={attributes.margin.bottom}
                                onChange={(value) => updateMargin(value, 'bottom')}
                                units={[
                                    { value: 'px', label: 'px' },
                                    { value: 'em', label: 'em' },
                                    { value: '%', label: '%' }
                                ]}
                            />
                            <UnitControl
                                label={__('Left', 'custom-series')}
                                value={attributes.margin.left}
                                onChange={(value) => updateMargin(value, 'left')}
                                units={[
                                    { value: 'px', label: 'px' },
                                    { value: 'em', label: 'em' },
                                    { value: '%', label: '%' }
                                ]}
                            />
                        </div>
                    </PanelBody>

                    <PanelBody title={__('Border', 'custom-series')} initialOpen={false}>
                        <RangeControl
                            label={__('Border Width', 'custom-series')}
                            value={attributes.borderWidth}
                            onChange={(value) => setAttributes({ borderWidth: value })}
                            min={0}
                            max={10}
                            step={1}
                        />
                        <div className="color-picker-control">
                            <p>{__('Border Color', 'custom-series')}</p>
                            <ColorPicker
                                color={attributes.borderColor}
                                onChangeComplete={(color) => setAttributes({ borderColor: color.hex })}
                            />
                        </div>
                        <RangeControl
                            label={__('Border Radius', 'custom-series')}
                            value={attributes.borderRadius}
                            onChange={(value) => setAttributes({ borderRadius: value })}
                            min={0}
                            max={20}
                            step={1}
                        />
                    </PanelBody>
                </InspectorControls>
                <ServerSideRender
                    block="custom-series/series-block"
                    attributes={attributes}
                />
            </div>
        );
    },
    save: function() {
        return null; // Dynamic block, rendered on server
    }
}); 