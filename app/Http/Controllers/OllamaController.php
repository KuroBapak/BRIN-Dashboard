<?php

namespace App\Http\Controllers;

use App\Services\OllamaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OllamaController extends Controller
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Kamu adalah AI asisten untuk manajemen energi AMCS (Autonomous Monitoring Control System) di greenhouse.

Tugasmu HANYA menganalisis data (sensor, cuaca BMKG, energi) lalu memberikan kesimpulan status dan 3-4 poin rekomendasi singkat.

FORMAT OUTPUT HARUS SEPERTI INI (Pilih salah satu status yang paling cocok: 🟢 Sistem Normal, 🟡 Hoarding Mode, atau 🔴 Mode Darurat Aktif):

[EMOJI] [NAMA STATUS]:
• [Poin rekomendasi aktuator 1]
• [Poin rekomendasi aktuator 2]
• [Poin info baterai/energi]
• [Poin info cuaca]

Contoh Output:
🟢 Sistem Normal:
• Semua aktuator beroperasi penuh
• Surplus energi mengisi baterai
• Cuaca mendukung 24-48 jam ke depan

ATURAN KERAS:
- DILARANG memberikan paragraf penjelasan!
- DILARANG membuat format tanya jawab!
- HANYA keluarkan teks sesuai format di atas, tidak lebih dari 5 baris!
PROMPT;

    /**
     * Analyze energy + weather data using Ollama LLM.
     */
    public function analyze(Request $request): JsonResponse
    {
        // Prevent PHP from timing out (default is 30s) since 8B models can take a while to generate
        set_time_limit(180);

        $ollama = new OllamaService();

        if (!$ollama->isAvailable()) {
            return response()->json([
                'error' => 'Ollama tidak tersedia. Pastikan Ollama sudah berjalan di localhost:11434',
            ], 503);
        }

        $data = $request->validate([
            'energy'   => 'required|array',
            'weather'  => 'required|array',
            'sensors'  => 'nullable|array',
        ]);

        $userPrompt = $this->buildUserPrompt($data);

        $response = $ollama->chat(self::SYSTEM_PROMPT, $userPrompt);

        if ($response === null) {
            return response()->json([
                'error' => 'Gagal mendapatkan respons dari Ollama. Coba lagi.',
            ], 500);
        }

        return response()->json([
            'analysis' => $response,
            'model'    => config('services.ollama.model', 'llama3.2:3b'),
        ]);
    }

    /**
     * Stream analysis response using Ollama (Server-Sent Events).
     */
    public function analyzeStream(Request $request): StreamedResponse
    {
        $ollama = new OllamaService();

        if (!$ollama->isAvailable()) {
            return response()->json([
                'error' => 'Ollama tidak tersedia.',
            ], 503);
        }

        $data = $request->all();
        $userPrompt = $this->buildUserPrompt($data);

        return response()->stream(function () use ($ollama, $userPrompt) {
            // Send SSE headers
            echo "data: " . json_encode(['type' => 'start']) . "\n\n";
            ob_flush();
            flush();

            foreach ($ollama->chatStream(self::SYSTEM_PROMPT, $userPrompt) as $token) {
                echo "data: " . json_encode(['type' => 'token', 'content' => $token]) . "\n\n";
                ob_flush();
                flush();
            }

            echo "data: " . json_encode(['type' => 'done']) . "\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type'  => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection'    => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Check if Ollama is running and which model is available.
     */
    public function status(): JsonResponse
    {
        $ollama = new OllamaService();

        return response()->json([
            'available' => $ollama->isAvailable(),
            'model'     => config('services.ollama.model', 'llama3.2:3b'),
            'url'       => config('services.ollama.url', 'http://localhost:11434'),
        ]);
    }

    /**
     * Build a detailed user prompt from sensor, energy, and weather data.
     */
    private function buildUserPrompt(array $data): string
    {
        $energy = $data['energy'] ?? [];
        $weather = $data['weather'] ?? [];
        $sensors = $data['sensors'] ?? [];

        $prompt = "=== DATA REAL-TIME SISTEM AMCS ===\n\n";

        // Ambil data energi real-time dari InfluxDB
        $energyData = $this->fetchEnergyFromInflux();

        // Energy section
        $prompt .= "📊 DATA KELISTRIKAN:\n";
        $prompt .= "- Daya Solar Panel (Power IN): " . ($energyData['pv_power'] ?? 'N/A') . " W\n";
        $prompt .= "- Tegangan Solar Panel: " . ($energyData['pv_voltage'] ?? 'N/A') . " V\n";
        $prompt .= "- Beban Sistem (Power OUT): " . ($energyData['load_power'] ?? 'N/A') . " W\n";
        $prompt .= "- Net Daya: " . ($energyData['net_power'] ?? 'N/A') . " W\n";
        $prompt .= "- Persentase Baterai: " . ($energyData['battery_percentage'] ?? 'N/A') . "%\n";
        $prompt .= "- Tegangan Baterai: " . ($energyData['battery_voltage'] ?? 'N/A') . " V\n";
        $prompt .= "- Kapasitas Baterai Max: 240 Wh (12V 20Ah)\n\n";

        // Sensor section
        if (!empty($sensors)) {
            $prompt .= "🌡️ DATA SENSOR:\n";
            if (isset($sensors['water_temp'])) $prompt .= "- Suhu Air: {$sensors['water_temp']}°C\n";
            if (isset($sensors['air_temp']))   $prompt .= "- Suhu Udara: {$sensors['air_temp']}°C\n";
            if (isset($sensors['humidity']))   $prompt .= "- Kelembapan: {$sensors['humidity']}%\n";
            if (isset($sensors['ph']))         $prompt .= "- pH: {$sensors['ph']}\n";
            if (isset($sensors['tds']))        $prompt .= "- TDS: {$sensors['tds']} ppm\n";
            if (isset($sensors['turbidity']))  $prompt .= "- Turbidity: {$sensors['turbidity']}\n";
            if (isset($sensors['light']))      $prompt .= "- Cahaya (LDR): {$sensors['light']}%\n";
            $prompt .= "\n";
        }

        // Weather forecast section
        if (!empty($weather)) {
            $prompt .= "🌤️ PRAKIRAAN CUACA BMKG (24 Jam ke Depan):\n";
            $prompt .= "Lokasi: " . ($weather['location'] ?? 'Cikarang Utara, Bekasi') . "\n";

            if (!empty($weather['forecasts'])) {
                foreach ($weather['forecasts'] as $i => $f) {
                    $prompt .= sprintf(
                        "- %s: %s, Suhu %d°C, Awan %d%%, Kelembapan %d%%, Angin %.1f km/j dari %s\n",
                        $f['time'] ?? '?',
                        $f['desc'] ?? '?',
                        $f['temp'] ?? 0,
                        $f['cloud'] ?? 0,
                        $f['humidity'] ?? 0,
                        $f['wind_speed'] ?? 0,
                        $f['wind_dir'] ?? '?'
                    );
                }
            }
            $prompt .= "\n";
        }

        $prompt .= "=== TUGAS ===\n";
        $prompt .= "Berikan output rekomendasi singkat berdasarkan data di atas dengan mematuhi format pada System Prompt.\n";

        return $prompt;
    }

    /**
     * Fetch real-time energy telemetry directly from InfluxDB.
     */
    private function fetchEnergyFromInflux(): array
    {
        $url = config('services.influxdb.url');
        $token = config('services.influxdb.token');
        $org = config('services.influxdb.org');
        $bucketSolar = config('services.influxdb.bucket_solar', 'solar_data');

        $energyData = [
            'pv_voltage' => 0,
            'pv_current' => 0,
            'pv_power' => 0,
            'battery_voltage' => 0,
            'battery_percentage' => 0,
            'load_power' => 0,
            'net_power' => 0,
            'temperature' => 0,
        ];

        if (empty($token) || empty($bucketSolar)) {
            return $energyData;
        }

        try {
            $client = new \InfluxDB2\Client([
                "url" => $url,
                "token" => $token,
                "org" => $org,
                "verifySSL" => false,
            ]);

            $queryApi = $client->createQueryApi();
            $query = "
              from(bucket: \"{$bucketSolar}\")
                |> range(start: -5m)
                |> filter(fn: (r) => r[\"_measurement\"] == \"solar_panel\")
                |> last()
            ";

            $tables = $queryApi->query($query, $org);
            foreach ($tables as $table) {
                foreach ($table->records as $record) {
                    $field = $record->getField();
                    $val = $record->getValue();
                    if (array_key_exists($field, $energyData)) {
                        $energyData[$field] = round((float)$val, 2);
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("OllamaController InfluxDB Query Error: " . $e->getMessage());
        }

        return $energyData;
    }
}
