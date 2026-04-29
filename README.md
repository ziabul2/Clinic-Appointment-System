# 🏥 Clinic Appointment System

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%207.4-blue.svg)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-%3E%3D%205.7-orange.svg)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple.svg)](https://getbootstrap.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

![Banner](assets/banner.png)

A comprehensive, web-based solution designed to streamline clinic operations. This system manages patients, doctors, and appointment scheduling with a focus on ease of use and security.

---

## ✨ Key Features

- **👤 Patient Management**: Seamlessly add, update, and track patient records and medical history.
- **👨‍⚕️ Doctor Portal**: Manage specialist profiles, availability, and dedicated appointment views.
- **📅 Smart Scheduling**: Real-time appointment booking with automated availability checking.
- **💬 Staff Messenger**: Premium, real-time messaging suite with:
    - File sharing (Images, PDFs, Text).
    - Chat permissions/request system.
    - Professional 3-level read receipts (Sent, Received, Seen).
    - Glowing real-time presence indicators.
- **🔒 Secure Auth**: Multi-role authentication (Admin, Doctor, Receptionist) with CSRF protection and secure session management.
- **📊 Session Audit**: Detailed session history tracking with auto-logout and activity monitoring.
- **📱 Premium UI**: Edge-to-edge modern design, dark mode support, and smooth animations.

---

## 🛠️ Tech Stack

- **Backend**: PHP 7.4+ (Vanilla PHP with PDO)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5
- **Icons**: Font Awesome 6
- **Server**: Apache / Nginx

---

## 📂 Project Structure

```text
clinicApp/
├── assets/             # CSS, JS, and Images
│   ├── css/            # Global and component-specific styles
│   ├── js/             # UI logic and Messenger (chat.js)
│   └── images/         # UI assets and logos
├── config/             # Database and system configuration
├── includes/           # Shared components (Header, Footer, Functions)
├── logs/               # System activity and error logs
├── pages/              # Main application pages
│   ├── messenger.php   # Premium Messenger UI
│   ├── dashboard.php   # Main operational hub
│   └── ...             # Feature-specific pages
├── private/            # Secure JSON data and internal settings
├── sqls_DB/            # Database schema exports
├── uploads/            # User-uploaded files (Profiles, Chat attachments)
├── chat_process.php    # Real-time Messaging API
└── process.php         # Central system logic and auth handler
```

---

## 🚀 Installation Guide

1.  **Clone the Repository**
    ```bash
    git clone https://github.com/ziabul2/Clinic-Appointment-System.git
    ```

2.  **Database Setup**
    - Create a database named `clinic_management`.
    - Import the schema from `sqls_DB/clinic_management.sql`.

3.  **Configuration**
    - Navigate to `config/database.php`.
    - Update your database credentials:
    ```php
    $host = "localhost";
    $db_name = "clinic_management";
    $username = "root";
    $password = "";
    ```

4.  **Run Locally**
    - Move the project to your local server (e.g., `htdocs` for XAMPP).
    - Access via `http://localhost/clinicapp`.

---

## 🔑 Default Credentials

| Role | Username | Password |
| :--- | :--- | :--- |
| **Admin** | `admin` | `password` |
| **Receptionist** | `reception` | `password` |

> [!WARNING]
> Please change default passwords immediately after your first login for security.

---

## 🤝 Contributing

Contributions are welcome! Feel free to fork this repository and submit pull requests.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

<p align="center">Made with ❤️ by <b>Ziabul Islam</b></p>