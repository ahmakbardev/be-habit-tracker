# Module Status: Habits (MongoDB-based)

Modul ini menggunakan **MongoDB** untuk menyimpan data kebiasaan (Habits) dan riwayat penyelesaiannya (Habit Completions) karena fleksibilitas struktur data (seperti `schedules`).

---

## 🟢 Current State: Feature Complete
- **MongoDB Collections**: `habits`, `habit_completions`.
- **Relationship**: `Habit` has many `HabitCompletion`.
- **Features**:
  - **CRUD Habits**: Create, List, Update, and Archive habits.
  - **Archiving Logic**: Menghentikan habit dari daftar aktif tanpa menghapus riwayat log masa lalu.
  - **Toggle Log**: Toggle completion for specific date/time_slot with future, creation-date, and archive validation.
  - **Range Fetching**: Get all completions for a specific date range (Weekly/Monthly view).
  - **Advanced Stats**: Global efficiency calculation and 7-day activity chart (Smart calculating based on active days only).

---

## 🛠️ API Documentation (Base URL: `/api/habits`)

| Method | Endpoint | Request Body (Contoh) | Deskripsi | Status |
| :--- | :--- | :--- | :--- | :--- |
| **GET** | `/` | - | Ambil semua kebiasaan aktif hari ini | ✅ Ready |
| **GET** | `/completions` | `?start_date=...&end_date=...` | Ambil log dalam rentang waktu | ✅ Ready |
| **GET** | `/stats` | - | Statistik efisiensi & chart 7 hari terakhir | ✅ Ready |
| **POST** | `/` | `{"name": "Minum", ...}` | Tambah kebiasaan baru | ✅ Ready |
| **PATCH** | `/{id}` | `{"name": "Baru"}` | Update konfigurasi kebiasaan | ✅ Ready |
| **POST** | `/toggle` | `{"habit_id": "...", ...}` | Toggle status penyelesaian | ✅ Ready |
| **GET** | `/{id}/efficiency` | - | Efisiensi spesifik satu habit | ✅ Ready |
| **DELETE** | `/{id}` | - | Archive kebiasaan (Log masa lalu tetap aman) | ✅ Ready |

---

## 📂 Database Schema Details

### MongoDB Atlas (`habit_app_mongo`)
1. **`habits`**: Koleksi konfigurasi kebiasaan.
   - `user_id`: Link ke MySQL User.
   - `name`: Nama habit.
   - `icon_type`: Identifier icon Lucide.
   - `color`: Hex code atau Tailwind class.
   - `schedules`: Array waktu (e.g., `["08:00", "12:00"]`).
   - `goal`: Target harian (auto-calculated from schedules).
   - `archived_at`: Timestamp kapan habit dihentikan (null jika masih aktif).
2. **`habit_completions`**: Log penyelesaian.
   - `habit_id`: Link ke `habits`.
   - `date`: Format `YYYY-MM-DD`.
   - `time_slot`: Waktu spesifik atau "daily".
   - `status`: 1 (selesai).

---

## 📝 TODO / Next Steps
- [ ] **Streak Tracking**: Hitung jumlah hari berturut-turut habit diselesaikan.
- [ ] **Auth Integration**: Hubungkan dengan User ID dari Sanctum/Session.
