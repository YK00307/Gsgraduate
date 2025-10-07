CREATE TABLE user_words (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    word_id INT NOT NULL,
    mastered TINYINT(1) DEFAULT 0, -- 1=覚えた
    mistake_count INT DEFAULT 0,   -- 通算間違え回数
    correct_count INT DEFAULT 0,   -- 通算正解回数
    last_practiced DATETIME DEFAULT NULL,
    last_mode ENUM('mcq','fill') DEFAULT NULL,
    UNIQUE KEY (user_id, word_id)
);
