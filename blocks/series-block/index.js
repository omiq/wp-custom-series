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

    // Helper function to safely extract title text
    const getTitleText = (title) => {
        if (typeof title === 'string') return title;
        if (title && typeof title === 'object' && title.rendered) return title.rendered;
        return '';
    };

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
                path: `/wp/v2/posts?per_page=100&meta_key=_series&meta_value=${encodeURIComponent(series)}&orderby=date&order=asc&_fields=id,title,link,date,meta`,
                parse: true
            });
            
            // Filter posts by series and ensure title is properly handled
            const filteredPosts = response
                .filter(post => post.meta && post.meta._series === series)
                .map(post => ({
                    ...post,
                    title: getTitleText(post.title)
                }));
            
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
                                        <a href={post.link}>{getTitleText(post.title)}</a>
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
    const { 
        seriesName, 
        showTitle, 
        alignment, 
        backgroundColor, 
        textColor, 
        borderColor, 
        borderWidth, 
        borderRadius,
        fontSize,
        fontFamily,
        fontWeight,
        lineHeight,
        textTransform,
        letterSpacing,
        style
    } = attributes;
    
    // Build style object with proper formatting
    const styleObject = {};
    
    if (backgroundColor) {
        styleObject.backgroundColor = `var(--wp--preset--color--${backgroundColor})`;
    }
    if (textColor) {
        styleObject.color = `var(--wp--preset--color--${textColor})`;
    }
    if (borderColor) {
        styleObject.borderColor = `var(--wp--preset--color--${borderColor})`;
    }
    if (borderWidth) {
        styleObject.borderWidth = borderWidth;
    }
    if (borderRadius) {
        styleObject.borderRadius = borderRadius;
    }
    if (fontSize) {
        styleObject.fontSize = `var(--wp--preset--font-size--${fontSize})`;
    }
    if (fontFamily) {
        styleObject.fontFamily = `var(--wp--preset--font-family--${fontFamily})`;
    }
    if (fontWeight) {
        styleObject.fontWeight = fontWeight;
    }
    if (lineHeight) {
        styleObject.lineHeight = `${lineHeight}em`;
    }
    if (textTransform) {
        styleObject.textTransform = textTransform;
    }
    if (letterSpacing) {
        styleObject.letterSpacing = `${letterSpacing}px`;
    }
    
    // Add spacing styles
    if (style) {
        if (style.spacing) {
            const spacing = style.spacing;
            if (spacing.margin) {
                styleObject.marginTop = spacing.margin.top ? `${spacing.margin.top}em` : undefined;
                styleObject.marginRight = spacing.margin.right ? `${spacing.margin.right}em` : undefined;
                styleObject.marginBottom = spacing.margin.bottom ? `${spacing.margin.bottom}em` : undefined;
                styleObject.marginLeft = spacing.margin.left ? `${spacing.margin.left}em` : undefined;
            }
            if (spacing.padding) {
                styleObject.paddingTop = spacing.padding.top ? `${spacing.padding.top}em` : undefined;
                styleObject.paddingRight = spacing.padding.right ? `${spacing.padding.right}em` : undefined;
                styleObject.paddingBottom = spacing.padding.bottom ? `${spacing.padding.bottom}em` : undefined;
                styleObject.paddingLeft = spacing.padding.left ? `${spacing.padding.left}em` : undefined;
            }
        }
    }
    
    const blockProps = useBlockProps.save({
        className: `wp-block-custom-series-series-block${alignment ? ` align${alignment}` : ''}${fontSize ? ` has-${fontSize}-font-size` : ''}${fontFamily ? ` has-${fontFamily}-font-family` : ''}${backgroundColor ? ` has-${backgroundColor}-background-color has-background` : ''}${textColor ? ` has-${textColor}-color has-text-color` : ''}${borderColor ? ` has-${borderColor}-border-color` : ''}`,
        style: styleObject
    });

    return (
        <div {...blockProps}>
            {showTitle && seriesName && (
                <h2 className="series-title">{seriesName}</h2>
            )}
            <div className="series-posts" style={lineHeight ? { lineHeight: `${lineHeight}em` } : undefined}>
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
        },
        borderColor: {
            type: 'string',
            default: ''
        },
        borderWidth: {
            type: 'string',
            default: ''
        },
        borderRadius: {
            type: 'string',
            default: ''
        },
        fontSize: {
            type: 'string',
            default: ''
        },
        fontFamily: {
            type: 'string',
            default: ''
        },
        fontWeight: {
            type: 'string',
            default: ''
        },
        lineHeight: {
            type: 'string',
            default: ''
        },
        textTransform: {
            type: 'string',
            default: ''
        },
        letterSpacing: {
            type: 'string',
            default: ''
        },
        style: {
            type: 'object',
            default: {}
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
            width: true
        },
        spacing: {
            margin: true,
            padding: true
        },
        typography: {
            fontSize: true,
            lineHeight: true,
            fontFamily: true,
            fontWeight: true,
            textTransform: true,
            letterSpacing: true
        }
    },
    edit: SeriesBlockEdit,
    save: SeriesBlockSave
});

// Export for testing purposes only
export { SeriesBlockEdit, SeriesBlockSave }; 