<?php
add_filter('post_thumbnail_html','add_class_to_thumbnail');
function add_class_to_thumbnail($thumb) {
    if( is_single() )
        $thumb = str_replace('attachment-', 'reflection attachment-', $thumb);
    return $thumb;
}
