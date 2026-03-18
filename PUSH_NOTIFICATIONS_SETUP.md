# Panduan Implementasi Web Push Notification (Laravel & PWA)

Dokumen ini menjelaskan apa yang perlu disiapkan di sisi **Laravel Backend** agar aplikasi PWA (React) dapat memunculkan notifikasi di level sistem (Windows/Android/iOS) untuk pengingat Todo 5-10 menit sebelum waktunya.

## 1. Package yang Dibutuhkan
Gunakan package standar industri untuk WebPush di Laravel:
```bash
composer require laravel-notification-channels/webpush
```

## 2. Database & Model
Anda perlu menyimpan "Subscription" (alamat unik browser) milik user ke database.
- Jalankan migrasi yang disediakan package: `php artisan migrate` (ini akan membuat tabel `push_subscriptions`).
- Pastikan model `User` menggunakan trait `HasPushSubscriptions`:
  ```php
  use NotificationChannels\WebPush\HasPushSubscriptions;

  class User extends Authenticatable {
      use HasPushSubscriptions;
  }
  ```

## 3. Konfigurasi VAPID Keys
VAPID Keys adalah kunci keamanan agar hanya server Anda yang bisa mengirim notifikasi ke browser user.
- Generate keys: `php artisan webpush:vapid`
- Ini akan menambahkan `VAPID_PUBLIC_KEY` dan `VAPID_PRIVATE_KEY` di file `.env`.
- **Penting:** Bagikan `VAPID_PUBLIC_KEY` ke tim Frontend (React) untuk proses registrasi di browser.

## 4. API Endpoints (Kebutuhan Frontend)
Frontend butuh 2 endpoint utama:
1.  **POST `/api/push-subscriptions`**: Untuk menyimpan/update subscription saat user mengizinkan notifikasi di browser.
2.  **DELETE `/api/push-subscriptions`**: Untuk menghapus subscription saat user logout.

Contoh Controller logic:
```php
public function updateSubscription(Request $request) {
    $this->validate($request, [
        'endpoint' => 'required',
        'keys.auth' => 'required',
        'keys.p256dh' => 'required'
    ]);
    $endpoint = $request->endpoint;
    $key = $request->keys['p256dh'];
    $token = $request->keys['auth'];
    $user = $request->user();
    $user->updatePushSubscription($endpoint, $key, $token);
    
    return response()->json(['success' => true]);
}
```

## 5. Logic Pengingat (Reminder 5-10 Menit)
Untuk mengirim notifikasi otomatis sebelum waktu Todo, gunakan **Laravel Scheduler**.

### A. Buat Notification Class
```php
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class TodoReminderNotification extends Notification {
    public function via($notifiable) {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification) {
        return (new WebPushMessage)
            ->title('Ingat Todo Kamu!')
            ->icon('/icon-192x192.png')
            ->body("Todo '{$this->todo->title}' akan dimulai dalam 10 menit.")
            ->action('Buka Aplikasi', 'view_todo')
            ->data(['id' => $this->todo->id]);
    }
}
```

### B. Jadwalkan di `routes/console.php` atau `Kernel.php`
Buat command yang berjalan setiap menit untuk mengecek Todo yang akan datang:
```php
// Contoh logic sederhana
$schedule->call(function () {
    $todos = Todo::where('status', 'pending')
        ->whereBetween('due_at', [now()->addMinutes(5), now()->addMinutes(11)])
        ->get();

    foreach ($todos as $todo) {
        $todo->user->notify(new TodoReminderNotification($todo));
    }
})->everyMinute();
```

## 6. Payload Data
Pastikan payload notifikasi menyertakan data minimal:
- **Title**: Judul Notifikasi.
- **Body**: Isi pesan.
- **Icon**: Path ke icon (misal `/icon-192x192.png`).
- **Data**: Metadata (seperti ID Todo) agar saat diklik, PWA bisa langsung membuka Todo tersebut.

### Apa yang sudah dilakukan:
1.  **Model User Updated**: Trait `HasPushSubscriptions` sudah ditambahkan. ✅
2.  **API Endpoints Ready**: `POST /api/push-subscriptions` dan `DELETE /api/push-subscriptions` sudah bisa digunakan. ✅
3.  **Notification Class Ready**: `TodoReminderNotification` sudah dibuat. ✅
4.  **Scheduler Configured**: Pengecekan task setiap menit sudah ada di `routes/console.php`. ✅

### Apa yang harus kamu lakukan selanjutnya:
1. **Fix VAPID Key**: Jalankan `export OPENSSL_CONF="path/ke/openssl.cnf"` lalu `php artisan webpush:vapid`.
2. **Migrate**: Jalankan `php artisan migrate` untuk membuat tabel `push_subscriptions`.
3. **Share Public Key**: Berikan nilai `VAPID_PUBLIC_KEY` dari `.env` ke tim Frontend.

