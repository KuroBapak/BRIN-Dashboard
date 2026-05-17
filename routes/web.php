<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // SmartGarden Config & Command routes
    Route::get('/settings', [\App\Http\Controllers\DeviceCommandController::class, 'settingsView'])->name('settings');
    Route::post('/settings/config', [\App\Http\Controllers\DeviceCommandController::class, 'updateConfig'])->name('settings.config');
    Route::post('/settings/pump', [\App\Http\Controllers\DeviceCommandController::class, 'manualOverride'])->name('settings.pump');

    // Plant Preset CRUD routes
    Route::post('/plant-presets', [\App\Http\Controllers\PlantPresetController::class, 'store'])->name('plant-presets.store');
    Route::put('/plant-presets/{plantPreset}', [\App\Http\Controllers\PlantPresetController::class, 'update'])->name('plant-presets.update');
    Route::delete('/plant-presets/{plantPreset}', [\App\Http\Controllers\PlantPresetController::class, 'destroy'])->name('plant-presets.destroy');

    // Solar Panel Real-Time API
    Route::get('/api/solar', [\App\Http\Controllers\DashboardController::class, 'solarData'])->name('api.solar');

    // BMKG Weather Forecast API
    Route::get('/api/bmkg/forecast', [\App\Http\Controllers\BmkgController::class, 'forecast'])->name('api.bmkg.forecast');

    // Plant Disease Detection API
    Route::get('/api/plant-scan/latest', function () {
        $jsonPath = public_path('plant_scans/latest.json');
        if (file_exists($jsonPath)) {
            return response()->json(json_decode(file_get_contents($jsonPath), true));
        }
        return response()->json(['error' => 'Belum ada data scan.', 'status' => null], 200);
    })->name('api.plant-scan.latest');

    Route::get('/api/plant-scan/history', function () {
        $dir = public_path('plant_scans');
        $files = [];
        if (is_dir($dir)) {
            foreach (glob($dir . '/scan_*.json') as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && isset($data['timestamp_formatted'])) {
                    $files[] = [
                        'filename' => basename($file),
                        'label' => $data['timestamp_formatted'],
                        'timestamp' => $data['timestamp'] ?? '',
                        'status' => $data['status']['status'] ?? 'unknown',
                        'status_emoji' => $data['status']['status_emoji'] ?? '❓',
                        'total_detections' => $data['total_detections'] ?? 0,
                    ];
                }
            }
        }
        // Urutkan terbaru dulu
        usort($files, fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));
        return response()->json($files);
    })->name('api.plant-scan.history');

    Route::get('/api/plant-scan/view/{filename}', function ($filename) {
        // Sanitasi nama file
        $filename = basename($filename);
        if (!str_ends_with($filename, '.json') || !str_starts_with($filename, 'scan_')) {
            return response()->json(['error' => 'File tidak valid.'], 400);
        }
        $jsonPath = public_path('plant_scans/' . $filename);
        if (file_exists($jsonPath)) {
            return response()->json(json_decode(file_get_contents($jsonPath), true));
        }
        return response()->json(['error' => 'File tidak ditemukan.'], 404);
    })->name('api.plant-scan.view');

    Route::post('/api/plant-scan/trigger', function () {
        $triggerPath = public_path('plant_scans/trigger_scan');
        file_put_contents($triggerPath, now()->toIso8601String());
        return response()->json(['triggered' => true, 'message' => 'Scan request telah dikirim.']);
    })->name('api.plant-scan.trigger');

    // Ollama AI Analysis API
    Route::post('/api/ollama/analyze', [\App\Http\Controllers\OllamaController::class, 'analyze'])->name('api.ollama.analyze');
    Route::post('/api/ollama/stream', [\App\Http\Controllers\OllamaController::class, 'analyzeStream'])->name('api.ollama.stream');
    Route::get('/api/ollama/status', [\App\Http\Controllers\OllamaController::class, 'status'])->name('api.ollama.status');
});

require __DIR__.'/auth.php';
