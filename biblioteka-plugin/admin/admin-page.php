<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the admin menu page.
 */
function biblioteka_admin_menu() {
    add_menu_page(
        'Biblioteka',
        'Biblioteka',
        'manage_options',
        'biblioteka',
        'biblioteka_admin_page',
        'dashicons-book-alt',
        30
    );
}
add_action( 'admin_menu', 'biblioteka_admin_menu' );

/**
 * Enqueue admin styles.
 */
function biblioteka_admin_enqueue( $hook ) {
    if ( 'toplevel_page_biblioteka' !== $hook ) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style(
        'biblioteka-admin-css',
        BIBLIOTEKA_PLUGIN_URL . 'admin/admin-style.css',
        array(),
        BIBLIOTEKA_VERSION
    );
    wp_enqueue_script(
        'biblioteka-admin-js',
        BIBLIOTEKA_PLUGIN_URL . 'admin/admin-script.js',
        array( 'jquery' ),
        BIBLIOTEKA_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'biblioteka_admin_enqueue' );

/**
 * Handle form submissions (add, edit, delete, import).
 */
function biblioteka_handle_actions() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'biblioteka_books';

    // Delete book.
    if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['book_id'] ) ) {
        if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'biblioteka_delete_' . $_GET['book_id'] ) ) {
            wp_die( 'Security check failed.' );
        }
        $wpdb->delete( $table_name, array( 'id' => intval( $_GET['book_id'] ) ), array( '%d' ) );
        wp_redirect( admin_url( 'admin.php?page=biblioteka&msg=deleted' ) );
        exit;
    }

    // Add or update book.
    if ( isset( $_POST['biblioteka_save_book'] ) ) {
        if ( ! wp_verify_nonce( $_POST['biblioteka_nonce'] ?? '', 'biblioteka_save' ) ) {
            wp_die( 'Security check failed.' );
        }

        $data = array(
            'title'            => sanitize_text_field( $_POST['book_title'] ?? '' ),
            'description'      => sanitize_textarea_field( $_POST['book_description'] ?? '' ),
            'author'           => sanitize_text_field( $_POST['book_author'] ?? '' ),
            'category'         => sanitize_text_field( $_POST['book_category'] ?? '' ),
            'image_url'        => esc_url_raw( $_POST['book_image_url'] ?? '' ),
            'related_post_url' => esc_url_raw( $_POST['book_related_post_url'] ?? '' ),
            'bookstore_url'    => esc_url_raw( $_POST['book_bookstore_url'] ?? '' ),
        );
        $format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

        $edit_id = intval( $_POST['book_id'] ?? 0 );
        if ( $edit_id > 0 ) {
            $wpdb->update( $table_name, $data, array( 'id' => $edit_id ), $format, array( '%d' ) );
            wp_redirect( admin_url( 'admin.php?page=biblioteka&msg=updated' ) );
        } else {
            $wpdb->insert( $table_name, $data, $format );
            wp_redirect( admin_url( 'admin.php?page=biblioteka&msg=added' ) );
        }
        exit;
    }

    // Import from CSV.
    if ( isset( $_POST['biblioteka_import_csv'] ) ) {
        if ( ! wp_verify_nonce( $_POST['biblioteka_import_nonce'] ?? '', 'biblioteka_import' ) ) {
            wp_die( 'Security check failed.' );
        }
        if ( ! empty( $_FILES['csv_file']['tmp_name'] ) ) {
            $handle = fopen( $_FILES['csv_file']['tmp_name'], 'r' );
            if ( $handle ) {
                $header = fgetcsv( $handle ); // Skip header row.
                $count = 0;
                while ( ( $row = fgetcsv( $handle ) ) !== false ) {
                    if ( count( $row ) >= 3 && ! empty( trim( $row[0] ) ) ) {
                        $wpdb->insert(
                            $table_name,
                            array(
                                'title'    => sanitize_text_field( trim( $row[0] ) ),
                                'author'   => sanitize_text_field( trim( $row[1] ) ),
                                'category' => sanitize_text_field( trim( $row[2] ) ),
                            ),
                            array( '%s', '%s', '%s' )
                        );
                        $count++;
                    }
                }
                fclose( $handle );
                wp_redirect( admin_url( 'admin.php?page=biblioteka&msg=imported&count=' . $count ) );
                exit;
            }
        }
    }
}
add_action( 'admin_init', 'biblioteka_handle_actions' );

/**
 * Render the admin page.
 */
function biblioteka_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;
    $table_name  = $wpdb->prefix . 'biblioteka_books';
    $categories  = biblioteka_get_categories();
    $editing     = false;
    $edit_book   = null;

    // Check if editing.
    if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] && isset( $_GET['book_id'] ) ) {
        $edit_book = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", intval( $_GET['book_id'] ) )
        );
        if ( $edit_book ) {
            $editing = true;
        }
    }

    // Get filter category.
    $filter_category = sanitize_text_field( $_GET['filter_category'] ?? '' );

    // Fetch books.
    if ( $filter_category ) {
        $books = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM $table_name WHERE category = %s ORDER BY category, title ASC", $filter_category )
        );
    } else {
        $books = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY category, title ASC" );
    }

    // Messages.
    $msg = $_GET['msg'] ?? '';
    ?>
    <div class="wrap biblioteka-admin">
        <h1>Biblioteka</h1>

        <?php if ( 'added' === $msg ) : ?>
            <div class="notice notice-success is-dismissible"><p>Book added successfully.</p></div>
        <?php elseif ( 'updated' === $msg ) : ?>
            <div class="notice notice-success is-dismissible"><p>Book updated successfully.</p></div>
        <?php elseif ( 'deleted' === $msg ) : ?>
            <div class="notice notice-success is-dismissible"><p>Book deleted.</p></div>
        <?php elseif ( 'imported' === $msg ) : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo intval( $_GET['count'] ?? 0 ); ?> books imported successfully.</p></div>
        <?php endif; ?>

        <!-- Add/Edit Book Form -->
        <div class="biblioteka-form-section">
            <h2><?php echo $editing ? 'Edit Book' : 'Add New Book'; ?></h2>
            <form method="post" action="<?php echo admin_url( 'admin.php?page=biblioteka' ); ?>" class="biblioteka-form">
                <?php wp_nonce_field( 'biblioteka_save', 'biblioteka_nonce' ); ?>
                <input type="hidden" name="book_id" value="<?php echo $editing ? esc_attr( $edit_book->id ) : '0'; ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="book_title">Title</label></th>
                        <td><input type="text" id="book_title" name="book_title" class="regular-text" required
                                   value="<?php echo $editing ? esc_attr( $edit_book->title ) : ''; ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="book_description">Description</label></th>
                        <td><textarea id="book_description" name="book_description" class="large-text" rows="4"><?php echo $editing ? esc_textarea( $edit_book->description ) : ''; ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="book_author">Author</label></th>
                        <td><input type="text" id="book_author" name="book_author" class="regular-text" required
                                   value="<?php echo $editing ? esc_attr( $edit_book->author ) : ''; ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="book_category">Category</label></th>
                        <td>
                            <select id="book_category" name="book_category" required>
                                <option value="">— Select Category —</option>
                                <?php foreach ( $categories as $cat ) : ?>
                                    <option value="<?php echo esc_attr( $cat ); ?>"
                                        <?php echo ( $editing && $edit_book->category === $cat ) ? 'selected' : ''; ?>>
                                        <?php echo esc_html( $cat ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="book_image_url">Cover Image</label></th>
                        <td>
                            <input type="text" id="book_image_url" name="book_image_url" class="regular-text"
                                   value="<?php echo $editing ? esc_attr( $edit_book->image_url ) : ''; ?>">
                            <button type="button" class="button" id="biblioteka_upload_btn">Choose Image</button>
                            <div id="biblioteka_image_preview" class="biblioteka-image-preview">
                                <?php if ( $editing && $edit_book->image_url ) : ?>
                                    <img src="<?php echo esc_url( $edit_book->image_url ); ?>" alt="">
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="book_related_post_url">Related Post URL</label></th>
                        <td><input type="url" id="book_related_post_url" name="book_related_post_url" class="regular-text"
                                   value="<?php echo $editing ? esc_attr( $edit_book->related_post_url ) : ''; ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="book_bookstore_url">Bookstore URL</label></th>
                        <td><input type="url" id="book_bookstore_url" name="book_bookstore_url" class="regular-text"
                                   value="<?php echo $editing ? esc_attr( $edit_book->bookstore_url ) : ''; ?>"></td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="biblioteka_save_book" class="button button-primary">
                        <?php echo $editing ? 'Update Book' : 'Add Book'; ?>
                    </button>
                    <?php if ( $editing ) : ?>
                        <a href="<?php echo admin_url( 'admin.php?page=biblioteka' ); ?>" class="button">Cancel</a>
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <!-- Import CSV -->
        <div class="biblioteka-import-section">
            <h2>Import Books from CSV</h2>
            <p class="description">CSV format: Title, Author, Category (with header row).</p>
            <form method="post" action="<?php echo admin_url( 'admin.php?page=biblioteka' ); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field( 'biblioteka_import', 'biblioteka_import_nonce' ); ?>
                <input type="file" name="csv_file" accept=".csv" required>
                <button type="submit" name="biblioteka_import_csv" class="button">Import CSV</button>
            </form>
        </div>

        <!-- Book List -->
        <div class="biblioteka-list-section">
            <h2>Books (<?php echo count( $books ); ?>)</h2>

            <form method="get" class="biblioteka-filter">
                <input type="hidden" name="page" value="biblioteka">
                <label for="filter_category">Filter by category:</label>
                <select name="filter_category" id="filter_category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ( $categories as $cat ) : ?>
                        <option value="<?php echo esc_attr( $cat ); ?>"
                            <?php echo ( $filter_category === $cat ) ? 'selected' : ''; ?>>
                            <?php echo esc_html( $cat ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:5%">ID</th>
                        <th style="width:22%">Title</th>
                        <th style="width:15%">Author</th>
                        <th style="width:15%">Category</th>
                        <th style="width:8%">Image</th>
                        <th style="width:10%">Post</th>
                        <th style="width:10%">Bookstore</th>
                        <th style="width:10%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $books ) ) : ?>
                        <tr><td colspan="8">No books found.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $books as $book ) : ?>
                            <tr>
                                <td><?php echo esc_html( $book->id ); ?></td>
                                <td><?php echo esc_html( $book->title ); ?></td>
                                <td><?php echo esc_html( $book->author ); ?></td>
                                <td><?php echo esc_html( $book->category ); ?></td>
                                <td>
                                    <?php if ( $book->image_url ) : ?>
                                        <img src="<?php echo esc_url( $book->image_url ); ?>" alt="" style="max-width:50px;max-height:50px;">
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $book->related_post_url ) : ?>
                                        <a href="<?php echo esc_url( $book->related_post_url ); ?>" target="_blank">View</a>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $book->bookstore_url ) : ?>
                                        <a href="<?php echo esc_url( $book->bookstore_url ); ?>" target="_blank">View</a>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url( 'admin.php?page=biblioteka&action=edit&book_id=' . $book->id ); ?>">Edit</a>
                                    |
                                    <a href="<?php echo wp_nonce_url(
                                        admin_url( 'admin.php?page=biblioteka&action=delete&book_id=' . $book->id ),
                                        'biblioteka_delete_' . $book->id
                                    ); ?>" class="biblioteka-delete-link" style="color:#a00;">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Shortcode Info -->
        <div class="biblioteka-info-section">
            <h2>Display on Frontend</h2>
            <p>Use this shortcode on any page or post to display the book library:</p>
            <code>[biblioteka]</code>
        </div>
    </div>
    <?php
}
