CREATE TABLE mastery_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    word_id INT NOT NULL,
    result ENUM('correct','incorrect','mastered') NOT NULL,
    mode ENUM('mcq','fill') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
