<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue frontend assets.
 */
function biblioteka_frontend_enqueue() {
    if ( ! is_admin() ) {
        wp_enqueue_style(
            'biblioteka-frontend-css',
            BIBLIOTEKA_PLUGIN_URL . 'public/css/biblioteka.css',
            array(),
            BIBLIOTEKA_VERSION
        );
        wp_enqueue_script(
            'biblioteka-frontend-js',
            BIBLIOTEKA_PLUGIN_URL . 'public/js/biblioteka.js',
            array(),
            BIBLIOTEKA_VERSION,
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'biblioteka_frontend_enqueue' );

/**
 * Register the [biblioteka] shortcode.
 */
function biblioteka_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'category' => '',
    ), $atts, 'biblioteka' );

    global $wpdb;
    $table_name = $wpdb->prefix . 'biblioteka_books';

    if ( ! empty( $atts['category'] ) ) {
        $books = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE category = %s ORDER BY title ASC",
                sanitize_text_field( $atts['category'] )
            )
        );
    } else {
        $books = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY category, title ASC" );
    }

    if ( empty( $books ) ) {
        return '<p class="biblioteka-empty">No books in the library yet.</p>';
    }

    // Group books by category.
    $grouped = array();
    foreach ( $books as $book ) {
        $grouped[ $book->category ][] = $book;
    }

    $categories = biblioteka_get_categories();

    ob_start();
    ?>
    <div class="biblioteka-library">

        <!-- Search / Filter Bar -->
        <div class="biblioteka-search">
            <div class="biblioteka-search-field">
                <label for="biblioteka-search-title">Title</label>
                <input type="text" id="biblioteka-search-title" placeholder="Search by title...">
            </div>
            <div class="biblioteka-search-field">
                <label for="biblioteka-search-author">Author</label>
                <input type="text" id="biblioteka-search-author" placeholder="Search by author...">
            </div>
            <div class="biblioteka-search-field">
                <label for="biblioteka-search-category">Category</label>
                <select id="biblioteka-search-category">
                    <option value="">All Categories</option>
                    <?php foreach ( $categories as $cat ) : ?>
                        <option value="<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( $cat ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="biblioteka-no-results" style="display:none;">No books match your search.</div>

        <?php foreach ( $grouped as $category => $cat_books ) : ?>
            <div class="biblioteka-category">
                <h3 class="biblioteka-category-title"><?php echo esc_html( $category ); ?></h3>
                <div class="biblioteka-book-list">
                    <?php foreach ( $cat_books as $book ) : ?>
                        <div class="biblioteka-book-item" data-book-id="<?php echo esc_attr( $book->id ); ?>"
                             data-title="<?php echo esc_attr( mb_strtolower( $book->title ) ); ?>"
                             data-author="<?php echo esc_attr( mb_strtolower( $book->author ) ); ?>"
                             data-category="<?php echo esc_attr( $book->category ); ?>">
                            <div class="biblioteka-book-header">
                                <span class="biblioteka-book-title"><?php echo esc_html( $book->title ); ?></span>
                                <span class="biblioteka-book-arrow">&#9662;</span>
                            </div>
                            <div class="biblioteka-book-details" style="display:none;">
                                <div class="biblioteka-book-details-inner">
                                    <?php if ( $book->image_url ) : ?>
                                        <div class="biblioteka-book-image">
                                            <img src="<?php echo esc_url( $book->image_url ); ?>"
                                                 alt="<?php echo esc_attr( $book->title ); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <div class="biblioteka-book-info">
                                        <?php if ( $book->description ) : ?>
                                            <p class="biblioteka-book-description"><?php echo nl2br( esc_html( $book->description ) ); ?></p>
                                        <?php endif; ?>
                                        <p class="biblioteka-book-author"><strong>Author:</strong> <?php echo esc_html( $book->author ); ?></p>
                                        <p class="biblioteka-book-cat"><strong>Category:</strong> <?php echo esc_html( $book->category ); ?></p>
                                        <?php if ( $book->related_post_url ) : ?>
                                            <p class="biblioteka-book-link">
                                                <a href="<?php echo esc_url( $book->related_post_url ); ?>" target="_blank" rel="noopener noreferrer">
                                                    Read related post &rarr;
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ( $book->bookstore_url ) : ?>
                                            <p class="biblioteka-bookstore-link">
                                                <a href="<?php echo esc_url( $book->bookstore_url ); ?>" target="_blank" rel="noopener noreferrer">
                                                    ZOBACZ W KSIĘGARNI &rarr;
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Desktop popup overlay -->
    <div class="biblioteka-popup-overlay" style="display:none;">
        <div class="biblioteka-popup">
            <button class="biblioteka-popup-close">&times;</button>
            <div class="biblioteka-popup-header"><?php echo esc_html( "CZYTAJ M\xC4\x84DRZE" ); ?></div>
            <div class="biblioteka-popup-content"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'biblioteka', 'biblioteka_shortcode' );
