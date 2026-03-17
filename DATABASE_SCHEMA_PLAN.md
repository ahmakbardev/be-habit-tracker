# Database Schema Plan (Hybrid: MySQL & MongoDB)

Dokumen ini merinci pembagian data antara MySQL (Relational) dan MongoDB (Document-based) untuk aplikasi Self-Note Backend.

---

## 1. MySQL (Primary Database: `habit_app_mysql`)
Digunakan untuk manajemen user, autentikasi, dan struktur organisasi (hierarki) yang membutuhkan relasi antar tabel yang ketat.

### A. Core / User Module
- **users**: Data akun, email, password, profile (Bawaan Laravel).

### B. Notes Structure (Organization Only)
- **note_folders**: ID, UserID, Name, Icon, OrderIndex.
- **note_workspaces**: ID, FolderID, Name, Icon, OrderIndex.
*(Hanya strukturnya saja, isi Note ada di MongoDB)*

### C. Tasks Module (Full Relational)
- **task_folders**: ID, UserID, Name, Icon, OrderIndex.
- **task_projects**: ID, FolderID, Name, Description, Icon, Status, StartDate, EndDate, OrderIndex.
- **task_columns**: ID, ProjectID, Title, OrderIndex (Untuk Kanban Board).
- **tasks**: ID, ProjectID, ColumnID, Title, Priority, StartDate, DueDate, OrderIndex.
*(Task membutuhkan relasi yang kuat antara Folder -> Project -> Column -> Task)*

---

## 2. MongoDB (Secondary Database: `habit_app_mongo`)
Digunakan untuk data yang fleksibel, konten teks kaya (Rich Text), dan data log harian.

### A. Notes Content (`notes` collection)
Karena Note mendukung **Rich Text Editor (Tiptap/JSON)**, MongoDB lebih ideal untuk menyimpan skema yang fleksibel.
- **Fields**:
    - `_id`: ObjectId
    - `workspace_id`: UUID (Relasi ke MySQL `note_workspaces`)
    - `title`: String
    - `content`: JSON / HTML (Flexible structure)
    - `plain_text_preview`: String (Untuk Full-text search)
    - `highlight`: Boolean
    - `tags`: Array
    - `order_index`: Integer
    - `created_at / updated_at`: ISODate

### B. Habits Module (`habits` & `habit_logs` collections)
Habits memiliki jadwal (`schedules`) yang bersifat array/JSON dan log harian yang akan terus bertambah (high volume).
- **Collection: `habits`**
    - `_id`: ObjectId
    - `user_id`: UUID (Relasi ke MySQL `users`)
    - `name`: String
    - `icon_type`: String
    - `color`: String
    - `schedules`: Array (e.g., `["08:00", "12:00"]`)
    - `goal`: Integer
    - `created_at`: ISODate

- **Collection: `habit_completions`**
    - `_id`: ObjectId
    - `habit_id`: ObjectId (Relasi ke `habits`)
    - `user_id`: UUID
    - `date`: String (YYYY-MM-DD)
    - `time_slot`: String
    - `status`: Integer (0/1)

---

## 3. Summary of Connection Strategy

| Module | Component | Database | Reason |
| :--- | :--- | :--- | :--- |
| **Auth** | Users, Tokens | MySQL | Security & ACID Compliance |
| **Notes** | Folders/Workspaces | MySQL | Strict hierarchy |
| **Notes** | Note Content | MongoDB | Flexible JSON structure (Tiptap) |
| **Habits** | Habit Config & Logs | MongoDB | High frequency, Array-based schedules |
| **Tasks** | Projects, Columns, Tasks | MySQL | Complex relational & Kanban logic |

---

## 4. Implementation Notes (Laravel)

1. **Model MySQL**: Gunakan `use Illuminate\Database\Eloquent\Model;`
2. **Model MongoDB**: Gunakan `use MongoDB\Laravel\Eloquent\Model;` dan set `protected $connection = 'mongodb';`
3. **Cross-Database Relation**:
   - Dari MySQL ke Mongo: Simpan ID Mongo sebagai String di MySQL.
   - Dari Mongo ke MySQL: Simpan UUID MySQL sebagai field di Mongo.
   - Laravel-MongoDB mendukung `belongsTo` dan `hasMany` lintas database secara *out-of-the-box*.
