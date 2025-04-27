import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { 
    InspectorControls, 
    useBlockProps,
    PanelColorSettings
} from '@wordpress/block-editor';
import { 
    PanelBody, 
    SelectControl, 
    ToggleControl,
    RangeControl
} from '@wordpress/components';
import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const SeriesBlockEdit = (props) => {
    const { attributes, setAttributes } = props;
    const { seriesName, showTitle, alignment } = attributes;
    const [seriesList, setSeriesList] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [posts, setPosts] = useState([]);
    const [postsLoading, setPostsLoading] = useState(false);

    const blockProps = useBlockProps({
        className: `wp-block-custom-series-block${alignment ? ` align${alignment}` : ''}`
    });

    const fetchSeries = useCallback(async () => {
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
    }, []);

    const fetchPosts = useCallback(async (series) => {
        if (!series) return;
        
        try {
            setPostsLoading(true);
            const response = await apiFetch({
                path: `/wp/v2/posts?per_page=100&meta_key=_series&meta_value=${encodeURIComponent(series)}`
            });
            
            const filteredPosts = response.filter(post => {
                return post.meta && post.meta._series === series;
            });
            
            setPosts(filteredPosts);
            setError(null);
        } catch (err) {
            setError(err.message);
        } finally {
            setPostsLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchSeries();
    }, [fetchSeries]);

    useEffect(() => {
        if (seriesName) {
            fetchPosts(seriesName);
        } else {
            setPosts([]);
        }
    }, [seriesName, fetchPosts]);

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
                        __next40pxDefaultSize={true}
                        __nextHasNoMarginBottom={true}
                    />
                    <ToggleControl
                        label={__('Show Series Title', 'custom-series')}
                        checked={showTitle}
                        onChange={(value) => setAttributes({ showTitle: value })}
                        __nextHasNoMarginBottom={true}
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
        alignment
    } = attributes;
    
    // Create a style object that matches the expected format exactly
    const styleObject = {
        borderColor: '',
        borderWidth: '',
        borderStyle: '',
        borderRadius: ''
    };
    
    const blockProps = useBlockProps.save({
        className: `wp-block-custom-series-series-block wp-block-custom-series-block${alignment ? ` align${alignment}` : ''}`,
        style: styleObject
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
        }
    },
    supports: {
        align: true,
        html: false,
        color: {
            text: true,
            background: true,
            gradients: true
        },
        border: {
            color: true,
            radius: true,
            style: true,
            width: true
        },
        spacing: {
            margin: true,
            padding: true
        },
        typography: {
            fontSize: true,
            lineHeight: true
        }
    },
    edit: SeriesBlockEdit,
    save: SeriesBlockSave
});

// Export for testing purposes only
export { SeriesBlockEdit, SeriesBlockSave }; 