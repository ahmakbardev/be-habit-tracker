# Self-Note Backend - Module Status & Roadmap

Dokumen ini mencatat progress implementasi backend, integrasi database (MySQL & MongoDB), dan daftar API yang siap digunakan.

---

## 1. Module: Notes (Hybrid System)
**Status:** 🟢 **Selesai Dasar & Lanjutan (Search, Duplicate, Soft Delete Ready)**

### Current State:
- **Database MySQL**: Tabel `note_folders` dan `note_workspaces` (UUID) sudah aktif dengan support **Soft Delete**.
- **Database MongoDB**: Koleksi `notes` sudah mendukung **Soft Delete**.
- **Features**:
    - CRUD Folder, Workspace, & Note.
    - **Search Engine**: Full-text search (regex) pada `title` dan `content`.
    - **Duplicate Note**: Kloning dokumen sekali klik.
    - **Media Integration**: Upload image ke S3-like media platform (`https://s3.ahmakbar.space`).

### API Endpoints (Base URL: `/api/notes`):
| Method | Endpoint | Description | DB Target |
| :--- | :--- | :--- | :--- |
| **GET** | `/` | Get all Folders -> Workspaces -> Notes | MySQL + Mongo |
| **GET** | `/search?q=...` | Search notes by title or content | MongoDB |
| **POST** | `/folders` | Create new Folder | MySQL |
| **POST** | `/workspaces` | Create new Workspace | MySQL |
| **POST** | `/` | Create new Note content | MongoDB |
| **POST** | `/{id}/duplicate` | Duplicate a Note | MongoDB |
| **PATCH** | `/{id}` | Update Note content | MongoDB |
| **DELETE** | `/{id}` | Soft Delete a Note | MongoDB |

---

## 2. Module: Habits (NoSQL Focus)
**Status:** 🟢 **Selesai (CRUD & Efficiency Ready)**

### Current State:
- **Model**: `Habit` dan `HabitCompletion` (MongoDB) sudah diimplementasi.
- **Features**:
    - CRUD Habit.
    - **Toggle Log**: Log harian untuk setiap habit.
    - **Efficiency**: Perhitungan persentase performa 30 hari terakhir.

### API Endpoints (Base URL: `/api/habits`):
| Method | Endpoint | Description | DB Target |
| :--- | :--- | :--- | :--- |
| **GET** | `/?date=YYYY-MM-DD` | List habit & status pada tanggal tertentu | MongoDB |
| **POST** | `/` | Create new Habit | MongoDB |
| **POST** | `/toggle` | Toggle log harian (Check/Uncheck) | MongoDB |
| **GET** | `/{id}/efficiency` | Get habit efficiency % | MongoDB |

---

## 3. Module: Tasks (Relational Focus)
**Status:** 🟢 **Selesai (Kanban & Structure Ready)**

### Current State:
- **Model**: `TaskFolder`, `TaskProject`, `TaskColumn`, `Task` (MySQL) sudah diimplementasi.
- **Features**:
    - **Auto-Initialization**: Project baru otomatis punya kolom "To Do", "In Progress", "Done".
    - **Kanban Logic**: Support reorder task antar kolom.

### API Endpoints (Base URL: `/api/tasks`):
| Method | Endpoint | Description | DB Target |
| :--- | :--- | :--- | :--- |
| **GET** | `/` | List semua folder dan project | MySQL |
| **GET** | `/projects/{id}` | Get detail project (Kanban structure) | MySQL |
| **POST** | `/projects` | Create project & default columns | MySQL |
| **POST** | `/` | Create new Task | MySQL |
| **PUT** | `/reorder` | Batch update order index/column | MySQL |

---

## 4. Media & Global Features
**Status:** 🟡 **In Progress**

- [x] **Media Upload**: Integrasi proxy ke `https://s3.ahmakbar.space/api/files/upload`.
- [ ] **Authentication**: Implementasi Sanctum/JWT (Next Step).
- [ ] **Standardized Error Handling**: Next Step.
