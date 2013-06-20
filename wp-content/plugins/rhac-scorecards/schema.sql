CREATE TABLE archer (
    name TEXT NOT NULL PRIMARY KEY,
    wp_id INTEGER
);

CREATE TABLE scorecards (
    id INTEGER NOT NULL PRIMARY KEY AUTO INCREMENT,
    archer_name TEXT NOT NULL,
    year integer NOT NULL,
    month integer NOT NULL,
    day integer NOT NULL,
    round TEXT NOT NULL,
    bow TEXT NOT NULL,
    hits INTEGER NOT NULL,
    xs INTEGER NOT NULL,
    golds INTEGER NOT NULL,
    score INTEGER NOT NULL,
    FOREIGN KEY (archer_name) REFERENCES archer(name)
);

CREATE INDEX scorecards_date ON scorecards(year, month, day);

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
    FOREIGN KEY (scorecard_id) REFERENCES scorecards(id)
);
