import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps, withColors, PanelColorSettings } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl, AlignmentControl } from '@wordpress/components';
import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { compose } from '@wordpress/compose';

const SeriesBlockEdit = ({ attributes, setAttributes, textColor, backgroundColor, setTextColor, setBackgroundColor }) => {
    const [seriesList, setSeriesList] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);
    const [seriesPosts, setSeriesPosts] = useState([]);
    const [isLoadingPosts, setIsLoadingPosts] = useState(false);
    
    // Get block props for proper selection
    const blockProps = useBlockProps({
        style: {
            textAlign: attributes.alignment,
            color: textColor?.color,
            backgroundColor: backgroundColor?.color
        }
    });

    // Memoize the fetch series function
    const fetchSeries = useCallback(async () => {
        try {
            setIsLoading(true);
            setError(null);
            
            const response = await apiFetch({
                path: '/wp/v2/posts?per_page=100&_fields=id,title,link,meta,date',
            });
            
            if (!response || !Array.isArray(response)) {
                throw new Error('Invalid response format');
            }
            
            const series = new Set();
            response.forEach(post => {
                if (post.meta && post.meta._series) {
                    series.add(post.meta._series);
                }
            });
            
            const sortedSeries = Array.from(series).sort();
            if (sortedSeries.length === 0) {
                setError(__('No series found. Please create some posts with series assigned.', 'custom-series'));
            }
            setSeriesList(sortedSeries);
        } catch (error) {
            console.error('Error fetching series:', error);
            setError(error.message || 'Failed to fetch series data');
        } finally {
            setIsLoading(false);
        }
    }, []);

    // Memoize the fetch series posts function
    const fetchSeriesPosts = useCallback(async () => {
        if (!attributes.seriesName) {
            setSeriesPosts([]);
            return;
        }

        try {
            setIsLoadingPosts(true);
            
            const response = await apiFetch({
                path: '/wp/v2/posts?per_page=100&_fields=id,title,link,meta,date',
            });
            
            if (!response || !Array.isArray(response)) {
                throw new Error('Invalid response format');
            }
            
            const filteredPosts = response.filter(post => 
                post.meta && post.meta._series === attributes.seriesName
            );
            
            filteredPosts.sort((a, b) => new Date(b.date) - new Date(a.date));
            
            setSeriesPosts(filteredPosts);
        } catch (error) {
            console.error('Error fetching series posts:', error);
            setError(error.message || 'Failed to fetch series posts');
        } finally {
            setIsLoadingPosts(false);
        }
    }, [attributes.seriesName]);

    // Fetch series data when component mounts
    useEffect(() => {
        fetchSeries();
    }, [fetchSeries]);

    // Fetch posts for the selected series
    useEffect(() => {
        fetchSeriesPosts();
    }, [fetchSeriesPosts]);

    // Memoize the series options
    const seriesOptions = useMemo(() => [
        { label: __('Select a series...', 'custom-series'), value: '' },
        ...seriesList.map(name => ({ label: name, value: name }))
    ], [seriesList]);

    // Memoize the color settings
    const colorSettings = useMemo(() => [
        {
            value: textColor?.color,
            onChange: setTextColor,
            label: __('Text Color', 'custom-series')
        },
        {
            value: backgroundColor?.color,
            onChange: setBackgroundColor,
            label: __('Background Color', 'custom-series')
        }
    ], [textColor?.color, backgroundColor?.color, setTextColor, setBackgroundColor]);

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title={__('Series Settings', 'custom-series')}>
                    <SelectControl
                        label={__('Select Series', 'custom-series')}
                        value={attributes.seriesName}
                        options={seriesOptions}
                        onChange={(value) => setAttributes({ seriesName: value })}
                        disabled={isLoading}
                    />
                    <ToggleControl
                        label={__('Show Title', 'custom-series')}
                        checked={attributes.showTitle}
                        onChange={(value) => setAttributes({ showTitle: value })}
                    />
                    <AlignmentControl
                        value={attributes.alignment}
                        onChange={(value) => setAttributes({ alignment: value })}
                    />
                </PanelBody>
                <PanelColorSettings
                    title={__('Color Settings', 'custom-series')}
                    colorSettings={colorSettings}
                />
            </InspectorControls>
            
            <div className="wp-block-custom-series-series-block">
                {isLoading ? (
                    <p>{__('Loading series...', 'custom-series')}</p>
                ) : error ? (
                    <p className="error-message">{error}</p>
                ) : attributes.seriesName ? (
                    <>
                        {attributes.showTitle && (
                            <h2>{attributes.seriesName}</h2>
                        )}
                        <div className="series-posts-list">
                            {isLoadingPosts ? (
                                <p>{__('Loading posts...', 'custom-series')}</p>
                            ) : seriesPosts.length > 0 ? (
                                <ul>
                                    {seriesPosts.map(post => (
                                        <li key={post.id}>
                                            <a href={post.link}>{post.title.rendered}</a>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p>{__('No posts found in this series.', 'custom-series')}</p>
                            )}
                        </div>
                    </>
                ) : (
                    <p>{__('Please select a series from the block settings.', 'custom-series')}</p>
                )}
            </div>
        </div>
    );
};

const SeriesBlockSave = ({ attributes }) => {
    if (!attributes.seriesName) {
        return null;
    }

    return (
        <div className="wp-block-custom-series-series-block">
            {attributes.showTitle && (
                <h2>{attributes.seriesName}</h2>
            )}
            <div className="series-posts-list">
                {/* Posts will be loaded dynamically */}
            </div>
        </div>
    );
};

// Export the components for testing and development
export { SeriesBlockEdit, SeriesBlockSave };

// Register the block
registerBlockType('custom-series/series-block', {
    apiVersion: 3,
    title: __('Series List', 'custom-series'),
    icon: 'editor-ol',
    category: 'widgets',
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
            default: 'left'
        },
        textColor: {
            type: 'string',
            default: ''
        },
        backgroundColor: {
            type: 'string',
            default: ''
        }
    },
    edit: compose([
        withColors('textColor', 'backgroundColor')
    ])(SeriesBlockEdit),
    save: SeriesBlockSave
}); 