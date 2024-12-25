CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    balance DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('deposit', 'withdraw') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE bets_2d (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    time ENUM('morning', 'evening') NOT NULL,
    type ENUM('straight', 'reverse', 'twin', 'power', 'round') NOT NULL,
    numbers VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'won', 'lost') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE bets_3d (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('straight', 'permutation', 'twin', 'front2', 'back2') NOT NULL,
    numbers VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'won', 'lost') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE winning_numbers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    morning_2d VARCHAR(2),
    evening_2d VARCHAR(2),
    three_d VARCHAR(3),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
); 