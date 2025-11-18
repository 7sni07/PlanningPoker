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
    FOREIGN KEY (rule_id) REFERENCES rules(rule_id)
);

CREATE TABLE player(
    player_id INT PRIMARY KEY AUTO_INCREMENT,
    pseudo VARCHAR(50) NOT NULL,
    game_id INT NOT NULL,
    FOREIGN KEY (game_id) REFERENCES games(game_id)
);

CREATE TABLE backlog_item (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    game_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    estimated_difficulty FLOAT,
    status VARCHAR(50)
);


CREATE TABLE vote (
    vote_id INT PRIMARY KEY AUTO_INCREMENT,
    player_id INT NOT NULL,
    item_id INT NOT NULL,
    value INT NOT NULL,
    round_number INT,
    created_at DATETIME,
    FOREIGN KEY (player_id) REFERENCES players(player_id),
    FOREIGN KEY (item_id) REFERENCES backlog_items(item_id)
);