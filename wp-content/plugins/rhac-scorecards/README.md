# RHAC Scorecards

This plugin is concerned with maintaining the scorecards table,
specifically calculating the badges attached to individual scorecards.

# How Accumulators Work

We start by creating an instance of `RHAC_ScorecardAccumulator`.
This in turn creates child accumulators for each type of accumulator
in the manifest, currently

* `RHAC_ClubRecordAccumulator`
* `RHAC_PersonalBestAccumulator`
* `RHAC_HandicapImprovementAccumulator`
* `RHAC_NewClassificationAccumulator`
* `RHAC_252Accumulator`

At this stage we have a tree:
```
RHAC_ScorecardAccumulator
    |
    +--- RHAC_ClubRecordAccumulator
    |
    +--- RHAC_PersonalBestAccumulator
    |
    +--- RHAC_HandicapImprovementAccumulator
    |
    +--- RHAC_NewClassificationAccumulator
    |
    +--- RHAC_252Accumulator
```

Then code in `RHAC_Scorecards` from `toplevel.php` performs a select
on the entire scorecards table, in a very specific order, first
date, then handicap for the score, then the score itself. For each
row in the result of that select, it calls `accept($row)` on the
`RHAC_ScorecardAccumulator`.

The `RHAC_ScorecardAccumulator::accept()` method merely passes the
row to the `accept()` method of each of its children (Composite
Pattern).

Each child `accept()` method is similar. First of all it may decide
to do nothing, for example the `RHAC_252Accumulator::accept()`
method simply returns if the round is not a 252. In most cases
though, all scorecards are processed by each accumulator.

If the scorecard is relevant, then the accumulator considers
distinguishing features of the row. The distinguishing features
differ by accumulator. For example the 252 accumulator distinguishes
scorecards purely by archer and bow, wheras the club records
accumulator distinguishes scorecards by bow, age, gender and round.

The accumulator creates a key from the significant fields and uses
that key to see if it has an "accumulator leaf" for that key. If
not it creates one. Each accumultor type has an associated accumulator
leaf class in the same file, for example `RHAC_ClubRecordAccumulator`
has an associated `RHAC_ClubRecordAccumulatorLeaf` class.

The accumulator leaf is then passed the scorecard in its own
`accept()` method.

The basic idea then is that each accumulator leaf is specialised
for a particular set of data, for example a `RHAC_252AccumulatorLeaf`
is specialised to a specific archer and bow, and will only recieve
scorecards for that archer and bow, while a
`RHAC_ClubRecordAccumulatorLeaf` is specialised to a specific bow,
age, gender and round and only recieves scorecards for that
combination.

Assume the following three scorecards have been processed:

Archer | Age | Gender | Bow | Round | ...
------ | --- | ------ | --- | ----- | ---
john doe | adult | m | longbow | white 252 | ...
jane doe | adult | f | compound | portsmouth | ...
fred doe | u14 | m | recurve | short metric | ...

Then we would have a tree like the following:
```
RHAC_ScorecardAccumulator
    |
    +--- RHAC_ClubRecordAccumulator
    |        |
    |        +-recurve-u14-m-short_metric---RHAC_ClubRecordAccumulatorLeaf
    |        |
    |        +-compound-adult-f-portsmouth---RHAC_ClubRecordAccumulatorLeaf
    |        |
    |        +-longbow-adult-m-white252---RHAC_ClubRecordAccumulatorLeaf
    |
    +--- RHAC_PersonalBestAccumulator
    |        |
    |        +-john_doe-recurve-short_metric---RHAC_PersonalBestAccumulatorLeaf
    |        |
    |        +-jane_doe-compound-portsmouth---RHAC_PersonalBestAccumulatorLeaf
    |        |
    |        +-fred_doe-longbow-white252---RHAC_PersonalBestAccumulatorLeaf
    |
    +--- RHAC_HandicapImprovementAccumulator
    |        |
    |        +-john_doe-recurve-outdoor---RHAC_HandicapImprovementAccumulatorLeaf
    |        |
    |        +-jane_doe-compound-indoor---RHAC_HandicapImprovementAccumulatorLeaf
    |        |
    |        +-fred_doe-longbow-outdoor---RHAC_HandicapImprovementAccumulatorLeaf
    |
    +--- RHAC_NewClassificationAccumulator
    |        |
    |        +-john_doe-recurve-outdoor---RHAC_NewClassificationAccumulatorLeaf
    |        |
    |        +-jane_doe-compound-indoor---RHAC_NewClassificationAccumulatorLeaf
    |        |
    |        +-fred_doe-longbow-outdoor---RHAC_NewClassificationAccumulatorLeaf
    |
    +--- RHAC_252Accumulator
             |
             +-fred_doe-longbow---RHAC_252AccumulatorLeaf
```

So, consider for example the `RHAC_ClubRecordAccumulatorLeaf` for
recurve under 14 male short metric.  It only receives scorecards
for that particular bow, age, gender and round, and recieves them
in the order they were shot, further ordered by handicap and score.
So all it needs to do is keep track of the current club record (and
the archer(s) who hold it,) and when a scorecard comes in that beats
it, update the current record holders to be old record holders and
replace them with the new record holder.

Other accumulator leaves have similarily simple tasks to perform.
However there is one slight complication in that existing club
records could be upset by a scorecard coming in late, or even a bug
in this code, so each accumulator leaf looks at the current value
of the relevant badge on the scorecard and updates it if it calculates
that it is wrong.

For efficiency's sake the acceumulator leaves do not directly update
rows in the database. Rather they collect "recommendations" for
changes to scorecards, and they are allowed to change their minds
(a current club record becoming an old club record is an obvious
case). When all of the scorecards are processed, the recommendations
are collected into a set of updates keyed on scorecard id, and those
updates are run afterwards.

For example one such update might be

```sql
UPDATE scorecards
SET two_five_two = "Y", club_record = "N"
WHERE id = 1234
```

Note the multiple changes in a single update statement.
