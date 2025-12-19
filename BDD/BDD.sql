USE planning_poker_hs;

CREATE TABLE rule (
    rule_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE game(
    game_id INT PRIMARY KEY AUTO_INCREMENT,
    rule_id INT NOT NULL,
    status VARCHAR(50),
    invite_id VARCHAR(100) NOT NULL,
    started_at DATETIME,
    ended_at DATETIME NULL,
    current_item_id INT NULL,
    nb_invited_players INT NOT NULL,
    FOREIGN KEY (rule_id) REFERENCES rule(rule_id)
);

CREATE TABLE player(
    player_id INT PRIMARY KEY AUTO_INCREMENT,
    pseudo VARCHAR(50) NOT NULL,
    game_id INT NOT NULL,
    is_host BOOLEAN NOT NULL,
    FOREIGN KEY (game_id) REFERENCES game(game_id)
);

CREATE TABLE backlog_item (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    game_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    estimated_difficulty FLOAT,
    last_round_number INT NOT NULL,
    status VARCHAR(50)
);


CREATE TABLE vote (
    vote_id INT PRIMARY KEY AUTO_INCREMENT,
    player_id INT NOT NULL,
    item_id INT NOT NULL,
    value INT NOT NULL,
    round_number INT,
    FOREIGN KEY (player_id) REFERENCES player(player_id),
    FOREIGN KEY (item_id) REFERENCES backlog_item(item_id)
);


INSERT INTO rule (name) VALUES('Mode Strict (Unanimité)');
INSERT INTO rule (name) VALUES('Moyenne');
INSERT INTO rule (name) VALUES('Médiane');
INSERT INTO rule (name) VALUES('Majorité absolue');
INSERT INTO rule (name) VALUES('Majorité relative');