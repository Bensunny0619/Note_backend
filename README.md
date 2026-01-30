<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

# 📝 Note Backend – Powerful, Synchronized Note-Taking API

A robust, enterprise-grade backend API built with **Laravel 12** to power modern note-taking applications. Featuring real-time synchronization, advanced media attachments, and granular organizational tools.

---

## 🚀 Key Features

### 🔐 Authentication & Security
- **JWT-powered Auth**: Secure stateless authentication using `tymon/jwt-auth`.
- **User Management**: Comprehensive registration, login, and profile management.
- **Sanctum Integration**: Provided for flexible session/token handling.

### 📓 Advanced Note Management
- **Full CRUD**: Create, read, update, and delete notes effortlessly.
- **Organization**: Pin important notes, archive completed ones, and customize with vibrant colors.
- **Rich Media**: Support for **Images**, **Audio Recordings** (voice notes), and **Freehand Drawings**.
- **Checklists**: Integrated task lists within notes with toggle functionality.

### 🏷️ Categorization & Alerts
- **Dynamic Labels**: Tag notes for easy categorization and filtering.
- **Reminders**: Schedule alerts for specific notes to never miss a deadline.
- **Global Search**: High-performance search across titles and content.

### 🌐 Real-Time Collaboration
- **Laravel Reverb**: Built-in WebSocket server for instant state synchronization across all devices.
- **Note Sharing**: Share notes with other users via email with customizable permissions.
- **Broadcasting**: Real-time events for Note creation, updates, and deletions.

---

## 🛠️ Technical Stack

- **Framework**: [Laravel 12](https://laravel.com)
- **Language**: PHP 8.2+
- **Auth**: JWT (JSON Web Tokens)
- **Real-time**: [Laravel Reverb](https://laravel.com/docs/reverb)
- **Broadcasting**: Pusher (PHP Server SDK)
- **Media Handling**: Intervention Image
- **Database**: PostgreSQL / MySQL (Compatible)

---

## 📂 Project Structure

```bash
├── app/
│   ├── Http/Controllers/    # Application logic (Auth, Notes, Media)
│   ├── Models/             # Database schemas (Note, Checklist, Label, etc.)
│   └── Events/             # Real-time broadcasting events
├── database/
│   └── migrations/         # Database structural history
├── routes/
│   └── api.php             # Core API endpoints
└── storage/
    └── app/public/         # Uploaded media (Recordings, Drawings)
```

---

## ⚙️ Installation & Setup

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd note-backend
   ```

2. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Configure Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan jwt:secret
   ```

4. **Database Setup**:
   ```bash
   php artisan migrate
   ```

5. **Start the Servers**:
   ```bash
   # Start the API server
   php artisan serve
   
   # Start the Real-time Reverb server
   php artisan reverb:start
   ```

---

## 📡 API Endpoints (Quick Reference)

### Authentication
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| POST | `/api/auth/register` | Create a new account |
| POST | `/api/auth/login` | Obtain a JWT token |
| GET | `/api/auth/me` | Get authenticated user info |

### Notes
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| GET | `/api/notes` | List notes (Searchable/Filterable) |
| POST | `/api/notes` | Create a new note |
| PUT | `/api/notes/{id}` | Update note details |
| DELETE | `/api/notes/{id}`| Remove a note |
| PUT | `/api/notes/{id}/pin` | Pin/Unpin a note |

*(Refer to `routes/api.php` for complete documentation on Checklists, Labels, and Sharing.)*

---

## 📄 License

The Note Backend is open-source software licensed under the [MIT license](LICENSE).
