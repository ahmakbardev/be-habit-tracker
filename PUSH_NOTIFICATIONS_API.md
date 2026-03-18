# Dokumentasi API: Web Push Notification (Staging)

Dokumentasi ini menjelaskan cara kerja fitur **Push Notification** di level sistem (OS) yang dikirim oleh Laravel Backend ke aplikasi PWA (Frontend).

---

## 1. Identitas Server (VAPID Public Key)
Frontend (FE) **WAJIB** menggunakan kunci ini untuk melakukan registrasi/meminta izin notifikasi ke browser user.

**VAPID PUBLIC KEY:**
```text
BEStur5-9xp43ohkryv1_BETJfnUNJoCCvc-bFL6RJV3x4LytsL4DYxNf7ocDxtW9P-aZC5jk4JJHxDcng-6ybk
```

---

## 2. API Endpoints (Kebutuhan Frontend)

### A. Simpan/Update Subscription
Gunakan endpoint ini setelah user memberikan izin (`Notification.requestPermission()`) dan FE mendapatkan objek `PushSubscription` dari browser.

*   **URL**: `/api/push-subscriptions`
*   **Method**: `POST`
*   **Request Body (JSON)**:
    ```json
    {
        "endpoint": "https://fcm.googleapis.com/fcm/send/d5z...",
        "keys": {
            "auth": "P256dh_auth_key_string",
            "p256dh": "P256dh_public_key_string"
        }
    }
    ```
*   **Response**: `200 OK` jika berhasil disimpan.

### B. Hapus Subscription (Unsubscribe/Logout)
Gunakan ini saat user melakukan logout atau menonaktifkan notifikasi secara manual di pengaturan aplikasi.

*   **URL**: `/api/push-subscriptions`
*   **Method**: `DELETE`
*   **Request Body (JSON)**:
    ```json
    {
        "endpoint": "https://fcm.googleapis.com/fcm/send/d5z..."
    }
    ```

---

## 3. Struktur Payload Notifikasi
Saat Backend mengirim notifikasi (otomatis 10 menit sebelum Todo), Service Worker di FE akan menerima payload dengan struktur berikut:

```json
{
    "title": "Ingat Todo Kamu!",
    "body": "Task 'Nama Task' akan dimulai dalam 10 menit.",
    "icon": "/icon-192x192.png",
    "data": {
        "id": "UUID-TASK-DI-SINI"
    },
    "actions": [
        {
            "action": "view_task",
            "title": "Buka Aplikasi"
        }
    ]
}
```

**Tips untuk Frontend:**
- Pastikan Service Worker menangani event `push` dan memanggil `self.registration.showNotification()`.
- Gunakan `data.id` untuk melakukan redirect (deep linking) saat notifikasi diklik.

---

## 4. Cara Kerja & Jadwal (Backend Logic)
1.  **Otomatisasi**: Backend menjalankan pengecekan setiap **1 menit sekali**.
2.  **Kriteria**: Backend mencari task yang:
    - Statusnya belum `completed`.
    - Memiliki `due_at` (waktu jatuh tempo).
    - Berada dalam rentang **5 sampai 11 menit** dari waktu sekarang.
3.  **Pengiriman**: Notifikasi akan dikirimkan ke semua browser yang sudah terdaftar untuk user tersebut (PC, HP Android, dll).

---

## 5. Catatan Penting untuk Staging
Saat ini di server staging, API masih menggunakan **User Pertama** di database sebagai identitas pengirim. Jika sistem Auth (Login) sudah aktif sepenuhnya, Frontend cukup menambahkan header `Authorization: Bearer <token>` pada setiap request di atas.

---
**Status Progres: ✅ API Ready | ✅ Scheduler Active | ✅ VAPID Keys Generated**
