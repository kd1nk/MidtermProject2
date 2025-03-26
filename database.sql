-- Create the 'authors' table
CREATE TABLE authors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author VARCHAR(255) NOT NULL
);

-- Create the 'categories' table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(255) NOT NULL
);

-- Create the 'quotes' table
CREATE TABLE quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote TEXT NOT NULL,
    author_id INT NOT NULL,
    category_id INT NOT NULL,
    FOREIGN KEY (author_id) REFERENCES authors(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Insert data into 'authors'
INSERT INTO authors (author) VALUES
('Albert Einstein'),
('Maya Angelou'),
('Nelson Mandela'),
('Jane Austen'),
('William Shakespeare'),
('Mark Twain');

-- Insert data into 'categories'
INSERT INTO categories (category) VALUES
('Inspirational'),
('Motivational'),
('Wisdom'),
('Love'),
('Humor'),
('Life');

-- Insert data into 'quotes'
INSERT INTO quotes (quote, author_id, category_id) VALUES
('Imagination is more important than knowledge.', 1, 3),
('You will face many defeats in life, but never let yourself be defeated.', 2, 1),
('The greatest glory in living lies not in never falling, but in rising every time we fall.', 3, 2),
('There is no charm equal to tenderness of heart.', 4, 4),
('To be, or not to be, that is the question.', 5, 3),
('The fear of death follows from the fear of life. A man who lives fully is prepared to die at any time.', 6, 6),
('Success is not final, failure is not fatal: It is the courage to continue that counts.', 3, 2),
('We must let go of the life we have planned, so as to have the life that is waiting for us.', 2, 6),
('Doubt thou the stars are fire; Doubt that the sun doth move; Doubt truth to be a liar; But never doubt I love.', 5, 4),
('The only way to do great work is to love what you do.', 1, 1),
('Life is what happens while you are busy making other plans.', 6, 6),
('I have learned over the years that when one\'s mind is made up, this diminishes fear.', 3, 2),
('Love looks not with the eyes, but with the mind, And therefore is winged Cupid painted blind.', 5, 4),
('The best and most beautiful things in the world cannot be seen or even touched - they must be felt with the heart.', 4, 4),
('The future belongs to those who believe in the beauty of their dreams.', 2, 1),
('A witty woman is a treasure.', 4, 5),
('The greatest wealth is to live content with little.', 6, 3),
('What\'s in a name? that which we call a rose By any other name would smell as sweet.', 5, 4),
('The only impossible journey is the one you never begin.', 2, 2),
('The most wasted of all days is one without laughter.', 6, 5),
('The purpose of our lives is to be happy.', 1, 6),
('The best revenge is massive success.', 3, 2),
('Don\'t cry because it\'s over, smile because it happened.', 6, 6),
('The strongest people are not those who show strength before us, but those who win battles we know nothing about.', 2, 1),
('To thine own self be true.', 5, 3),
('Example Quote 1 Author 5 Category 4', 5, 4),
('Example Quote 2 Author 5 Category 4', 5, 4);

-- verify data
SELECT * FROM quotes WHERE id = 10;
SELECT * FROM authors WHERE id = 5;
SELECT * FROM categories WHERE id = 4;
SELECT * FROM quotes WHERE author_id = 5 AND category_id = 4;
