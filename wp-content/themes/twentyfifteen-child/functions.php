<?php

add_action( 'wp_enqueue_scripts', 'theme_enqueue_mystyles' );
function theme_enqueue_mystyles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
