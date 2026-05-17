<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Dashboard') }}</h2>
            <div id="mqtt_status_badge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-sm bg-amber-50 text-amber-700 border-amber-200">
                <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span></span>
                Connecting...
            </div>
        </div>
    </x-slot>

    <style>
        @keyframes siren-pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.35); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .pump-active-row {
            animation: siren-pulse 1.5s infinite;
            background-color: #fef2f2 !important;
            border-color: #fca5a5 !important;
        }
        .pump-btn {
            position: relative; overflow: hidden;
            transition: all 0.2s ease;
        }
        .pump-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .pump-btn:active { transform: translateY(0); }
        @keyframes fan-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .fan-active {
            animation: fan-spin 0.8s linear infinite;
            opacity: 1 !important;
        }
        .pump-modal-backdrop {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: opacity 0.2s ease;
        }
        .pump-modal-backdrop.active { opacity: 1; pointer-events: auto; }
        .pump-modal {
            background: white; border-radius: 1rem; padding: 1.5rem; width: 90%; max-width: 380px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            transform: scale(0.95); transition: transform 0.2s ease;
        }
        .pump-modal-backdrop.active .pump-modal { transform: scale(1); }
        .unit-btn { transition: all 0.15s ease; }
        .unit-btn.selected { background-color: #3b82f6; color: white; }
        @keyframes pump-running-glow {
            0% { box-shadow: 0 0 0 0 rgba(34,197,94,0.5); }
            50% { box-shadow: 0 0 12px 4px rgba(34,197,94,0.35); }
            100% { box-shadow: 0 0 0 0 rgba(34,197,94,0.5); }
        }
        .pump-running {
            animation: pump-running-glow 1.2s ease-in-out infinite;
            pointer-events: none; opacity: 0.85;
        }
        .pump-running .pump-countdown {
            display: inline-flex;
        }
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ═══════ ROW 1: ENERGY MANAGEMENT ═══════ --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-5">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        Energy Management
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        {{-- Solar --}}
                        <div class="flex items-center gap-4 p-4 rounded-xl bg-amber-50 border border-amber-100">
                            <div class="w-11 h-11 rounded-full bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-500 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold text-amber-600 uppercase tracking-wide">Solar Panel</p>
                                <p class="text-2xl font-bold text-gray-900 leading-tight" id="val_solar_w">-- <span class="text-sm font-medium text-gray-400">W</span></p>
                            </div>
                        </div>
                        {{-- Load --}}
                        <div class="flex items-center gap-4 p-4 rounded-xl bg-blue-50 border border-blue-100">
                            <div class="w-11 h-11 rounded-full bg-blue-100 border border-blue-200 flex items-center justify-center text-blue-500 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold text-blue-600 uppercase tracking-wide">System Load</p>
                                <p class="text-2xl font-bold text-gray-900 leading-tight" id="val_load_w">-- <span class="text-sm font-medium text-gray-400">W</span></p>
                            </div>
                        </div>
                        {{-- Battery --}}
                        <div class="flex items-center gap-4 p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                            <div class="w-11 h-11 rounded-full bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-500 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wide">Battery</p>
                                    <p class="text-lg font-bold text-gray-900" id="val_battery_pct">--%</p>
                                </div>
                                <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden mt-1">
                                    <div id="battery_bar_fill" class="h-full bg-emerald-500 rounded-full transition-all duration-700" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════ ROW 2: MONITORING + WATER QUALITY + PUMP CONTROL ═══════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Combined Monitoring + Water Quality Panel (2 col) --}}
                <div class="lg:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5">
                        {{-- Realtime Monitoring Section --}}
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Realtime Monitoring
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                            <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 border-l-4 border-l-blue-500">
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Water Temp</div>
                                <div class="mt-2 text-2xl font-bold text-gray-900" id="val_water_temp">-- °C</div>
                            </div>
                            <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 border-l-4 border-l-green-500">
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">WiFi RSSI</div>
                                <div class="mt-2 text-2xl font-bold text-gray-900" id="val_rssi">-- dBm</div>
                            </div>
                            <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 border-l-4 border-l-cyan-500">
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Humidity</div>
                                <div class="mt-2 text-2xl font-bold text-gray-900" id="val_humidity">-- %</div>
                            </div>
                            <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 border-l-4 border-l-purple-500">
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Light Level</div>
                                <div class="mt-2 text-2xl font-bold text-gray-900" id="val_light">-- %</div>
                            </div>
                        </div>

                        {{-- Divider --}}
                        <hr class="border-gray-100 mb-5">

                        {{-- Water Quality & Automation Section --}}
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                Water Quality & Automation Status
                            </h3>
                            <span class="flex h-2.5 w-2.5 relative" title="System Health">
                                <span id="water_health_ping" class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span id="water_health_dot" class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            {{-- pH --}}
                            <div id="card_ph" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 transition-all duration-300">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">pH Level</span>
                                    <svg id="icon_pump_ph" class="w-4 h-4 text-red-500 opacity-0 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </div>
                                <p class="text-3xl font-bold text-gray-900" id="val_ph">--</p>
                                <p id="status_ph" class="text-[11px] text-gray-400 mt-1">Target Min: {{ $setting->min_ph }}</p>
                            </div>
                            {{-- TDS --}}
                            <div id="card_tds" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 transition-all duration-300">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">TDS Nutrisi</span>
                                    <svg id="icon_pump_tds" class="w-4 h-4 text-red-500 opacity-0 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </div>
                                <p class="text-3xl font-bold text-gray-900" id="val_tds">-- <span class="text-sm font-medium text-gray-400">ppm</span></p>
                                <p id="status_tds" class="text-[11px] text-gray-400 mt-1">Target Min: {{ $setting->min_tds }} ppm</p>
                            </div>
                            {{-- Turbidity --}}
                            <div id="card_turbidity" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 transition-all duration-300">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Turbidity</span>
                                    <svg id="icon_pump_turbidity" class="w-4 h-4 text-red-500 opacity-0 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <p class="text-3xl font-bold text-gray-900" id="val_turbidity">--</p>
                                <p id="status_turbidity" class="text-[11px] text-gray-400 mt-1">Target Max: {{ $setting->max_turb }}</p>
                            </div>
                            {{-- Air Temp (with Fan pump animation) --}}
                            <div id="card_air_temp" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 transition-all duration-300">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Air Temp</span>
                                    <svg id="icon_pump_fan" class="w-5 h-5 text-red-500 opacity-0 transition-opacity fan-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.59 4.59A2 2 0 1111 8H2m10.59 11.41A2 2 0 1014 16H2m15.73-8.27A2.5 2.5 0 1119.5 12H2"/></svg>
                                </div>
                                <p class="text-3xl font-bold text-gray-900" id="val_air_temp">-- <span class="text-sm font-medium text-gray-400">°C</span></p>
                                <p id="status_air_temp" class="text-[11px] text-gray-400 mt-1">Target Max: {{ $setting->max_temp ?? 30 }} °C</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Manual Pump Override (1 col) --}}
                <div class="lg:col-span-1 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5 h-full flex flex-col">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                            Manual Override
                        </h3>
                        <div class="flex-1 flex flex-col justify-center space-y-3">
                            <button id="btn_pump_ph" onclick="openPumpModal('ph', 'pH Pump', 'Injeksi Buffer')" class="pump-btn w-full flex items-center justify-between p-3.5 rounded-lg bg-green-600 text-white shadow-sm hover:bg-green-700 border border-green-700">
                                <div><span class="text-sm font-bold">pH Pump</span><br><span class="text-[10px] text-green-200">Injeksi Buffer</span></div>
                                <span class="pump-countdown hidden items-center gap-1 text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg><span class="countdown-text"></span></span>
                                <svg class="w-4 h-4 text-green-200 pump-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button id="btn_pump_tds" onclick="openPumpModal('tds', 'TDS Pump', 'Injeksi AB Mix')" class="pump-btn w-full flex items-center justify-between p-3.5 rounded-lg bg-yellow-500 text-white shadow-sm hover:bg-yellow-600 border border-yellow-600">
                                <div><span class="text-sm font-bold">TDS Pump</span><br><span class="text-[10px] text-yellow-100">Injeksi AB Mix</span></div>
                                <span class="pump-countdown hidden items-center gap-1 text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg><span class="countdown-text"></span></span>
                                <svg class="w-4 h-4 text-yellow-100 pump-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button id="btn_pump_water" onclick="openPumpModal('water', 'Water Pump', 'Sirkulasi / Flush')" class="pump-btn w-full flex items-center justify-between p-3.5 rounded-lg bg-blue-600 text-white shadow-sm hover:bg-blue-700 border border-blue-700">
                                <div><span class="text-sm font-bold">Water Pump</span><br><span class="text-[10px] text-blue-200">Sirkulasi / Flush</span></div>
                                <span class="pump-countdown hidden items-center gap-1 text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg><span class="countdown-text"></span></span>
                                <svg class="w-4 h-4 text-blue-200 pump-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button id="btn_pump_fan" onclick="openPumpModal('fan', 'Fan / Spray', 'Pendingin Udara')" class="pump-btn w-full flex items-center justify-between p-3.5 rounded-lg bg-purple-600 text-white shadow-sm hover:bg-purple-700 border border-purple-700">
                                <div><span class="text-sm font-bold">Fan / Spray</span><br><span class="text-[10px] text-purple-200">Pendingin Udara</span></div>
                                <span class="pump-countdown hidden items-center gap-1 text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg><span class="countdown-text"></span></span>
                                <svg class="w-4 h-4 text-purple-200 pump-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════ ROW 3: BMKG WEATHER FORECAST + SMART BATTERY AI ═══════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- BMKG Weather Forecast Table (2 col) --}}
                <div class="lg:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                                Prakiraan Cuaca BMKG — Cikarang Utara
                            </h3>
                            <div class="flex items-center gap-2">
                                <span id="bmkg_last_update" class="text-[10px] text-gray-400"></span>
                                <button onclick="fetchBmkgForecast()" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors" title="Refresh">
                                    <svg id="bmkg_refresh_icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Day Tabs --}}
                        <div class="flex gap-1.5 mb-4" id="bmkg_day_tabs"></div>

                        {{-- Weather Table --}}
                        <div class="overflow-x-auto rounded-xl border border-gray-100">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gradient-to-r from-sky-50 to-blue-50">
                                        <th class="px-3 py-2.5 text-left text-[11px] font-bold text-sky-700 uppercase tracking-wider">Waktu</th>
                                        <th class="px-3 py-2.5 text-center text-[11px] font-bold text-sky-700 uppercase tracking-wider">Cuaca</th>
                                        <th class="px-3 py-2.5 text-center text-[11px] font-bold text-sky-700 uppercase tracking-wider">Suhu</th>
                                        <th class="px-3 py-2.5 text-center text-[11px] font-bold text-sky-700 uppercase tracking-wider">Kelembapan</th>
                                        <th class="px-3 py-2.5 text-center text-[11px] font-bold text-sky-700 uppercase tracking-wider">Awan</th>
                                        <th class="px-3 py-2.5 text-center text-[11px] font-bold text-sky-700 uppercase tracking-wider">Angin</th>
                                        <th class="px-3 py-2.5 text-center text-[11px] font-bold text-sky-700 uppercase tracking-wider">Jarak Pandang</th>
                                    </tr>
                                </thead>
                                <tbody id="bmkg_forecast_body">
                                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">
                                        <svg class="w-5 h-5 animate-spin mx-auto mb-2 text-sky-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                        Memuat data BMKG...
                                    </td></tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- BMKG Attribution --}}
                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-[10px] text-gray-400">Sumber data: <strong>BMKG</strong> (Badan Meteorologi, Klimatologi, dan Geofisika)</p>
                            <span id="bmkg_analysis_date" class="text-[10px] text-gray-400"></span>
                        </div>
                    </div>
                </div>

                {{-- Smart Battery AI Panel (1 col) --}}
                <div class="lg:col-span-1 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5 h-full flex flex-col">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            Smart Battery AI
                        </h3>

                        {{-- Status Indicator --}}
                        <div id="ai_status_card" class="p-4 rounded-xl border-2 mb-4 transition-all duration-500 bg-gray-50 border-gray-200">
                            <div class="flex items-center gap-3 mb-2">
                                <div id="ai_status_icon" class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-200 text-gray-400 transition-all duration-500 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <div>
                                    <p id="ai_status_label" class="text-sm font-bold text-gray-700">Menunggu Data...</p>
                                    <p id="ai_status_desc" class="text-[11px] text-gray-400 leading-tight">Menganalisis prakiraan cuaca</p>
                                </div>
                            </div>
                        </div>

                        {{-- Energy Metrics --}}
                        <div class="space-y-3 flex-1">
                            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Net Daya</span>
                                    <span id="ai_net_power" class="text-sm font-bold text-gray-700">-- W</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Solar IN − System OUT</p>
                            </div>

                            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Estimasi Ketahanan</span>
                                    <span id="ai_endurance" class="text-sm font-bold text-gray-700">-- Jam</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Saat cuaca buruk tiba</p>
                            </div>

                            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Prakiraan Solar</span>
                                    <span id="ai_solar_forecast" class="text-sm font-bold text-gray-700">-- W</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Besok (prediksi dari awan)</p>
                            </div>

                            <div id="ai_action_box" class="p-3 rounded-lg border-2 border-dashed border-gray-200 bg-gray-50/50 transition-all duration-500">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Rekomendasi AI</p>
                                    <div class="flex items-center gap-1.5" title="Status Koneksi AI Lokal">
                                        <span id="ollama_status_dot" class="h-2 w-2 rounded-full bg-gray-300 transition-colors"></span>
                                    </div>
                                </div>
                                <p id="ai_recommendation" class="text-xs text-gray-600 leading-relaxed">Menunggu data cuaca dan energi...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════ ROW 4: PLANT DISEASE DETECTION ═══════ --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-5">
                    {{-- Header with Capture button --}}
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Plant Disease Detection
                            <span id="plant_scan_status_dot" class="h-2 w-2 rounded-full bg-gray-300 transition-colors"></span>
                        </h3>
                        <div class="flex items-center gap-2 flex-wrap">
                            {{-- History Selector --}}
                            <select id="plant_history_select" onchange="loadPlantScanHistory(this.value)" class="text-xs border-gray-300 rounded-lg shadow-sm focus:border-lime-400 focus:ring focus:ring-lime-200 focus:ring-opacity-50 py-1.5 pl-2 pr-7 bg-white">
                                <option value="latest">📷 Terbaru (Live)</option>
                            </select>
                            {{-- Capture Now Button --}}
                            <button id="btn_capture_now" onclick="triggerManualCapture()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-lime-500 to-emerald-500 text-white text-xs font-bold hover:from-lime-600 hover:to-emerald-600 shadow-md transition-all hover:shadow-lg active:scale-95 border border-lime-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span id="btn_capture_text">Capture Now</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                        {{-- Status Card --}}
                        <div class="lg:col-span-1 space-y-4">
                            {{-- Health Status --}}
                            <div id="plant_status_card" class="p-4 rounded-xl border-2 transition-all duration-500 bg-gray-50 border-gray-200">
                                <div class="flex items-center gap-3 mb-2">
                                    <div id="plant_status_icon" class="w-12 h-12 rounded-full flex items-center justify-center bg-gray-200 text-gray-400 transition-all duration-500 shrink-0 text-xl">
                                        🌿
                                    </div>
                                    <div>
                                        <p id="plant_status_label" class="text-sm font-bold text-gray-700">Menunggu Scan...</p>
                                        <p id="plant_status_desc" class="text-[11px] text-gray-400 leading-tight">Jalankan plant_disease_scanner.py</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Detected Diseases List --}}
                            <div id="plant_diseases_box" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 hidden">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-3">Penyakit Terdeteksi</p>
                                <div id="plant_diseases_list" class="space-y-2">
                                </div>
                            </div>

                            {{-- Detection Summary --}}
                            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Total Deteksi</span>
                                    <span id="plant_total_detections" class="text-sm font-bold text-gray-700">--</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Jumlah objek terdeteksi</p>
                            </div>

                            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Waktu Scan</span>
                                    <span id="plant_last_scan_time" class="text-sm font-bold text-gray-700">--</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Capture dari kamera Raspi</p>
                            </div>
                        </div>

                        {{-- Captured Image --}}
                        <div class="lg:col-span-2">
                            <div id="plant_image_container" class="relative rounded-xl border-2 border-gray-100 overflow-hidden bg-gray-100 flex items-center justify-center" style="min-height: 320px;">
                                {{-- Placeholder --}}
                                <div id="plant_image_placeholder" class="text-center p-8">
                                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <p class="text-sm font-medium text-gray-400">Belum ada gambar scan</p>
                                    <p class="text-xs text-gray-300 mt-1">Tekan tombol <strong class="text-lime-500">Capture Now</strong> atau jalankan scanner</p>
                                </div>
                                {{-- Actual Image --}}
                                <img id="plant_scan_image" src="" alt="Plant Scan" class="w-full h-auto object-contain hidden cursor-pointer" onclick="togglePlantImageZoom()" style="max-height: 450px;">
                            </div>
                            <p class="text-[10px] text-gray-400 mt-2 text-center">Klik gambar untuk zoom • Auto scan setiap 5 menit</p>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ═══════ ROW 5: HISTORICAL CHART (ORIGINAL STYLE) ═══════ --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                        <h3 class="text-lg font-medium text-gray-900">Historical Data Trends</h3>
                        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center space-x-2">
                            <select name="range" onchange="this.form.submit()" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="-1h" {{ $range == '-1h' ? 'selected' : '' }}>Last 1 Hour</option>
                                <option value="-6h" {{ $range == '-6h' ? 'selected' : '' }}>Last 6 Hours</option>
                                <option value="-12h" {{ $range == '-12h' ? 'selected' : '' }}>Last 12 Hours</option>
                                <option value="-24h" {{ $range == '-24h' ? 'selected' : '' }}>Last 24 Hours</option>
                                <option value="-7d" {{ $range == '-7d' ? 'selected' : '' }}>Last 7 Days</option>
                            </select>
                            <select name="interval" onchange="this.form.submit()" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="5m" {{ $interval == '5m' ? 'selected' : '' }}>Jarak: 5m</option>
                                <option value="10m" {{ $interval == '10m' ? 'selected' : '' }}>Jarak: 10m</option>
                                <option value="15m" {{ $interval == '15m' ? 'selected' : '' }}>Jarak: 15m</option>
                                <option value="30m" {{ $interval == '30m' ? 'selected' : '' }}>Jarak: 30m</option>
                                <option value="1h" {{ $interval == '1h' ? 'selected' : '' }}>Jarak: 1h</option>
                            </select>
                        </form>
                    </div>
                    <div class="relative h-96 w-full">
                        <canvas id="historicalChart"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ─── 1. CHART (ORIGINAL STYLE with 3 Y-Axes, dots, all 7 datasets) ─────
            const ctx = document.getElementById('historicalChart').getContext('2d');
            const rawData = @json($historicalData);

            const historicalSize = rawData.labels.length;
            const initRadius = () => Array(historicalSize).fill(3);
            const initHitRadius = () => Array(historicalSize).fill(10);
            const initHoverRadius = () => Array(historicalSize).fill(4);

            const chartData = {
                labels: rawData.labels,
                datasets: [
                    {
                        label: 'Water Temp (°C)', data: rawData.water_temp,
                        borderColor: 'rgb(59, 130, 246)', tension: 0.3, yAxisID: 'y-regular',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'Air Temp (°C)', data: rawData.air_temp,
                        borderColor: 'rgb(239, 68, 68)', tension: 0.3, yAxisID: 'y-regular',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'pH', data: rawData.ph,
                        borderColor: 'rgb(34, 197, 94)', tension: 0.3, yAxisID: 'y-regular',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'Humidity (%)', data: rawData.humidity,
                        borderColor: 'rgb(6, 182, 212)', tension: 0.3, yAxisID: 'y-percent',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'Light Level (%)', data: rawData.light,
                        borderColor: 'rgb(168, 85, 247)', tension: 0.3, yAxisID: 'y-percent',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'TDS (ppm)', data: rawData.tds,
                        borderColor: 'rgb(234, 179, 8)', tension: 0.3, yAxisID: 'y-large',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'Turbidity', data: rawData.turbidity,
                        borderColor: 'rgb(249, 115, 22)', tension: 0.3, yAxisID: 'y-percent',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'RSSI (dBm)', data: rawData.rssi,
                        borderColor: 'rgb(16, 185, 129)', tension: 0.3, yAxisID: 'y-rssi',
                        borderDash: [5, 3],
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    }
                ]
            };

            const myChart = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true, maintainAspectRatio: false, spanGaps: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { align: 'center', labels: { padding: 14 } } },
                    elements: { point: { hitRadius: 10, hoverRadius: 4 } },
                    scales: {
                        x: {
                            type: 'time',
                            time: { tooltipFormat: 'dd MMM yyyy HH:mm', displayFormats: { minute: 'HH:mm', hour: 'HH:mm' } },
                            ticks: { maxRotation: 45, minRotation: 45 }
                        },
                        'y-regular': {
                            type: 'linear', display: true, position: 'left',
                            title: { display: true, text: 'Temp & pH' },
                            beginAtZero: true, suggestedMax: 40
                        },
                        'y-percent': {
                            type: 'linear', display: true, position: 'right',
                            title: { display: true, text: 'Percentage / Turbidity' },
                            beginAtZero: true, suggestedMax: 100,
                            grid: { drawOnChartArea: false }
                        },
                        'y-large': {
                            type: 'linear', display: true, position: 'right',
                            title: { display: true, text: 'TDS (ppm)' },
                            suggestedMin: 0, suggestedMax: 1000,
                            grid: { drawOnChartArea: false }
                        },
                        'y-rssi': {
                            type: 'linear', display: false,
                            suggestedMin: -100, suggestedMax: 0,
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });

            // ─── 2. MQTT ────────────────────────────────────────────────────────────
            const mqttOptions = {
                keepalive: 60,
                clientId: '{{ config("services.mqtt.client_id") }}' + '-' + Math.random().toString(16).substr(2, 6),
                protocolId: 'MQTT', protocolVersion: 4, clean: true, reconnectPeriod: 1000, connectTimeout: 30000,
                username: '{{ config("services.mqtt.username") }}',
                password: '{{ config("services.mqtt.password") }}',
            };

            const mqttHost = '{{ config("services.mqtt.host") }}';
            const mqttWsPort = '{{ config("services.mqtt.ws_port") }}';
            const brokerUrl = mqttWsPort ? `ws://${mqttHost}:${mqttWsPort}/mqtt` : `wss://${mqttHost}/mqtt`;
            const pubTopic = `brin/water/{{ $setting->device_id }}/down/cmd`;

            const badge = document.getElementById('mqtt_status_badge');
            const client = mqtt.connect(brokerUrl, mqttOptions);

            client.on('connect', function () {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-sm bg-emerald-50 text-emerald-700 border-emerald-200';
                badge.innerHTML = '<span class="h-2 w-2 rounded-full bg-emerald-500"></span> Online';
                client.subscribe('brin/water/+/up/telemetry');
            });
            client.on('error', function () {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-sm bg-red-50 text-red-700 border-red-200';
                badge.innerHTML = '<span class="h-2 w-2 rounded-full bg-red-500"></span> Error';
            });
            client.on('offline', function () {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-sm bg-gray-50 text-gray-600 border-gray-200';
                badge.innerHTML = '<span class="h-2 w-2 rounded-full bg-gray-400"></span> Offline';
            });

            // ─── 3. MANUAL PUMP MODAL ──────────────────────────────────────────────
            const pumpHistoryKey = (pump) => `pump_history_${pump}`;

            function getPumpHistory(pump) {
                try { return JSON.parse(localStorage.getItem(pumpHistoryKey(pump))) || null; }
                catch { return null; }
            }
            function savePumpHistory(pump, val, unit) {
                localStorage.setItem(pumpHistoryKey(pump), JSON.stringify({ val, unit }));
            }

            let currentPumpTarget = '';

            window.openPumpModal = function(pump, title, subtitle) {
                currentPumpTarget = pump;
                document.getElementById('modal_pump_title').textContent = title;
                document.getElementById('modal_pump_subtitle').textContent = subtitle;
                document.getElementById('modal_duration_input').value = '';
                // reset unit buttons
                document.getElementById('unit_sec').classList.add('selected');
                document.getElementById('unit_min').classList.remove('selected');
                // show history
                const hist = getPumpHistory(pump);
                const histEl = document.getElementById('modal_history_section');
                if (hist) {
                    histEl.classList.remove('hidden');
                    const label = hist.unit === 'min' ? 'menit' : 'detik';
                    document.getElementById('modal_history_text').textContent = `${hist.val} ${label}`;
                } else {
                    histEl.classList.add('hidden');
                }
                document.getElementById('pump_modal_backdrop').classList.add('active');
            };

            window.closePumpModal = function() {
                document.getElementById('pump_modal_backdrop').classList.remove('active');
            };

            window.selectUnit = function(unit) {
                if (unit === 'sec') {
                    document.getElementById('unit_sec').classList.add('selected');
                    document.getElementById('unit_min').classList.remove('selected');
                } else {
                    document.getElementById('unit_min').classList.add('selected');
                    document.getElementById('unit_sec').classList.remove('selected');
                }
            };

            window.useHistory = function() {
                const hist = getPumpHistory(currentPumpTarget);
                if (hist) {
                    document.getElementById('modal_duration_input').value = hist.val;
                    selectUnit(hist.unit === 'min' ? 'min' : 'sec');
                }
            };

            function startPumpAnimation(pump, durationMs) {
                const btn = document.getElementById(`btn_pump_${pump}`);
                if (!btn) return;
                const arrow = btn.querySelector('.pump-arrow');
                if (arrow) arrow.classList.add('hidden');
                btn.classList.add('pump-running');
                let remaining = Math.ceil(durationMs / 1000);
                const countdownEl = btn.querySelector('.countdown-text');
                countdownEl.textContent = `${remaining}s`;
                const interval = setInterval(() => {
                    remaining--;
                    if (remaining <= 0) {
                        clearInterval(interval);
                        btn.classList.remove('pump-running');
                        if (arrow) arrow.classList.remove('hidden');
                        countdownEl.textContent = '';
                    } else {
                        countdownEl.textContent = `${remaining}s`;
                    }
                }, 1000);
            }

            window.confirmPump = function() {
                const val = parseInt(document.getElementById('modal_duration_input').value);
                if (!val || val <= 0) { alert('Masukkan durasi yang valid!'); return; }
                if (!client.connected) { alert('MQTT Not Connected!'); closePumpModal(); return; }
                const isMin = document.getElementById('unit_min').classList.contains('selected');
                const unit = isMin ? 'min' : 'sec';
                const durationMs = isMin ? val * 60000 : val * 1000;
                savePumpHistory(currentPumpTarget, val, unit);
                const payload = { action: 'manual_pump', target: currentPumpTarget, duration: durationMs };
                const pumpTarget = currentPumpTarget;
                client.publish(pubTopic, JSON.stringify(payload), {qos: 0}, function(err) {
                    if (err) alert('Gagal kirim command');
                });
                closePumpModal();
                startPumpAnimation(pumpTarget, durationMs);
            };

            // ─── 4. INCOMING TELEMETRY ──────────────────────────────────────────────
            client.on('message', function (topic, message) {
                try {
                    const p = JSON.parse(message.toString());

                    const targets = {
                        ph: {{ $setting->min_ph }}, tds: {{ $setting->min_tds }},
                        turb: {{ $setting->max_turb }}, temp: {{ $setting->max_temp ?? 30 }}
                    };

                    // Energy
                    if(p.solar_w !== undefined) document.getElementById('val_solar_w').innerHTML = `${p.solar_w} <span class="text-sm font-medium text-gray-400">W</span>`;
                    if(p.load_w !== undefined) document.getElementById('val_load_w').innerHTML = `${p.load_w} <span class="text-sm font-medium text-gray-400">W</span>`;
                    if(p.battery_pct !== undefined) {
                        document.getElementById('val_battery_pct').innerText = p.battery_pct + '%';
                        document.getElementById('battery_bar_fill').style.width = p.battery_pct + '%';
                    }

                    // Simple sensors
                    if (p.water_temp !== undefined) document.getElementById('val_water_temp').textContent = parseFloat(p.water_temp).toFixed(1) + ' °C';
                    if (p.rssi !== undefined) document.getElementById('val_rssi').textContent = parseInt(p.rssi) + ' dBm';
                    if (p.humidity !== undefined) document.getElementById('val_humidity').textContent = parseInt(p.humidity) + ' %';
                    if (p.light !== undefined) document.getElementById('val_light').textContent = parseInt(p.light) + ' %';

                    let hasAlert = false;

                    // pH
                    if (p.ph !== undefined) {
                        let v = parseFloat(p.ph);
                        document.getElementById('val_ph').textContent = v.toFixed(2);
                        let card = document.getElementById('card_ph');
                        let icon = document.getElementById('icon_pump_ph');
                        let status = document.getElementById('status_ph');
                        if (v < targets.ph) {
                            card.classList.add('pump-active-row'); icon.classList.replace('opacity-0','opacity-100');
                            status.innerHTML = '⚠️ Pompa pH Aktif'; status.className = 'text-[11px] text-red-600 font-bold mt-1';
                            hasAlert = true;
                        } else {
                            card.classList.remove('pump-active-row'); icon.classList.replace('opacity-100','opacity-0');
                            status.innerHTML = `Target Min: ${targets.ph}`; status.className = 'text-[11px] text-gray-400 mt-1';
                        }
                    }

                    // TDS
                    if (p.tds !== undefined) {
                        let v = parseInt(p.tds);
                        document.getElementById('val_tds').innerHTML = `${v} <span class="text-sm font-medium text-gray-400">ppm</span>`;
                        let card = document.getElementById('card_tds');
                        let icon = document.getElementById('icon_pump_tds');
                        let status = document.getElementById('status_tds');
                        if (v < targets.tds) {
                            card.classList.add('pump-active-row'); icon.classList.replace('opacity-0','opacity-100');
                            status.innerHTML = '⚠️ Pompa TDS Aktif'; status.className = 'text-[11px] text-red-600 font-bold mt-1';
                            hasAlert = true;
                        } else {
                            card.classList.remove('pump-active-row'); icon.classList.replace('opacity-100','opacity-0');
                            status.innerHTML = `Target Min: ${targets.tds} ppm`; status.className = 'text-[11px] text-gray-400 mt-1';
                        }
                    }

                    // Turbidity
                    if (p.turbidity !== undefined) {
                        let v = parseFloat(p.turbidity);
                        document.getElementById('val_turbidity').textContent = v;
                        let card = document.getElementById('card_turbidity');
                        let icon = document.getElementById('icon_pump_turbidity');
                        let status = document.getElementById('status_turbidity');
                        if (v > targets.turb) {
                            card.classList.add('pump-active-row'); icon.classList.replace('opacity-0','opacity-100');
                            status.innerHTML = '⚠️ Filter Air Aktif'; status.className = 'text-[11px] text-red-600 font-bold mt-1';
                            hasAlert = true;
                        } else {
                            card.classList.remove('pump-active-row'); icon.classList.replace('opacity-100','opacity-0');
                            status.innerHTML = `Target Max: ${targets.turb}`; status.className = 'text-[11px] text-gray-400 mt-1';
                        }
                    }

                    // Air Temp (Fan automation)
                    if (p.air_temp !== undefined) {
                        let v = parseFloat(p.air_temp);
                        document.getElementById('val_air_temp').innerHTML = `${v.toFixed(1)} <span class="text-sm font-medium text-gray-400">°C</span>`;
                        let card = document.getElementById('card_air_temp');
                        let icon = document.getElementById('icon_pump_fan');
                        let status = document.getElementById('status_air_temp');
                        if (v > targets.temp) {
                            card.classList.add('pump-active-row'); icon.classList.add('fan-active');
                            status.innerHTML = '⚠️ Fan Aktif'; status.className = 'text-[11px] text-red-600 font-bold mt-1';
                            hasAlert = true;
                        } else {
                            card.classList.remove('pump-active-row'); icon.classList.remove('fan-active');
                            status.innerHTML = `Target Max: ${targets.temp} °C`; status.className = 'text-[11px] text-gray-400 mt-1';
                        }
                    }

                    // Health dot
                    const dot = document.getElementById('water_health_dot');
                    const ping = document.getElementById('water_health_ping');
                    if(hasAlert) {
                        dot.className = "relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500";
                        ping.className = "animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75";
                    } else {
                        dot.className = "relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500";
                        ping.className = "absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75";
                    }

                    // Chart push
                    const now = new Date().toISOString();
                    myChart.data.labels.push(now);
                    const push = (i, val) => {
                        myChart.data.datasets[i].data.push(parseFloat(val));
                        myChart.data.datasets[i].pointRadius.push(0);
                        myChart.data.datasets[i].pointHitRadius.push(0);
                        myChart.data.datasets[i].pointHoverRadius.push(0);
                    };
                    if (p.water_temp !== undefined) push(0, p.water_temp);
                    if (p.air_temp !== undefined) push(1, p.air_temp);
                    if (p.ph !== undefined) push(2, p.ph);
                    if (p.humidity !== undefined) push(3, p.humidity);
                    if (p.light !== undefined) push(4, p.light);
                    if (p.tds !== undefined) push(5, p.tds);
                    if (p.turbidity !== undefined) push(6, p.turbidity);
                    if (p.rssi !== undefined) push(7, p.rssi);

                    if (myChart.data.labels.length > 200) {
                        myChart.data.labels.shift();
                        myChart.data.datasets.forEach(d => {
                            d.data.shift(); d.pointRadius.shift(); d.pointHitRadius.shift(); d.pointHoverRadius.shift();
                        });
                    }
                    myChart.update('none');

                } catch (e) { console.error('MQTT parse error:', e); }
            });

            // ─── 5. BMKG WEATHER FORECAST + SMART BATTERY AI ─────────────────────
            let bmkgData = null;
            let bmkgSelectedDay = 0;

            // --- Dummy energy values (will be overridden by MQTT telemetry) ---
            let currentEnergy = {
                solar_w: 50,     // Power IN from solar panel (W)
                load_w: 15,      // System load / Power OUT (W)
                battery_pct: 80, // Battery percentage
                battery_wh: 120, // Battery capacity in Wh (assume 12V 10Ah)
            };

            // Observers removed. Data is updated directly via fetchSolarData API.

            window.fetchBmkgForecast = function() {
                const refreshIcon = document.getElementById('bmkg_refresh_icon');
                refreshIcon.classList.add('animate-spin');

                fetch('{{ route("api.bmkg.forecast") }}')
                    .then(r => r.json())
                    .then(data => {
                        refreshIcon.classList.remove('animate-spin');
                        if (data.error) {
                            document.getElementById('bmkg_forecast_body').innerHTML =
                                `<tr><td colspan="7" class="px-4 py-8 text-center text-red-400 text-sm">⚠️ ${data.error}</td></tr>`;
                            return;
                        }
                        bmkgData = data;
                        renderBmkgDayTabs();
                        renderBmkgTable(0);
                        runBatteryAI();
                    })
                    .catch(err => {
                        refreshIcon.classList.remove('animate-spin');
                        console.error('BMKG fetch error:', err);
                        document.getElementById('bmkg_forecast_body').innerHTML =
                            `<tr><td colspan="7" class="px-4 py-8 text-center text-red-400 text-sm">⚠️ Gagal memuat data cuaca</td></tr>`;
                    });
            };

            function renderBmkgDayTabs() {
                if (!bmkgData?.data?.[0]?.cuaca) return;
                const days = bmkgData.data[0].cuaca;
                const tabsEl = document.getElementById('bmkg_day_tabs');
                tabsEl.innerHTML = '';

                const dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

                days.forEach((dayData, i) => {
                    if (!dayData.length) return;
                    const dt = new Date(dayData[0].local_datetime.replace(' ', 'T'));
                    const dayName = dayNames[dt.getDay()];
                    const dateStr = dt.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });

                    const btn = document.createElement('button');
                    btn.className = `px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 ${
                        i === bmkgSelectedDay
                            ? 'bg-sky-500 text-white shadow-sm'
                            : 'bg-gray-100 text-gray-500 hover:bg-sky-50 hover:text-sky-600'
                    }`;
                    btn.textContent = `${dayName}, ${dateStr}`;
                    btn.onclick = () => {
                        bmkgSelectedDay = i;
                        renderBmkgDayTabs();
                        renderBmkgTable(i);
                    };
                    tabsEl.appendChild(btn);
                });
            }

            function renderBmkgTable(dayIndex) {
                if (!bmkgData?.data?.[0]?.cuaca?.[dayIndex]) return;
                const items = bmkgData.data[0].cuaca[dayIndex];
                const tbody = document.getElementById('bmkg_forecast_body');

                // Show analysis date
                if (items[0]?.analysis_date) {
                    const ad = new Date(items[0].analysis_date);
                    document.getElementById('bmkg_analysis_date').textContent =
                        `Produksi data: ${ad.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })} ${ad.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })} UTC`;
                }

                document.getElementById('bmkg_last_update').textContent =
                    `Diperbarui: ${new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}`;

                let rows = '';
                items.forEach((item, idx) => {
                    const localDt = new Date(item.local_datetime.replace(' ', 'T'));
                    const timeStr = localDt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

                    // Cloud cover color
                    let tccColor = 'text-sky-600 bg-sky-50';
                    if (item.tcc > 70) tccColor = 'text-gray-600 bg-gray-100';
                    else if (item.tcc > 40) tccColor = 'text-amber-600 bg-amber-50';

                    // Temperature color
                    let tempColor = 'text-blue-600';
                    if (item.t >= 32) tempColor = 'text-red-600';
                    else if (item.t >= 28) tempColor = 'text-amber-600';

                    // Rain indicator
                    const isRain = item.weather >= 60;
                    const rainBg = isRain ? 'bg-blue-50/50' : '';

                    rows += `
                        <tr class="border-t border-gray-50 hover:bg-gray-50/50 transition-colors ${rainBg} ${idx % 2 === 0 ? '' : 'bg-gray-50/30'}">
                            <td class="px-3 py-2.5">
                                <span class="font-bold text-gray-800">${timeStr}</span>
                                <span class="text-[10px] text-gray-400 ml-1">WIB</span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <div class="flex flex-col items-center gap-0.5">
                                    <img src="${item.image}" alt="${item.weather_desc}" class="w-7 h-7" onerror="this.style.display='none'">
                                    <span class="text-[10px] font-medium text-gray-600 leading-tight">${item.weather_desc}</span>
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="text-lg font-bold ${tempColor}">${item.t}</span>
                                <span class="text-[10px] text-gray-400">°C</span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="font-semibold text-cyan-700">${item.hu}</span>
                                <span class="text-[10px] text-gray-400">%</span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-bold ${tccColor}">${item.tcc}%</span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="font-semibold text-gray-700">${item.ws}</span>
                                <span class="text-[10px] text-gray-400">km/j</span>
                                <span class="text-[10px] text-gray-400 ml-0.5">${item.wd}</span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="text-xs text-gray-600">${item.vs_text}</span>
                            </td>
                        </tr>
                    `;
                });

                tbody.innerHTML = rows;
            }

            // ─── SMART BATTERY AI ENGINE ──────────────────────────────────────────
            function runBatteryAI() {
                if (!bmkgData?.data?.[0]?.cuaca) return;

                const allForecasts = bmkgData.data[0].cuaca.flat();
                const PANEL_MAX_W = 50; // Max solar panel output (W) at 0% cloud

                // --- Calculate net power ---
                const netPower = currentEnergy.solar_w - currentEnergy.load_w;
                const netEl = document.getElementById('ai_net_power');
                netEl.textContent = `${netPower >= 0 ? '+' : ''}${netPower.toFixed(1)} W`;
                netEl.className = `text-sm font-bold ${netPower >= 0 ? 'text-emerald-600' : 'text-red-600'}`;

                // --- Predict tomorrow's solar output based on cloud cover ---
                // Get next 24h forecasts
                const now = new Date();
                const next24h = allForecasts.filter(f => {
                    const fdt = new Date(f.local_datetime.replace(' ', 'T'));
                    return fdt > now && fdt <= new Date(now.getTime() + 24 * 60 * 60 * 1000);
                });

                // Average cloud coverage for daytime hours (6am-6pm)
                const daytimeForecasts = next24h.filter(f => {
                    const hr = new Date(f.local_datetime.replace(' ', 'T')).getHours();
                    return hr >= 6 && hr < 18;
                });

                let avgCloudCover = 50;
                if (daytimeForecasts.length > 0) {
                    avgCloudCover = daytimeForecasts.reduce((sum, f) => sum + f.tcc, 0) / daytimeForecasts.length;
                }

                // Solar output degrades linearly with cloud cover
                const predictedSolarW = PANEL_MAX_W * (1 - avgCloudCover / 100);
                document.getElementById('ai_solar_forecast').textContent = `~${predictedSolarW.toFixed(1)} W`;

                // --- Check for incoming bad weather ---
                const badWeather = next24h.filter(f => f.weather >= 60); // rain codes
                const stormWeather = next24h.filter(f => f.weather >= 95); // thunderstorm
                const hasBadWeather = badWeather.length > 0;
                const hasStorm = stormWeather.length > 0;

                // --- Calculate battery endurance ---
                // If solar drops to predicted minimum, how long can battery last?
                const worstCaseLoad = currentEnergy.load_w;
                const worstCaseSolar = hasBadWeather ? predictedSolarW * 0.3 : predictedSolarW;
                const deficit = worstCaseLoad - worstCaseSolar;

                let enduranceHours = Infinity;
                if (deficit > 0) {
                    const batteryWh = currentEnergy.battery_wh * (currentEnergy.battery_pct / 100);
                    enduranceHours = batteryWh / deficit;
                }

                const enduranceEl = document.getElementById('ai_endurance');
                if (enduranceHours === Infinity) {
                    enduranceEl.textContent = '∞ (Surplus)';
                    enduranceEl.className = 'text-sm font-bold text-emerald-600';
                } else {
                    enduranceEl.textContent = `${enduranceHours.toFixed(1)} Jam`;
                    enduranceEl.className = `text-sm font-bold ${enduranceHours > 48 ? 'text-emerald-600' : enduranceHours > 12 ? 'text-amber-600' : 'text-red-600'}`;
                }

                // --- Determine AI Status ---
                const statusCard = document.getElementById('ai_status_card');
                const statusIcon = document.getElementById('ai_status_icon');
                const statusLabel = document.getElementById('ai_status_label');
                const statusDesc = document.getElementById('ai_status_desc');
                if (hasStorm || (hasBadWeather && enduranceHours < 12)) {
                    // CRITICAL - Power Saving Mode
                    statusCard.className = 'p-4 rounded-xl border-2 mb-4 transition-all duration-500 bg-red-50 border-red-300';
                    statusIcon.className = 'w-10 h-10 rounded-full flex items-center justify-center bg-red-500 text-white transition-all duration-500 shrink-0';
                    statusLabel.textContent = '⚡ Power Saving Mode';
                    statusLabel.className = 'text-sm font-bold text-red-700';
                    statusDesc.textContent = `Cuaca buruk terdeteksi! Baterai ${currentEnergy.battery_pct}% | Ketahanan: ${enduranceHours === Infinity ? '∞' : enduranceHours.toFixed(1)} jam`;
                    statusDesc.className = 'text-[11px] text-red-500 leading-tight';
                } else if (hasBadWeather || enduranceHours < 48) {
                    // WARNING - Battery Hoarding
                    statusCard.className = 'p-4 rounded-xl border-2 mb-4 transition-all duration-500 bg-amber-50 border-amber-300';
                    statusIcon.className = 'w-10 h-10 rounded-full flex items-center justify-center bg-amber-500 text-white transition-all duration-500 shrink-0';
                    statusLabel.textContent = '🔋 Menabung Baterai';
                    statusLabel.className = 'text-sm font-bold text-amber-700';
                    statusDesc.textContent = `Hujan diprediksi ${badWeather.length}x/24jam | Baterai ${currentEnergy.battery_pct}%`;
                    statusDesc.className = 'text-[11px] text-amber-500 leading-tight';
                } else {
                    // OPTIMAL - Normal Operation
                    statusCard.className = 'p-4 rounded-xl border-2 mb-4 transition-all duration-500 bg-emerald-50 border-emerald-300';
                    statusIcon.className = 'w-10 h-10 rounded-full flex items-center justify-center bg-emerald-500 text-white transition-all duration-500 shrink-0';
                    statusLabel.textContent = '✅ Optimal';
                    statusLabel.className = 'text-sm font-bold text-emerald-700';
                    statusDesc.textContent = `Cuaca cerah | Baterai ${currentEnergy.battery_pct}% | Surplus +${netPower.toFixed(1)}W`;
                    statusDesc.className = 'text-[11px] text-emerald-500 leading-tight';
                }
            }

            // ─── 6. OLLAMA AI INTEGRATION ─────────────────────────────────────
            let ollamaAvailable = false;

            function checkOllamaStatus() {
                fetch('{{ route("api.ollama.status") }}')
                    .then(r => r.json())
                    .then(data => {
                        const dot = document.getElementById('ollama_status_dot');
                        if (data.available) {
                            ollamaAvailable = true;
                            dot.className = 'h-2 w-2 rounded-full bg-emerald-500 transition-colors';
                        } else {
                            ollamaAvailable = false;
                            dot.className = 'h-2 w-2 rounded-full bg-red-400 transition-colors';
                        }
                    })
                    .catch(() => {
                        document.getElementById('ollama_status_dot').className = 'h-2 w-2 rounded-full bg-red-400';
                    });
            }

            // Collect current sensor readings from DOM
            function collectSensorData() {
                const getText = (id) => {
                    const el = document.getElementById(id);
                    return el ? el.textContent.replace(/[^\d.\-]/g, '') : null;
                };
                return {
                    water_temp: getText('val_water_temp') || null,
                    air_temp: getText('val_air_temp') || null,
                    humidity: getText('val_humidity') || null,
                    ph: getText('val_ph') || null,
                    tds: getText('val_tds') || null,
                    turbidity: getText('val_turbidity') || null,
                    light: getText('val_light') || null,
                };
            }

            // Build weather forecast summary for Ollama
            function collectWeatherForOllama() {
                if (!bmkgData?.data?.[0]?.cuaca) return {};

                const allForecasts = bmkgData.data[0].cuaca.flat();
                const now = new Date();
                const next24h = allForecasts.filter(f => {
                    const fdt = new Date(f.local_datetime.replace(' ', 'T'));
                    return fdt > now && fdt <= new Date(now.getTime() + 24 * 60 * 60 * 1000);
                });

                const location = bmkgData.data?.[0]?.lokasi
                    ? `${bmkgData.data[0].lokasi.desa}, ${bmkgData.data[0].lokasi.kecamatan}, ${bmkgData.data[0].lokasi.kotkab}`
                    : 'Cikarang Utara';

                return {
                    location: location,
                    forecasts: next24h.map(f => ({
                        time: f.local_datetime,
                        desc: f.weather_desc,
                        temp: f.t,
                        cloud: f.tcc,
                        humidity: f.hu,
                        wind_speed: f.ws,
                        wind_dir: f.wd,
                    })),
                };
            }

            window.runOllamaAnalysis = function() {
                if (!ollamaAvailable) return;

                const recEl = document.getElementById('ai_recommendation');
                const actionBox = document.getElementById('ai_action_box');

                // Show loading on recommendation box
                actionBox.className = 'p-3 rounded-lg border-2 border-violet-200 bg-violet-50 transition-all duration-500';
                recEl.innerHTML = '<span class="text-violet-600 font-medium flex items-center gap-2"><svg class="w-4 h-4 animate-spin text-violet-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg> AI sedang menganalisis data...</span>';
                recEl.className = 'text-xs text-violet-500 leading-relaxed';

                const payload = {
                    energy: {
                        solar_w: currentEnergy.solar_w,
                        load_w: currentEnergy.load_w,
                        battery_pct: currentEnergy.battery_pct,
                        battery_wh: currentEnergy.battery_wh,
                    },
                    weather: collectWeatherForOllama(),
                    sensors: collectSensorData(),
                };

                fetch('{{ route("api.ollama.analyze") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    if (!data.analysis) {
                        throw new Error("Respons tidak valid dari LLM");
                    }

                    const fullText = data.analysis;
                    const timestamp = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

                    // Determine color based on LLM output emoji
                    let boxClass = 'p-3 rounded-lg border-2 border-emerald-200 bg-emerald-50 transition-all duration-500';
                    let textClass = 'text-xs text-emerald-600 leading-relaxed max-h-96 overflow-y-auto block pr-1';
                    let headerClass = 'text-emerald-700 font-bold mb-1.5 inline-block tracking-wide';
                    let bodyColorClass = 'text-emerald-800';
                    
                    if (fullText.includes('🔴')) {
                        boxClass = 'p-3 rounded-lg border-2 border-red-200 bg-red-50 transition-all duration-500';
                        textClass = 'text-xs text-red-600 leading-relaxed max-h-96 overflow-y-auto block pr-1';
                        headerClass = 'text-red-700 font-bold mb-1.5 inline-block tracking-wide';
                        bodyColorClass = 'text-red-800';
                    } else if (fullText.includes('🟡') || fullText.includes('menabung') || fullText.toLowerCase().includes('hoarding')) {
                        boxClass = 'p-3 rounded-lg border-2 border-amber-200 bg-amber-50 transition-all duration-500';
                        textClass = 'text-xs text-amber-600 leading-relaxed max-h-96 overflow-y-auto block pr-1';
                        headerClass = 'text-amber-700 font-bold mb-1.5 inline-block tracking-wide';
                        bodyColorClass = 'text-amber-800';
                    }

                    // Update the Rekomendasi AI box with full Ollama response
                    actionBox.className = boxClass;
                    recEl.innerHTML = `
                        <span class="${headerClass}">Analisis AI (Diperbarui: ${timestamp})</span><br>
                        <span class="${bodyColorClass}">${fullText.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<b>$1</b>')}</span>
                    `;
                    recEl.className = textClass;
                })
                .catch(err => {
                    actionBox.className = 'p-3 rounded-lg border-2 border-red-200 bg-red-50 transition-all duration-500';
                    recEl.innerHTML = '<span class="text-red-600 font-bold">⚠️ Gagal menghubungi AI:</span><br><span class="text-red-500">' + err.message + '</span>';
                    recEl.className = 'text-xs leading-relaxed';
                });
            };

            // Auto-run Ollama after BMKG data is loaded
            let ollamaAutoRunScheduled = false;
            function scheduleOllamaAutoRun() {
                if (ollamaAutoRunScheduled) return;
                ollamaAutoRunScheduled = true;
                // Wait 5 seconds for BMKG to load, then auto-analyze
                setTimeout(() => {
                    if (ollamaAvailable && bmkgData) {
                        runOllamaAnalysis(true);
                    }
                    ollamaAutoRunScheduled = false;
                }, 5000);
            }

            // Auto-fetch on load
            fetchBmkgForecast();
            checkOllamaStatus();

            // After both BMKG and Ollama are ready, auto-run first analysis
            setTimeout(() => {
                if (ollamaAvailable && bmkgData) {
                    runOllamaAnalysis(true);
                } else {
                    // Retry after 10 more seconds if not ready yet
                    setTimeout(() => {
                        if (ollamaAvailable && bmkgData) runOllamaAnalysis(true);
                    }, 10000);
                }
            }, 8000);

            // Refresh BMKG + Ollama analysis every 30 minutes
            setInterval(() => {
                fetchBmkgForecast();
                // Run Ollama 10 seconds after BMKG refresh
                setTimeout(() => {
                    if (ollamaAvailable) runOllamaAnalysis(true);
                }, 10000);
            }, 30 * 60 * 1000);

            // Check Ollama status every 60 seconds
            setInterval(checkOllamaStatus, 60 * 1000);

            // ─── 7. PLANT DISEASE DETECTION ──────────────────────────────────────
            let plantScanData = null;
            let plantImageZoomed = false;

            window.fetchPlantScan = function() {
                fetch('{{ route("api.plant-scan.latest") }}')
                    .then(r => r.json())
                    .then(data => {
                        if (data.error || !data.status) {
                            document.getElementById('plant_scan_status_dot').className = 'h-2 w-2 rounded-full bg-gray-300 transition-colors';
                            return;
                        }
                        plantScanData = data;
                        renderPlantScan(data);
                        // Also refresh history dropdown
                        fetchPlantHistory();
                    })
                    .catch(err => {
                        console.error('Plant scan fetch error:', err);
                    });
            };

            // Fetch scan history list for dropdown
            function fetchPlantHistory() {
                fetch('{{ route("api.plant-scan.history") }}')
                    .then(r => r.json())
                    .then(items => {
                        const select = document.getElementById('plant_history_select');
                        const currentVal = select.value;
                        // Keep "Terbaru (Live)" as first option
                        select.innerHTML = '<option value="latest">📷 Terbaru (Live)</option>';
                        items.forEach(item => {
                            const emoji = item.status === 'healthy' ? '✅' : (item.status === 'critical' ? '🔴' : '🟡');
                            const opt = document.createElement('option');
                            opt.value = item.filename;
                            opt.textContent = `${emoji} ${item.label}`;
                            select.appendChild(opt);
                        });
                        // Restore selection if it was on a history item
                        if (currentVal !== 'latest') {
                            select.value = currentVal;
                        }
                    })
                    .catch(err => console.error('History fetch error:', err));
            }

            // Load a specific history scan
            window.loadPlantScanHistory = function(value) {
                if (value === 'latest') {
                    // Go back to live/latest
                    fetchPlantScan();
                    return;
                }
                fetch(`/api/plant-scan/view/${value}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) return;
                        renderPlantScan(data);
                    })
                    .catch(err => console.error('History load error:', err));
            };

            function renderPlantScan(data) {
                const statusCard = document.getElementById('plant_status_card');
                const statusIcon = document.getElementById('plant_status_icon');
                const statusLabel = document.getElementById('plant_status_label');
                const statusDesc = document.getElementById('plant_status_desc');
                const statusDot = document.getElementById('plant_scan_status_dot');
                const diseasesBox = document.getElementById('plant_diseases_box');
                const diseasesList = document.getElementById('plant_diseases_list');

                const s = data.status;

                // Update status card styling based on health status
                if (s.status === 'healthy') {
                    statusCard.className = 'p-4 rounded-xl border-2 transition-all duration-500 bg-emerald-50 border-emerald-300';
                    statusIcon.className = 'w-12 h-12 rounded-full flex items-center justify-center bg-emerald-500 text-white transition-all duration-500 shrink-0 text-xl';
                    statusIcon.textContent = '✅';
                    statusLabel.textContent = s.status_label;
                    statusLabel.className = 'text-sm font-bold text-emerald-700';
                    statusDesc.textContent = s.message;
                    statusDesc.className = 'text-[11px] text-emerald-500 leading-tight';
                    statusDot.className = 'h-2 w-2 rounded-full bg-emerald-500 transition-colors';
                    diseasesBox.classList.add('hidden');
                } else if (s.status === 'critical') {
                    statusCard.className = 'p-4 rounded-xl border-2 transition-all duration-500 bg-red-50 border-red-300';
                    statusIcon.className = 'w-12 h-12 rounded-full flex items-center justify-center bg-red-500 text-white transition-all duration-500 shrink-0 text-xl';
                    statusIcon.textContent = '🔴';
                    statusLabel.textContent = s.status_label;
                    statusLabel.className = 'text-sm font-bold text-red-700';
                    statusDesc.textContent = s.message;
                    statusDesc.className = 'text-[11px] text-red-500 leading-tight';
                    statusDot.className = 'h-2 w-2 rounded-full bg-red-500 transition-colors';
                } else {
                    // warning / mild
                    statusCard.className = 'p-4 rounded-xl border-2 transition-all duration-500 bg-amber-50 border-amber-300';
                    statusIcon.className = 'w-12 h-12 rounded-full flex items-center justify-center bg-amber-500 text-white transition-all duration-500 shrink-0 text-xl';
                    statusIcon.textContent = '🟡';
                    statusLabel.textContent = s.status_label;
                    statusLabel.className = 'text-sm font-bold text-amber-700';
                    statusDesc.textContent = s.message;
                    statusDesc.className = 'text-[11px] text-amber-500 leading-tight';
                    statusDot.className = 'h-2 w-2 rounded-full bg-amber-500 transition-colors';
                }

                // Render diseases list
                if (s.diseases && s.diseases.length > 0) {
                    diseasesBox.classList.remove('hidden');
                    let html = '';
                    s.diseases.forEach(d => {
                        const pct = d.accuracy;
                        let barColor = 'bg-red-500';
                        let textColor = 'text-red-700';
                        let bgColor = 'bg-red-50';
                        if (pct < 60) {
                            barColor = 'bg-amber-500';
                            textColor = 'text-amber-700';
                            bgColor = 'bg-amber-50';
                        }

                        html += `
                            <div class="p-2.5 rounded-lg ${bgColor} border border-gray-100">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-bold ${textColor}">🦠 ${d.name}</span>
                                    <span class="text-xs font-bold ${textColor}">${pct}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full ${barColor} rounded-full transition-all duration-700" style="width: ${pct}%"></div>
                                </div>
                            </div>
                        `;
                    });
                    diseasesList.innerHTML = html;
                } else {
                    diseasesBox.classList.add('hidden');
                }

                // Update counters
                document.getElementById('plant_total_detections').textContent = data.total_detections || '0';
                document.getElementById('plant_last_scan_time').textContent = data.timestamp_formatted || '--';

                // Show image
                if (data.image) {
                    const imgEl = document.getElementById('plant_scan_image');
                    const placeholder = document.getElementById('plant_image_placeholder');
                    imgEl.src = data.image + '?t=' + Date.now();
                    imgEl.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                }
            }

            window.togglePlantImageZoom = function() {
                const container = document.getElementById('plant_image_container');
                const img = document.getElementById('plant_scan_image');
                plantImageZoomed = !plantImageZoomed;
                if (plantImageZoomed) {
                    container.style.maxHeight = 'none';
                    img.style.maxHeight = 'none';
                    img.classList.add('ring-2', 'ring-lime-400');
                } else {
                    img.style.maxHeight = '450px';
                    img.classList.remove('ring-2', 'ring-lime-400');
                }
            };

            window.triggerManualCapture = function() {
                const btn = document.getElementById('btn_capture_now');
                const btnText = document.getElementById('btn_capture_text');
                
                // Disable button & show loading
                btn.disabled = true;
                btn.className = 'inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-400 text-white text-xs font-bold shadow-md cursor-wait border border-gray-500';
                btnText.textContent = 'Capturing...';

                fetch('{{ route("api.plant-scan.trigger") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                })
                .then(r => r.json())
                .then(data => {
                    btnText.textContent = '⏳ Memproses...';
                    // Tunggu scanner Python memproses (~8 detik)
                    setTimeout(() => {
                        fetchPlantScan();
                        // Reset tombol ke gradient hijau
                        btn.disabled = false;
                        btn.className = 'inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-lime-500 to-emerald-500 text-white text-xs font-bold hover:from-lime-600 hover:to-emerald-600 shadow-md transition-all hover:shadow-lg active:scale-95 border border-lime-600';
                        btnText.textContent = 'Capture Now';
                        // Reset dropdown ke terbaru
                        document.getElementById('plant_history_select').value = 'latest';
                    }, 8000);
                })
                .catch(err => {
                    console.error('Trigger error:', err);
                    btn.disabled = false;
                    btn.className = 'inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-lime-500 to-emerald-500 text-white text-xs font-bold hover:from-lime-600 hover:to-emerald-600 shadow-md transition-all hover:shadow-lg active:scale-95 border border-lime-600';
                    btnText.textContent = 'Capture Now';
                });
            };

            // Auto-fetch plant scan on load
            fetchPlantScan();

            // Refresh plant scan every 30 seconds (only when on "latest")
            setInterval(() => {
                if (document.getElementById('plant_history_select').value === 'latest') {
                    fetchPlantScan();
                }
            }, 30 * 1000);

            // ─── 8. SOLAR PANEL DATA FETCH ──────────────────────────────────────
            window.fetchSolarData = function() {
                fetch('{{ route("api.solar") }}')
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) return;
                        
                        // Update Main UI Elements
                        document.getElementById('val_solar_w').innerHTML = `${data.pv_power} <span class="text-sm font-medium text-gray-400">W</span>`;

                        document.getElementById('val_load_w').innerHTML = `${data.load_power} <span class="text-sm font-medium text-gray-400">W</span>`;
                        
                        document.getElementById('val_battery_pct').textContent = `${data.battery_percentage}%`;
                        document.getElementById('battery_bar_fill').style.width = `${data.battery_percentage}%`;
                        
                        document.getElementById('ai_net_power').textContent = `${data.net_power > 0 ? '+' : ''}${data.net_power} W`;

                        // Update currentEnergy for Smart Battery AI
                        if (typeof currentEnergy !== 'undefined') {
                            currentEnergy.solar_w = data.pv_power;
                            currentEnergy.load_w = data.load_power;
                            currentEnergy.battery_pct = data.battery_percentage;
                            
                            // Trigger AI Logic recalculation
                            if (typeof runBatteryAI === 'function' && bmkgData) {
                                runBatteryAI();
                            }
                        }
                    })
                    .catch(err => console.error('Solar data fetch error:', err));
            };

            // Initial fetch & auto-refresh
            fetchSolarData();
            setInterval(fetchSolarData, 5000); // Polling every 5 seconds for real-time feel

        });
    </script>

    {{-- ═══════ PUMP MODAL ═══════ --}}
    <div id="pump_modal_backdrop" class="pump-modal-backdrop" onclick="if(event.target===this) closePumpModal()">
        <div class="pump-modal">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 id="modal_pump_title" class="text-lg font-bold text-gray-900">Pump</h4>
                    <p id="modal_pump_subtitle" class="text-xs text-gray-400">-</p>
                </div>
                <button onclick="closePumpModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Durasi</label>
            <div class="flex items-center gap-2 mb-3">
                <input id="modal_duration_input" type="number" min="1" max="999" placeholder="Contoh: 6"
                    class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg text-lg font-bold text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
            </div>

            <div class="flex gap-2 mb-4">
                <button id="unit_sec" onclick="selectUnit('sec')" class="unit-btn flex-1 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 selected">Detik</button>
                <button id="unit_min" onclick="selectUnit('min')" class="unit-btn flex-1 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600">Menit</button>
            </div>

            <div id="modal_history_section" class="hidden mb-4">
                <p class="text-[11px] text-gray-400 uppercase tracking-wide font-semibold mb-1">Terakhir digunakan</p>
                <button onclick="useHistory()" class="w-full flex items-center justify-between p-2.5 rounded-lg bg-gray-50 border border-gray-200 hover:bg-blue-50 hover:border-blue-200 transition-all group">
                    <span id="modal_history_text" class="text-sm font-medium text-gray-700 group-hover:text-blue-700">-</span>
                    <span class="text-[10px] font-semibold text-gray-400 group-hover:text-blue-500 uppercase">Pakai</span>
                </button>
            </div>

            <div class="flex gap-2">
                <button onclick="closePumpModal()" class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">Batal</button>
                <button onclick="confirmPump()" class="flex-1 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-bold hover:bg-blue-700 shadow-sm transition-colors">Kirim</button>
            </div>
        </div>
    </div>

</x-app-layout>
