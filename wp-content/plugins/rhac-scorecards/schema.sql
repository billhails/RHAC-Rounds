CREATE TABLE scorecard_end (
    scorecard_id INTEGER NOT NULL,
    end_number INTEGER NOT NULL,
    arrow_1 TEXT NOT NULL DEFAULT "",
    arrow_2 TEXT NOT NULL DEFAULT "",
    arrow_3 TEXT NOT NULL DEFAULT "",
    arrow_4 TEXT NOT NULL DEFAULT "",
    arrow_5 TEXT NOT NULL DEFAULT "",
    arrow_6 TEXT NOT NULL DEFAULT "",
    PRIMARY KEY (scorecard_id, end_number),
    FOREIGN KEY (scorecard_id) REFERENCES scorecards(scorecard_id)
);
CREATE TABLE round_handicaps (
    round TEXT NOT NULL,
    compound TEXT NOT NULL,
    score INTEGER NOT NULL,
    handicap INTEGER NOT NULL,
    PRIMARY KEY (round, compound, score)
);
CREATE TABLE archer (
    name TEXT NOT NULL PRIMARY KEY,
    wp_id INTEGER,
    gender text not null default "M",
    date_of_birth TEXT not null default "0001/01/01"
, archived TEXT not null default "N");
CREATE TABLE venue(
venue_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
name text not null
);
CREATE TABLE scorecards (
    scorecard_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    archer TEXT NOT NULL,
    date TEXT NOT NULL,
    round TEXT NOT NULL,
    bow TEXT NOT NULL,
    hits INTEGER NOT NULL,
    xs INTEGER NOT NULL,
    golds INTEGER NOT NULL,
    score INTEGER NOT NULL,
    venue_id integer references venue(venue_id),
    handicap_ranking integer,
    has_ends text not null default "Y",
    classification TEXT,
    outdoor TEXT not null default "Y",
    handicap_improvement integer,
    new_classification text,
    club_record text not null default "N",
    medal text,
    category text,
    FOREIGN KEY (archer) REFERENCES archer(name)
);
CREATE INDEX scorecards_date ON scorecards(date);
CREATE UNIQUE INDEX round_handicap on round_handicaps(round, compound, handicap);
