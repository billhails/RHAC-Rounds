<?php
/*
Plugin Name: SunSet
Plugin URI: http://www.jonlynch.co.uk/wordpress-plugins/sunsets/
Description: Widget to display sunset times, copied and modified from SunTimes by Jon Lynch
Author: Bill Hails
Version: 0.1.0
Author URI: http://www.billhails.net
*/

class SunSetWidget extends WP_Widget {
    function SunSetWidget() {
        $widget_ops = array('classname' => 'SunSetWidget', 'description' => 'Displays sunset times' );
        $this->WP_Widget('SunSetWidget', 'SunSet', $widget_ops);
    }

    function form($instance) {
        $instance = wp_parse_args( (array) $instance, array( 'title' => '', 'lat'=>54, 'lon' => -3 ) );
        $title = $instance['title'];
        $lat = $instance['lat'];
        $lon = $instance['lon'];
        ?>
<p>
    <label for="<?php echo $this->get_field_id('title'); ?>">Title: <em>(eg Sunset for London)</em>
        <input class="widefat"
               id="<?php echo $this->get_field_id('title'); ?>"
               name="<?php echo $this->get_field_name('title'); ?>"
               type="text"
               value="<?php echo attribute_escape($title); ?>" />
   </label>
</p>
<p>To find the latitude and longitude of the location you would like the sunsets for visit
<a href="http://maps.google.com">Google Maps</a>, right click and choose <em>what's here?</em></p>
<p>
    <label for="<?php echo $this->get_field_id('lat'); ?>">Latitude:
        <input id="<?php echo $this->get_field_id('lat'); ?>"
               name="<?php echo $this->get_field_name('lat'); ?>"
               type="text"
               value="<?php echo attribute_escape($lat); ?>"
               size="8"/>
    </label>
</p>
<p>
    <label for="<?php echo $this->get_field_id('lon'); ?>">Longitude:
        <input id="<?php echo $this->get_field_id('lon'); ?>"
               name="<?php echo $this->get_field_name('lon'); ?>"
               type="text"
               value="<?php echo attribute_escape($lon); ?>"
               size="8"/>
    </label>
</p>
<?php
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = $new_instance['title'];
        $instance['lat'] = $new_instance['lat'];
        $instance['lon'] = $new_instance['lon'];
        return $instance;
    }

    function widget($args, $instance) {
        extract($args, EXTR_SKIP);
        $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
        $lat = $instance['lat'];
        $lon = $instance['lon'];
        echo $before_widget;

        if (!empty($title))
            echo $before_title . $title . $after_title;  

        $this->sunset_display( $lat, $lon );

        echo $after_widget;
    }

// --- Fetches and displayes the sunrise and sunset times ---
    function sunset_display( $lat, $lon) {
        date_default_timezone_set(get_option('timezone_string'));
        $sunset = $this->sunset($lat, $long, 90.8);
        $lastlight = $this->sunset($lat, $long, 96.0);

        ?><div id="sunsets">
            <ul>
                <li>Sunset: <?php echo $sunset ?></li>
                <li>Last Light: <?php echo $lastlight; ?></li>
            </ul>
        </div><?php
    }

    function sunset ($lat, $long, $zenith) {
        return date("g:ia", date_sunset(time(), SUNFUNCS_RET_TIMESTAMP, $lat, $long, $zenith));
    }

}

add_action( 'widgets_init', create_function('', 'return register_widget("SunSetWidget");') );

