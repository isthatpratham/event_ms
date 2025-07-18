# 🎉 College Event Management System

[![PHP](https://img.shields.io/badge/Backend-PHP-blue)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/Database-MySQL-orange)](https://www.mysql.com/)
[![Status](https://img.shields.io/badge/Status-Working-brightgreen)]()
[![License: Unlicensed](https://img.shields.io/badge/License-Educational-lightgrey)]()
[![GitHub last commit](https://img.shields.io/github/last-commit/isthatpratham/event_ms)](https://github.com/isthatpratham/event_ms)

A simple web application built in PHP and MySQL for managing college-level events. It allows students to register and participate, organizers to manage events, and admins to control the overall system.

---

## 📌 Features

### 👨‍🎓 Student
- Register and log in
- View upcoming events
- Apply to participate in events

### 🧑‍💼 Organizer
- Register and log in
- Add and manage events
- View event participants

### 🛠️ Admin
- Admin login/logout
- Manage:
  - Events
  - Organizers
  - Users
  - Venues
  - Teams

---

## 🗃️ Tech Stack

- **Frontend**: HTML, CSS
- **Backend**: PHP
- **Database**: MySQL

---

## 🚀 Getting Started

### ⚙️ Prerequisites

- PHP >= 7.0
- MySQL/MariaDB
- Apache server (XAMPP/WAMP recommended)

---

### 📥 Installation

1. **Clone this repo** or download ZIP:
   ```bash
   git clone https://github.com/isthatpratham/event_ms.git
2. **Move the project folder** to your web server directory (e.g., `htdocs` if using XAMPP):

   - If you're using XAMPP on Windows:
     ```
     Move the "college event" folder into: C:\xampp\htdocs\
     ```
   - Or on Linux/macOS with a terminal:
     ```bash
     mv "college event" /opt/lampp/htdocs/
     ```

3. **Import the database**:
   - Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
   - Create a new database (e.g., `event_db`)
   - Click **Import**, then select the file:  
     `college event/eveflow_db (1).sql`

4. **Configure the database connection**:
   - Open the file:
     ```
     college event/adminpanel/config.php
     ```
   - Edit the credentials to match your MySQL setup:
     ```php
     $host = "localhost";
     $username = "root";        // default for XAMPP
     $password = "";            // leave empty unless you've set one
     $database = "event_db";    // name of the DB you created
     ```

5. **Run the app**:
   - Open your browser and go to:
     ```
     http://localhost/college%20event/
     ```
   - You should now see the home/login page.



