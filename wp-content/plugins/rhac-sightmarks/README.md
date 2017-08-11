# RHAC Sightmarks

This plugin creates the sightmarks calculator page.
Most of the code is javascript and makes use of jQuery.

The basic design is MVC with the graph and the table
representing the views.

Note that the file `laGrange.js` is unused, it was mistakenly
implemented as a "line of best fit" algorithm, when in fact
the laGrange function calculates the curve that actually
passes thropugh each point. However it is left here as a
curiosity and because it may be offered as an alternative
to the line of best fit at a later date.