CREATE TABLE archer (
    name TEXT NOT NULL PRIMARY KEY,
    wp_id INTEGER
);

INSERT INTO archer(name, wp_id) VALUES("Bill Hails", 1);
INSERT INTO archer(name, wp_id) VALUES("Dave Barrett", NULL);

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
    FOREIGN KEY (archer) REFERENCES archer(name)
);

CREATE INDEX scorecards_date ON scorecards(date);

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
