# Shoe Store E-commerce Project

## Prerequisites
- **Node.js**: You need to install Node.js from [nodejs.org](https://nodejs.org/).
- **MySQL**: You need a running MySQL server.

## Setup Instructions

1.  **Database Setup**:
    - Open your MySQL client (Workbench, Command Line, etc.).
    - Run the script located at `database/schema.sql` to create the database and tables.
    - Make sure your MySQL user matches the configuration in `.env` (default: user `root`, no password).

2.  **Install Dependencies**:
    - Open a terminal in this folder.
    - Run: `npm install`

3.  **Run the Server**:
    - Run: `npm start`
    - The server will start at `http://localhost:3000`.

## Features
- **Register/Login**: Create an account to shop.
- **Browse**: View shoes by brand.
- **Cart**: Add items and view your cart.
- **Checkout**: Place an order (simulated).

## Project Structure
- `server.js`: Backend entry point.
- `routes/`: API routes.
- `public/`: Frontend HTML/CSS/JS.
- `config/`: Database configuration.
