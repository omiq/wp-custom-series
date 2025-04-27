import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { 
    PanelBody, 
    SelectControl, 
    ToggleControl, 
    RangeControl, 
    ColorPicker,
    __experimentalUnitControl as UnitControl
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { store as coreDataStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

const SeriesBlockEdit = (props) => {
    const { attributes, setAttributes } = props;
    const { seriesName, showTitle, alignment, textColor, backgroundColor } = attributes;
    const [seriesList, setSeriesList] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [posts, setPosts] = useState([]);
    const [postsLoading, setPostsLoading] = useState(false);

    const blockProps = useBlockProps({
        className: `wp-block-custom-series-block${alignment ? ` align${alignment}` : ''}`,
        style: {
            borderWidth: attributes.borderWidth ? `${attributes.borderWidth}px` : undefined,
            borderColor: attributes.borderColor || undefined,
            borderRadius: attributes.borderRadius ? `${attributes.borderRadius}px` : undefined,
            padding: attributes.padding ? `${attributes.padding}px` : undefined,
            margin: attributes.margin ? `${attributes.margin}px` : undefined
        }
    });

    useEffect(() => {
        fetchSeries();
    }, []);

    useEffect(() => {
        if (seriesName) {
            fetchPosts();
        }
    }, [seriesName]);

    const fetchSeries = async () => {
        try {
            setLoading(true);
            const posts = await apiFetch({
                path: '/wp/v2/posts?per_page=100&_fields=id,meta'
            });

            const uniqueSeries = [...new Set(
                posts
                    .map(post => post.meta._series)
                    .filter(series => series && series.length > 0)
            )].sort();

            setSeriesList(uniqueSeries);
            setError(null);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const fetchPosts = async () => {
        try {
            setPostsLoading(true);
            console.log('Fetching posts for series:', seriesName);
            
            const response = await apiFetch({
                path: `/wp/v2/posts?per_page=100&meta_key=_series&meta_value=${encodeURIComponent(seriesName)}`
            });
            
            console.log('Posts fetched:', response);
            
            const filteredPosts = response.filter(post => {
                return post.meta && post.meta._series === seriesName;
            });
            
            console.log('Filtered posts:', filteredPosts);
            setPosts(filteredPosts);
            setError(null);
        } catch (err) {
            console.error('Error fetching posts:', err);
            setError(err.message);
        } finally {
            setPostsLoading(false);
        }
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Series Settings', 'custom-series')}>
                    <SelectControl
                        label={__('Select Series', 'custom-series')}
                        value={seriesName}
                        options={[
                            { label: __('Select a series', 'custom-series'), value: '' },
                            ...seriesList.map(series => ({
                                label: series,
                                value: series
                            }))
                        ]}
                        onChange={(value) => setAttributes({ seriesName: value })}
                    />
                    <ToggleControl
                        label={__('Show Series Title', 'custom-series')}
                        checked={showTitle}
                        onChange={(value) => setAttributes({ showTitle: value })}
                    />
                </PanelBody>
                <PanelBody title={__('Styles', 'custom-series')} initialOpen={false}>
                    <SelectControl
                        label={__('Alignment', 'custom-series')}
                        value={alignment}
                        options={[
                            { label: __('Left', 'custom-series'), value: 'left' },
                            { label: __('Center', 'custom-series'), value: 'center' },
                            { label: __('Right', 'custom-series'), value: 'right' }
                        ]}
                        onChange={(value) => setAttributes({ alignment: value })}
                    />
                    <RangeControl
                        label={__('Border Width', 'custom-series')}
                        value={attributes.borderWidth || 0}
                        onChange={(value) => setAttributes({ borderWidth: value })}
                        min={0}
                        max={10}
                        allowReset={true}
                    />
                    <div className="components-base-control">
                        <label className="components-base-control__label">
                            {__('Border Color', 'custom-series')}
                        </label>
                        <ColorPicker
                            color={attributes.borderColor || '#000000'}
                            onChangeComplete={(value) => setAttributes({ borderColor: value.hex })}
                        />
                    </div>
                    <RangeControl
                        label={__('Border Radius', 'custom-series')}
                        value={attributes.borderRadius || 0}
                        onChange={(value) => setAttributes({ borderRadius: value })}
                        min={0}
                        max={20}
                        allowReset={true}
                    />
                    <UnitControl
                        label={__('Padding', 'custom-series')}
                        value={attributes.padding || 24}
                        onChange={(value) => setAttributes({ padding: parseInt(value) })}
                        units={[
                            { value: 'px', label: 'px' },
                            { value: '%', label: '%' },
                            { value: 'em', label: 'em' },
                            { value: 'rem', label: 'rem' }
                        ]}
                        min={0}
                        max={100}
                    />
                    <UnitControl
                        label={__('Margin', 'custom-series')}
                        value={attributes.margin || 32}
                        onChange={(value) => setAttributes({ margin: parseInt(value) })}
                        units={[
                            { value: 'px', label: 'px' },
                            { value: '%', label: '%' },
                            { value: 'em', label: 'em' },
                            { value: 'rem', label: 'rem' }
                        ]}
                        min={0}
                        max={100}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                {loading ? (
                    <p>{__('Loading series...', 'custom-series')}</p>
                ) : error ? (
                    <p className="error">{error}</p>
                ) : (
                    <>
                        {showTitle && seriesName && (
                            <h2 className="series-title">{seriesName}</h2>
                        )}
                        {postsLoading ? (
                            <p>{__('Loading posts...', 'custom-series')}</p>
                        ) : posts.length > 0 ? (
                            <ul className="series-posts">
                                {posts.map(post => (
                                    <li key={post.id}>
                                        <a href={post.link}>{post.title.rendered}</a>
                                    </li>
                                ))}
                            </ul>
                        ) : seriesName && (
                            <p>{__('No posts found in this series.', 'custom-series')}</p>
                        )}
                    </>
                )}
            </div>
        </>
    );
};

const SeriesBlockSave = ({ attributes }) => {
    const { 
        seriesName, 
        showTitle, 
        alignment,
        borderWidth,
        borderColor,
        borderRadius,
        padding,
        margin
    } = attributes;
    const blockProps = useBlockProps.save({
        className: `wp-block-custom-series-block${alignment ? ` align${alignment}` : ''}`,
        style: {
            borderWidth: borderWidth ? `${borderWidth}px` : undefined,
            borderColor: borderColor || undefined,
            borderRadius: borderRadius ? `${borderRadius}px` : undefined,
            padding: padding ? `${padding}px` : undefined,
            margin: margin ? `${margin}px` : undefined
        }
    });

    return (
        <div {...blockProps}>
            {showTitle && seriesName && (
                <h2 className="series-title">{seriesName}</h2>
            )}
            <div className="series-posts">
                {/* Posts will be loaded dynamically via PHP */}
                <div className="series-posts-container" data-series={seriesName}></div>
            </div>
        </div>
    );
};

// Register the block
registerBlockType('custom-series/series-block', {
    apiVersion: 2,
    title: __('Series Block', 'custom-series'),
    description: __('Display posts from a selected series.', 'custom-series'),
    category: 'widgets',
    icon: 'list-view',
    supports: {
        html: false,
        align: true,
        color: {
            text: true,
            background: true,
            gradients: true
        }
    },
    attributes: {
        seriesName: {
            type: 'string',
            default: ''
        },
        showTitle: {
            type: 'boolean',
            default: true
        },
        alignment: {
            type: 'string',
            default: ''
        },
        borderWidth: {
            type: 'number',
            default: 1
        },
        borderColor: {
            type: 'string',
            default: '#ddd'
        },
        borderRadius: {
            type: 'number',
            default: 4
        },
        padding: {
            type: 'number',
            default: 24
        },
        margin: {
            type: 'number',
            default: 32
        }
    },
    edit: SeriesBlockEdit,
    save: SeriesBlockSave
});

// Export for testing purposes only
export { SeriesBlockEdit, SeriesBlockSave }; 