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
allows the entry of users score cards, which are then viewable with the
rhac-scorecard-view plugin <tt>wp-content/plugins/rhac-scorecard-view</tt>.

The sunset plugin is just a utility that displays the sunset and last light
for a configured latitude and longditude. It's useful for our club
when shooting in the evenings during the summer to know exactly
how much daylight we have left.

The <tt>wp-content/themes/</tt> is just a backup of the modified
themes our website uses.

TODO
====

note to self: We can't use unoficcial rounds for classifications. We *can* use them
for handicaps, provided they're properly described.

* Add <tt>$round-&gt;getClassification($score, $gender, $bow, $age_group);</tt>
* Add capability to edit scorecard where has_ends = "N"
    * i.e. enter the scores directly: "new score" as opposed to "new scorecard"
* Add/update score/scorecard should include hidden has_ends = "Y"/"N" in the form.
* Add/update score/scorecard should include dropdown venue in the form.
* Add/update score/scorecard should (re-)calculate outdoor/indoor.
* Add/update score/scorecard should (re-)calculate handicap_ranking and classification.
* Add/update score/scorecard should (re-)calculate category.
* Re-build or update all calculable fields on demand.
    * pay attention to age group changes.
* Add an interface for defining new rounds.
* Re-do the scorecards admin interface altogether.
