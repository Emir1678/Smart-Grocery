 Market & Inventory Tracking System (Smart Grocery)
This project is a Full-Stack Web Application developed as part of the Computer Engineering curriculum at Ankara Bilim University. It is designed to track product prices across different markets, manage inventory, and handle user-based shopping lists.

  Key Features
Dynamic Product Management (CRUD): A dedicated panel for market administrators to add, delete, and update products and prices.

Price Comparison: Real-time listing and comparison of the same products across various retailers (e.g., BİM, Şok, A101).

Smart Cart System: Ability to add products to the cart with automatic stock deduction upon "purchase" (handled via asynchronous requests).

User Authentication: Secure Login/Register system with session management and user role differentiation (Admin/Customer).

Database Management: Optimized queries with a relational database (MySQL) architecture.

   Tech Stack
Backend: PHP 8.x

Database: MySQL (Relational schema design)

Frontend: HTML5, CSS3, JavaScript (Fetch API for asynchronous operations), Bootstrap

Server Environment: XAMPP / Apache

   Project Structure & Architecture
/admin: Interface for market and inventory management.

/auth: User session management (Login/Sign-up).

/includes: Database connection configuration (db.php) and helper functions.

/assets: Project styling (CSS) and interaction (JS) files.

proje_db1.sql: SQL dump containing database tables and sample data.

   Installation & Setup
Ensure you have XAMPP or a similar local server environment installed.

Clone or copy the project files into the C:/xampp/htdocs/proje_db1 directory.

Create a new database named proje_db1 via phpMyAdmin.

Import the .sql file located in the root directory into your new database.

Navigate to localhost/proje_db1 in your browser to run the application.
