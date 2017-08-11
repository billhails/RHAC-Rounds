# RHAC Targets

This plugin supplies the target pictures page where you can
compare the relative apparent sizes of targets at different
distances. The code is simple, and consists of two files:

* `rhac-targets.php`
  > registers the plugin and creates a shortcode that
  > calls it. The PHP code merely creates a canvas and
  > loads the javascript.
* `rhac-targets.js`
  > Draws targets on the canvas and allows manipulation
  > of the targets.