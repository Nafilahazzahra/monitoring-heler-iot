MQTT local setup untuk Monitoring Mesin Heler

1. Jalankan broker lokal:
   npm run broker
   Broker akan listen ke 0.0.0.0:1883 agar bisa diakses dari ESP32 di jaringan Wi-Fi yang sama.

2. Jalankan subscriber Laravel:
   php artisan mqtt:listen

3. Jalankan web Laravel:
   php artisan serve

4. Untuk ESP32, isi MQTT_HOST di sketch dengan IP Wi-Fi laptop:
   10.74.228.213

Test publish manual:
   npm run publish:test

Catatan:
- ESP32 dan laptop harus berada di Wi-Fi yang sama.
- Jika Windows Firewall meminta izin untuk Node.js pada port 1883, pilih Allow.
- Topic default yang dipakai: heler/sensor
