# Module Status: Notes (Hybrid System)

Modul ini menggunakan sistem Hybrid Database: **MySQL** untuk struktur hierarki (Folder & Workspace) dan **MongoDB** untuk konten dokumen yang fleksibel.

---

## 🟢 Current State: Basic CRUD Ready
- **MySQL Tables**: `note_folders`, `note_workspaces` (UUID Primary Keys).
- **MongoDB Collection**: `notes` (Tiptap JSON compatible).
- **Relationship**: Berhasil menghubungkan relasi lintas-database (MySQL ↔ MongoDB).
- **Seeder**: `NoteSeeder` siap digunakan untuk testing.

---

## 🛠️ API Documentation (Base URL: `/api/notes`)

| Method | Endpoint | Request Body (Contoh) | Deskripsi | Target DB |
| :--- | :--- | :--- | :--- | :--- |
| **GET** | `/` | - | Ambil semua Folder -> Workspace -> Notes | MySQL + Mongo |
| **GET** | `/{id}` | - | Ambil detail satu Note | MongoDB |
| **POST** | `/folders` | `{"name": "Kerja"}` | Tambah Folder baru | MySQL |
| **POST** | `/workspaces` | `{"folder_id": "UUID", "name": "Project A"}` | Tambah Workspace baru | MySQL |
| **PATCH** | `/folders/{id}` | `{"name": "Baru"}` | Update Folder (Rename/Icon) | MySQL |
| **PATCH** | `/workspaces/{id}` | `{"name": "Baru"}` | Update Workspace (Rename/Icon) | MySQL |
| **POST** | `/` | `{"workspace_id": "UUID", ...}` | Tambah Note baru | MongoDB |
| **POST** | `/media/upload` | `file` (Multipart) | Upload Gambar ke S3 Proxy | External |
| **PATCH** | `/{id}` | `{"title": "Judul Baru"}` | Update isi/judul Note | MongoDB |
| **POST** | `/{id}/duplicate` | - | Duplikasi Note | MongoDB |
| **DELETE** | `/folders/{id}` | - | Hapus Folder (Soft Delete) | MySQL |
| **DELETE** | `/workspaces/{id}` | - | Hapus Workspace (Soft Delete) | MySQL |
| **DELETE** | `/{id}` | - | Hapus Note (Soft Delete) | MongoDB |

---

## 🖼️ Media Uploader Integration
Backend bertindak sebagai proxy ke `https://s3.ahmakbar.space`.
- **Endpoint**: `POST /api/media/upload`
- **Format**: `multipart/form-data`
- **Field**: `file` (Image/Binary)
- **Response**: Mengembalikan `url` absolut untuk langsung dipakai di tag `<img>`.

---

## 📂 Database Schema Details

### MySQL (`habit_app_mysql`)
1. **`note_folders`**: Menyimpan kategori utama (e.g., Work, Personal).
2. **`note_workspaces`**: Menyimpan sub-kategori (e.g., Project A, Journal).

### MongoDB Atlas (`habit_app_mongo`)
1. **`notes`**: Koleksi dokumen.
   - `workspace_id`: Foreign Key ke MySQL.
   - `content`: Data JSON fleksibel (untuk Rich Text).
   - `plain_text_preview`: Teks murni untuk pencarian.

---

## 📝 TODO / Next Steps
- [ ] **Auth Integration**: Hubungkan `user_id` dengan sistem login (Sanctum).
- [x] **Soft Delete**: Implementasi penghapusan sementara pada Folder & Workspace.
- [x] **Search Engine**: Implementasi pencarian kata kunci di dalam konten MongoDB.
- [x] **Note Duplication**: Fitur untuk menggandakan catatan yang sudah ada.
