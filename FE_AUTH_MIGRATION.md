# Frontend Migration Guide — Auth & Sanctum Token

## Ringkasan

Semua endpoint API sekarang **dilindungi autentikasi**. Sebelumnya semua request bisa langsung hit tanpa header apapun. Sekarang setiap request harus membawa Bearer token di header `Authorization`.

---

## Perubahan Breaking

### Sebelumnya
```
GET /api/habits  → langsung dapat data, tanpa header
```

### Sekarang
```
GET /api/habits  → 401 Unauthenticated  ← jika tidak ada token
GET /api/habits  → 200 OK               ← jika ada token yang valid
```

---

## Endpoint Auth (Public — tidak perlu token)

### POST `/api/auth/register`
Buat akun baru.

**Request Body:**
```json
{
  "name": "Ahmad Akbar",
  "email": "user@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

**Response 201:**
```json
{
  "status": "success",
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "Ahmad Akbar",
      "email": "user@example.com",
      "created_at": "...",
      "updated_at": "..."
    },
    "token": "1|abcdefghij...",
    "token_type": "Bearer"
  }
}
```

**Response 422 (validasi gagal):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password confirmation does not match."]
  }
}
```

---

### POST `/api/auth/login`
Login dan dapat token.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "secret123"
}
```

**Response 200:**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Ahmad Akbar",
      "email": "user@example.com"
    },
    "token": "1|abcdefghij...",
    "token_type": "Bearer"
  }
}
```

**Response 401 (credentials salah):**
```json
{
  "status": "error",
  "message": "Invalid credentials"
}
```

---

## Endpoint Auth (Protected — perlu token)

### POST `/api/auth/logout`
Hapus token yang sedang aktif (logout dari device ini saja).

**Response 200:**
```json
{
  "status": "success",
  "message": "Logged out successfully"
}
```

---

### GET `/api/auth/me`
Ambil data user yang sedang login.

**Response 200:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "name": "Ahmad Akbar",
    "email": "user@example.com",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

---

## Cara Pakai Token di Setiap Request

Simpan token setelah login/register, lalu kirim di setiap request sebagai header:

```
Authorization: Bearer 1|abcdefghij...
```

### Contoh Fetch (Vanilla JS)
```js
const token = localStorage.getItem('token');

const res = await fetch('/api/habits', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
});
```

### Contoh Axios
```js
// Set sekali di setup/interceptor
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
axios.defaults.headers.common['Accept'] = 'application/json';

// Atau pakai interceptor
axios.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});
```

---

## Alur Auth yang Disarankan

```
App load
  └─ Ada token di storage?
       ├─ Ya  → GET /api/auth/me
       │         ├─ 200 → lanjut, render app
       │         └─ 401 → hapus token, redirect ke login
       └─ Tidak → redirect ke login

Login page
  └─ POST /api/auth/login
       ├─ 200 → simpan token, redirect ke app
       └─ 401 → tampilkan "Email atau password salah"

Logout
  └─ POST /api/auth/logout (dengan token)
       └─ Hapus token dari storage, redirect ke login
```

---

## Menyimpan Token

Gunakan `localStorage` atau `sessionStorage`. Contoh:

```js
// Setelah login/register
localStorage.setItem('token', data.token);

// Setiap request
const token = localStorage.getItem('token');

// Setelah logout
localStorage.removeItem('token');
```

> **Catatan:** Jika app menggunakan cookie-based session (SPA dengan domain sama), bisa pakai cookie auth Sanctum. Tapi untuk setup saat ini, Bearer token via localStorage lebih straightforward.

---

## Handling Error 401

Setiap response `401 Unauthenticated` artinya token tidak ada, expired, atau tidak valid. FE harus redirect ke halaman login.

### Contoh Axios Interceptor
```js
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);
```

---

## Daftar Semua Endpoint yang Sekarang Butuh Token

Semua endpoint di bawah ini **sebelumnya bisa diakses tanpa auth**, sekarang **wajib pakai Bearer token**:

### Notes
| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/api/notes` | List semua folder, workspace, note |
| GET | `/api/notes/search` | Search notes |
| GET | `/api/notes/{id}` | Detail note |
| POST | `/api/notes/folders` | Buat folder |
| PATCH | `/api/notes/folders/{id}` | Update folder |
| DELETE | `/api/notes/folders/{id}` | Hapus folder |
| POST | `/api/notes/workspaces` | Buat workspace |
| PATCH | `/api/notes/workspaces/{id}` | Update workspace |
| DELETE | `/api/notes/workspaces/{id}` | Hapus workspace |
| POST | `/api/notes` | Buat note |
| PATCH | `/api/notes/{id}` | Update note |
| POST | `/api/notes/{id}/duplicate` | Duplikat note |
| DELETE | `/api/notes/{id}` | Hapus note |
| POST | `/api/notes/media/upload` | Upload media |

### Habits
| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/api/habits` | List habits |
| GET | `/api/habits/completions` | List completions |
| GET | `/api/habits/stats` | Stats global |
| POST | `/api/habits` | Buat habit |
| PATCH | `/api/habits/{id}` | Update habit |
| POST | `/api/habits/toggle` | Toggle log |
| GET | `/api/habits/{id}/efficiency` | Efisiensi habit |
| DELETE | `/api/habits/{id}` | Hapus habit |

### Tasks
| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/api/tasks` | List folder & projects |
| POST | `/api/tasks/folders` | Buat folder |
| GET | `/api/tasks/projects/{id}` | Detail project |
| POST | `/api/tasks/projects` | Buat project |
| POST | `/api/tasks` | Buat task |
| PUT | `/api/tasks/reorder` | Reorder tasks |

### Push Subscriptions
| Method | Endpoint | Keterangan |
|--------|----------|------------|
| POST | `/api/push-subscriptions` | Daftar/update subscription |
| DELETE | `/api/push-subscriptions` | Hapus subscription |

---

## Catatan untuk Backend Deployment

> Ini bukan urusan FE, tapi FE perlu tahu agar tidak bingung kenapa data kosong:
>
> Sebelum app bisa dipakai, backend perlu menjalankan:
> ```bash
> php artisan migrate          # buat tabel personal_access_tokens
> php artisan db:seed --class=AssignDataToUserSeeder  # assign data lama ke user
> ```
> Data lama (notes/habits/tasks) sudah ter-assign ke akun `ahmakbar.dev@gmail.com`.
> Login dengan akun tersebut untuk akses data yang sudah ada.
