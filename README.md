# Dashboard AMCS Replika BRIN + AI 🌿

Sistem pemantauan cerdas (*Autonomous Monitoring Control System*) untuk Greenhouse/Smart Garden. Proyek ini merupakan replika dan pengembangan dari sistem BRIN yang mengintegrasikan IoT (Internet of Things), Dashboard Laravel, kecerdasan buatan (Ollama Local LLM), dan Computer Vision (YOLOv8) untuk deteksi penyakit tanaman secara real-time.

## Fitur Utama

- **Real-Time IoT Monitoring**: Memantau pH, TDS, Suhu Air, Suhu Udara, Kelembaban, Intensitas Cahaya, dan Kekeruhan Air (Turbidity).
- **Ollama AI Integration**: Analisis kondisi lingkungan secara otomatis berbasis Local LLM (Llama 3) yang memberikan rekomendasi tindakan perbaikan berdasarkan data sensor.
- **Plant Disease Scanner (Computer Vision)**:
  - Menggunakan **YOLOv8** dengan GPU acceleration.
  - Arsitektur Edge-Cloud: **Raspberry Pi** + Webcam sebagai Edge Device untuk *video streaming*, **Laptop (GPU)** sebagai server inferensi.
  - Fitur Auto-Scan setiap 5 menit & Capture Manual langsung dari Dashboard.
  - Riwayat deteksi tersimpan secara lokal dan dapat dilihat ulang melalui Dashboard.
- **Manual Override & Preset**: Pengaturan durasi pompa air dan nutrisi, serta preset batas optimal lingkungan untuk berbagai jenis tanaman.
- **BMKG Weather Integration**: Menampilkan prediksi cuaca dari API BMKG untuk mendukung efisiensi irigasi.

## Arsitektur Sistem

1. **IoT Nodes (ESP32 / Arduino)**: Mengirimkan data telemetri sensor (via InfluxDB/MQTT).
2. **Raspberry Pi (Camera Node)**: Menjalankan `raspi_streamer.py` untuk me-*stream* video dari greenhouse ke server lokal.
3. **Local Server (Laptop/PC)**:
   - **Laravel 11**: Menjalankan Dashboard UI dan API Backend.
   - **Python YOLOv8**: Menjalankan `plant_disease_scanner.py` di *background* untuk menangkap stream dari Raspi dan menyimpannya di folder `public/plant_scans`.
   - **Ollama**: Menjalankan model `llama3` untuk memberikan rekomendasi AI.
4. **Database**: Menggunakan MySQL untuk konfigurasi/user dan InfluxDB untuk data historis *time-series*.

## Prasyarat (Requirements)

- **PHP** ^8.2 & **Composer**
- **Node.js** & **NPM**
- **Python** 3.9+ (untuk Computer Vision)
- **Ollama** (terinstall dan model `llama3:8b` sudah didownload)
- **InfluxDB** v2 (untuk histori data sensor)
- **NVIDIA GPU** (opsional tapi direkomendasikan untuk YOLOv8)

## Cara Instalasi & Menjalankan

### 1. Persiapan Dashboard (Laravel)
```bash
# Install dependencies PHP & Node
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Build frontend (Tailwind)
npm run build

# Jalankan server
php artisan serve
```

### 2. Menjalankan Computer Vision (Plant Disease Scanner)
Pastikan model `best.pt` (hasil training YOLOv8) berada di dalam folder project.

**Di Terminal Raspberry Pi (Pengirim Kamera):**
```bash
pip install flask opencv-python
python raspi_streamer.py
```

**Di Terminal Laptop (Server Inferensi AI):**
```bash
pip install ultralytics opencv-python
python plant_disease_scanner.py
```
*(Dashboard Laravel akan secara otomatis membaca hasil deteksi terbaru yang dihasilkan oleh script ini).*

### 3. Menjalankan Ollama AI
Pastikan Ollama berjalan di *background*:
```bash
ollama run llama3:8b
```

## Kontributor

- **HuangMingZhi0206** (Pengembang Utama)

---
*Proyek ini dibangun sebagai bagian dari pengembangan sistem Smart Agriculture modern yang otonom dan presisi.*
