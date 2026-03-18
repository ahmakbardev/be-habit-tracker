# Issue Report: WebPush Notification Subscription Missing

## 📝 Deskripsi Masalah
Sistem **Habit Scheduler** pada sisi Backend telah berhasil mendeteksi jadwal dan memicu pengiriman notifikasi (sudah terverifikasi di logs). Namun, notifikasi tidak sampai ke perangkat user karena tabel `push_subscriptions` di database masih **kosong**.

Tanpa data subscription (endpoint & keys) dari browser, Backend tidak memiliki "alamat tujuan" untuk mengirimkan pesan WebPush.

---

## 🛠️ Detail Teknis (Backend Status)
1. **Timezone:** Sudah disinkronkan ke `Asia/Jakarta` (WIB).
2. **Scheduler:** Berjalan setiap menit dan mencari habit yang jadwalnya cocok dengan waktu saat ini (toleransi ±1 menit).
3. **Connection:** Koneksi hybrid (MySQL & MongoDB) sudah stabil.
4. **Endpoint API:** `POST /api/push-subscriptions` (Gunakan endpoint ini untuk mendaftarkan subscription).

---

## 🚀 Panduan Perbaikan untuk Frontend (FE)

Tim Frontend perlu memastikan alur registrasi Service Worker dan Push Subscription berjalan sebagai berikut:

### 1. Request Permission & Subscribe
Frontend harus meminta izin notifikasi dan mendapatkan objek subscription dari browser.
```javascript
// Contoh Logika di Frontend
const registration = await navigator.serviceWorker.ready;
const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array('VAPID_PUBLIC_KEY_ANDA') // Ambil dari .env Backend
});
```

### 2. Kirim Data ke API
Setelah mendapatkan objek `subscription`, Frontend **wajib** mengirimkannya ke Backend agar tersimpan di database. 

> **Catatan Penting:** Karena sistem saat ini belum menggunakan Authentication (Login), Frontend **TIDAK PERLU** mengirimkan header `Authorization`. Backend akan otomatis menyimpan data ini untuk User pertama.

**Endpoint:** `POST /api/push-subscriptions`  
**Payload (JSON):**
```json
{
    "endpoint": "https://fcm.googleapis.com/fcm/send/...",
    "keys": {
        "auth": "auth_secret_dari_browser", // Ini adalah 'auth' dari subscription.toJSON().keys
        "p256dh": "p256dh_key_dari_browser"
    },
    "content_encoding": "aesgcm" 
}
```
*Pastikan `auth` dan `p256dh` diambil langsung dari objek `PushSubscription.toJSON().keys`.*

### 3. VAPID Key Alignment
Pastikan `applicationServerKey` yang digunakan Frontend **sama persis** dengan `VAPID_PUBLIC_KEY` yang ada di file `.env` server Backend.

---

## 🔍 Cara Verifikasi (Testing)
1. Buka aplikasi di browser (pastikan Service Worker aktif).
2. Lakukan proses "Subscribe" atau "Izinkan Notifikasi".
3. Cek tabel `push_subscriptions` di database (MySQL). 
   - **Gagal:** Jika tabel masih kosong setelah proses di atas.
   - **Berhasil:** Jika muncul minimal 1 baris data baru dengan `subscribable_id` milik user yang bersangkutan.
4. Jika sudah ada data di tabel, buat Habit baru dengan jadwal 2-3 menit dari sekarang, lalu tunggu notifikasi muncul.

---
*Dibuat oleh Sistem Backend - 19 Maret 2026*
