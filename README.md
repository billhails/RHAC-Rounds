RHAC-Rounds
===========

A WordPress plugin to manage and display GNAS (ArcheryGB) rounds
and classifications, plus other bits and pieces.

This project is necessarily of great interest to a very few people,
namely admins of UK archery club websites running WordPress.
Surprisingly I know of at least one other :-).

The classifications plugin is in
<tt>wp-content/plugins/gnas-archery-rounds</tt>.  The classifications
data in <tt>dump.sql</tt> and in the <tt>archery.db</tt> has been
proofread and fixed via the admin screens of the running plugin.
The source of the data is the Archery GB Shooting Administrative
Procedures document issued March 2013, available from
http://www.archerygb.org/ which remains the authoratative source.
Any errors in the data presented here are totally my fault, though
I have tried to be careful.  In any case the plugin gives you the
ability to amend the data.

**I cannot make any promises to maintain this data** but I will try.

By the way, RHAC is Royston Heath Archery Club: http://roystonarchery.org/

The Hover plugin in <tt>wp-content/plugins/hover</tt> is a temporary
fork in the official hover plugin by Stefan V&ouml;lkel - I've tried
to contact the author but had no reply so far.  My fork supports longest
match first and fixes a few bugs where matches overlap, for example
round names like <q>Metric</q>, <q>Short Metric</q> and <q>Short
Metric II</q> tended to confuse the older version.

The rhac-scorecards plugin in <tt>wp-content/plugins/rhac-scorecards</tt>
is currently a Work In Progress.  The intention is to provide an
admin interface where scorecards can be entered and edited, and
final scores reviewed for entry into other software like Golden
Records, at the same time storing the scorecards and allowing members
to view their own scorecards as well as providing summaries in chart
form (bar and/or pie charts) based on round and/or distance.

The <tt>wp-content/themes/</tt> is just a backup of the modified
themes our website uses.

