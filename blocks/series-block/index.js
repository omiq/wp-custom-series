import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { 
    InspectorControls, 
    useBlockProps,
    PanelColorSettings,
    __experimentalPanelColorGradientSettings as PanelColorGradientSettings
} from '@wordpress/block-editor';
import { 
    PanelBody, 
    SelectControl, 
    ToggleControl
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
        className: `wp-block-custom-series-series-block${alignment ? ` align${alignment}` : ''}`
    });

    const fetchSeries = useCallback(async () => {
        try {
            setLoading(true);

            // Get all posts to extract series names
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

            // Get posts in chronological order (oldest first)
            const response = await apiFetch({
                path: `/wp/v2/posts?per_page=100&meta_key=_series&meta_value=${encodeURIComponent(series)}&orderby=date&order=asc&_fields=id,title,link,date,meta`
            });
            
            // Filter and sort posts - ensure we're getting the meta fields
            const filteredPosts = response
                .filter(post => post.meta && post.meta._series === series)
                .sort((a, b) => {
                    const dateA = new Date(a.date);
                    const dateB = new Date(b.date);
                    return dateA - dateB;
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
                    />
                    <ToggleControl
                        label={__('Show Series Title', 'custom-series')}
                        checked={showTitle}
                        onChange={(value) => setAttributes({ showTitle: value })}
                    />
                </PanelBody>
                <PanelColorGradientSettings
                    title={__('Color Settings', 'custom-series')}
                    settings={[
                        {
                            colorValue: attributes.backgroundColor,
                            onColorChange: (value) => setAttributes({ backgroundColor: value }),
                            label: __('Background Color', 'custom-series'),
                        },
                        {
                            colorValue: attributes.textColor,
                            onColorChange: (value) => setAttributes({ textColor: value }),
                            label: __('Text Color', 'custom-series'),
                        },
                    ]}
                />
            </InspectorControls>
            <div {...blockProps}>
                {loading ? (
                    <div className="series-placeholder">
                        {__('Loading series...', 'custom-series')}
                    </div>
                ) : error ? (
                    <div className="series-error">
                        <div className="error">{error}</div>
                    </div>
                ) : !seriesName ? (
                    <div className="series-placeholder">
                        {__('Select a series to display', 'custom-series')}
                    </div>
                ) : postsLoading ? (
                    <div className="series-placeholder">
                        {__('Loading posts...', 'custom-series')}
                    </div>
                ) : posts.length === 0 ? (
                    <div className="series-placeholder">
                        {__('No posts found in this series', 'custom-series')}
                    </div>
                ) : (
                    <>
                        {showTitle && seriesName && (
                            <h2 className="series-title">{seriesName}</h2>
                        )}
                        <div className="series-posts">
                            <ul className="series-posts-list">
                                {posts.map((post) => (
                                    <li key={post.id} className="series-post-item">
                                        <a href={post.link}>{post.title}</a>
                                        <span className="post-date">{new Date(post.date).toLocaleDateString()}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </>
                )}
            </div>
        </>
    );
};

// Server-side rendering for the frontend
const SeriesBlockSave = ({ attributes }) => {
    const { seriesName, showTitle, alignment } = attributes;
    
    const blockProps = useBlockProps.save({
        className: `wp-block-custom-series-series-block${alignment ? ` align${alignment}` : ''}`
    });

    return (
        <div {...blockProps}>
            {showTitle && seriesName && (
                <h2 className="series-title">{seriesName}</h2>
            )}
            <div className="series-posts">
                {/* This will be replaced with server-side rendered content */}
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
        },
        backgroundColor: {
            type: 'string',
            default: ''
        },
        textColor: {
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