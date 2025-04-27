import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const SeriesBlockEdit = (props) => {
    const { attributes, setAttributes } = props;
    const { seriesName, showTitle } = attributes;
    const [series, setSeries] = useState([]);
    const [posts, setPosts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const blockProps = useBlockProps({
        className: 'wp-block-custom-series-block'
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

            setSeries(uniqueSeries);
            setError(null);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const fetchPosts = async () => {
        try {
            setLoading(true);
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
            setLoading(false);
        }
    };

    const seriesOptions = [
        { label: __('Select a series', 'custom-series'), value: '' },
        ...series.map(name => ({
            label: name,
            value: name
        }))
    ];

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Series Settings', 'custom-series')}>
                    <SelectControl
                        label={__('Series', 'custom-series')}
                        value={seriesName}
                        options={seriesOptions}
                        onChange={(value) => setAttributes({ seriesName: value })}
                    />
                    <ToggleControl
                        label={__('Show Title', 'custom-series')}
                        checked={showTitle}
                        onChange={(value) => setAttributes({ showTitle: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                {loading && <p>{__('Loading series...', 'custom-series')}</p>}
                {error && <p className="error">{error}</p>}
                {!loading && !error && (
                    <>
                        {showTitle && seriesName && (
                            <h2 className="series-title">{seriesName}</h2>
                        )}
                        <div className="series-posts">
                            {seriesName ? (
                                posts.length > 0 ? (
                                    <ul>
                                        {posts.map(post => (
                                            <li key={post.id}>
                                                <a href={post.link}>{post.title.rendered}</a>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p>{__('No posts found in this series.', 'custom-series')}</p>
                                )
                            ) : (
                                <p>{__('Please select a series from the block settings.', 'custom-series')}</p>
                            )}
                        </div>
                    </>
                )}
            </div>
        </>
    );
};

const SeriesBlockSave = ({ attributes }) => {
    const { seriesName, showTitle } = attributes;
    const blockProps = useBlockProps.save({
        className: 'wp-block-custom-series-block'
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
        }
    },
    edit: SeriesBlockEdit,
    save: SeriesBlockSave
});

// Export for testing purposes only
export { SeriesBlockEdit, SeriesBlockSave }; 