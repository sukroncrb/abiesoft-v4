<?php
/**
 * Elegant & Modern Exception Template
 * Fitur: Deteksi otomatis framework, variabel lingkungan,
 * pengelompokan stack trace berdasarkan Kategori (User, Abiesoft, Pihak Ke-3),
 * sistem dropdown, Live Code Viewer, dan proteksi variabel sensitif $_SERVER dengan PIN.
 */

// ========================================================
// HELPER UNTUK MEMBACA DAN MEM-PARSE FILE .ENV SECARA AMAN
// ========================================================
function loadEnvironmentVariables() {
    $env = [];
    
    // Ambil variabel dari environment bawaan sistem/server dahulu
    foreach ($_ENV as $k => $v) {
        $env[$k] = $v;
    }
    
    // Daftar lokasi pencarian file .env (menyesuaikan struktur framework)
    $paths = [
        __DIR__ . '/.env',
        __DIR__ . '/../.env',
        __DIR__ . '/../../.env',
        __DIR__ . '/../../../.env',
        $_SERVER['DOCUMENT_ROOT'] . '/.env',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path) && is_file($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    // Abaikan baris kosong atau baris komentar
                    if ($line === '' || strpos($line, '#') === 0) {
                        continue;
                    }
                    if (strpos($line, '=') !== false) {
                        list($name, $value) = explode('=', $line, 2);
                        $name = trim($name);
                        $value = trim($value);
                        // Bersihkan tanda kutip pembungkus jika ada
                        $value = trim($value, '"\'');
                        $env[$name] = $value;
                    }
                }
            }
            break; // Berhenti setelah menemukan file .env valid pertama
        }
    }

    // Fallback manual ke getenv jika beberapa variabel penting belum terisi
    $fallbackKeys = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'APIKEY', 'PIN', 'SECRET_KEY'];
    foreach ($fallbackKeys as $key) {
        if (!isset($env[$key])) {
            $val = getenv($key);
            if ($val !== false) {
                $env[$key] = $val;
            }
        }
    }

    return $env;
}

// Muat variabel lingkungan .env
$envVariables = loadEnvironmentVariables();

// Gabungkan variabel .env ke dalam $_SERVER jika belum diset oleh engine web server
foreach ($envVariables as $key => $val) {
    if (!isset($_SERVER[$key])) {
        $_SERVER[$key] = $val;
    }
}

// ===================================================
// 0. SECURE AJAX SOURCE CODE & SECURE ENV ENDPOINTS
// ===================================================
if (isset($_GET['action'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    // AJAX: Membaca isi file source code
    if ($_GET['action'] === 'get_source' && isset($_GET['file'])) {
        $fileToRead = $_GET['file'];
        $lineToRead = isset($_GET['line']) ? (int)$_GET['line'] : 1;
        
        // Keamanan Ketat: Hanya izinkan membaca file yang benar-benar ada di dalam Stack Trace Exception
        $allowedFiles = [];
        if (isset($exception)) {
            $allowedFiles[] = $exception->getFile();
            foreach ($exception->getTrace() as $trace) {
                if (isset($trace['file'])) {
                    $allowedFiles[] = $trace['file'];
                }
            }
        }
        
        $normalizedFileToRead = str_replace('\\', '/', realpath($fileToRead) ?: $fileToRead);
        $isAllowed = false;
        foreach ($allowedFiles as $allowed) {
            $normalizedAllowed = str_replace('\\', '/', realpath($allowed) ?: $allowed);
            if ($normalizedFileToRead === $normalizedAllowed) {
                $isAllowed = true;
                break;
            }
        }
        
        usleep(350000); // Simulasi delay untuk kelancaran visual loading skeleton

        if (!$isAllowed || !file_exists($fileToRead) || !is_file($fileToRead)) {
            echo json_encode(['error' => 'Akses ditolak atau file tidak ditemukan untuk dibaca.']);
            exit;
        }
        
        $lines = file($fileToRead);
        if ($lines === false) {
            echo json_encode(['error' => 'Gagal membuka & membaca isi file tersebut.']);
            exit;
        }
        
        $formattedLines = [];
        foreach ($lines as $index => $content) {
            $formattedLines[] = [
                'number' => $index + 1,
                'content' => rtrim($content)
            ];
        }
        
        echo json_encode([
            'success' => true,
            'file' => basename($fileToRead),
            'path' => $fileToRead,
            'line' => $lineToRead,
            'lines' => $formattedLines
        ]);
        exit;
    }

    // AJAX: Mengambil nilai sensitif ENV/SERVER dengan verifikasi PIN
    if ($_GET['action'] === 'get_env_value' && isset($_GET['key']) && isset($_GET['pin'])) {
        $keyToReveal = $_GET['key'];
        $inputPin = $_GET['pin'];
        
        // Cari nilai PIN yang sah dari konfigurasi $_SERVER atau .env
        $actualPin = $_SERVER['PIN'] ?? $envVariables['PIN'] ?? null;
        
        if (empty($actualPin)) {
            echo json_encode(['error' => 'Kunci pengaman "PIN" belum dikonfigurasi di berkas .env Anda.']);
            exit;
        }

        if ($inputPin !== $actualPin) {
            echo json_encode(['error' => 'PIN Keamanan salah! Akses ditolak.']);
            exit;
        }

        if (!isset($_SERVER[$keyToReveal])) {
            echo json_encode(['error' => 'Variabel tidak ditemukan di dalam konfigurasi server.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'value' => $_SERVER[$keyToReveal]
        ]);
        exit;
    }
}

// 1. Deteksi Otomatis Framework
$frameworkName = 'abiesoft v.4';
$frameworkVersion = null;

if (class_exists('\Illuminate\Foundation\Application')) {
    $frameworkName = 'Laravel';
    $frameworkVersion = \Illuminate\Foundation\Application::VERSION;
} elseif (class_exists('\Symfony\Component\HttpKernel\Kernel')) {
    $frameworkName = 'Symfony';
    $frameworkVersion = \Symfony\Component\HttpKernel\Kernel::VERSION;
} elseif (class_exists('\CodeIgniter\CodeIgniter')) {
    $frameworkName = 'CodeIgniter';
    $frameworkVersion = \CodeIgniter\CodeIgniter::CI_VERSION;
} elseif (defined('CODELIGNITER_VERSION')) {
    $frameworkName = 'CodeIgniter';
    $frameworkVersion = CODELIGNITER_VERSION;
} elseif (class_exists('\Slim\App')) {
    $frameworkName = 'Slim';
    $frameworkVersion = defined('\Slim\App::VERSION') ? \Slim\App::VERSION : '4.x';
} elseif (class_exists('\Yii')) {
    $frameworkName = 'Yii Framework';
    $frameworkVersion = \Yii::getVersion();
}

// 2. Informasi Lingkungan / Sistem
$environment = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
$phpVersion = PHP_VERSION;
$os = PHP_OS_FAMILY;
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Command Line Interface';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$statusCode = http_response_code() ?: 500;

// Helper untuk Keamanan Output (XSS Prevention)
function escapeHtml($value) {
    if (is_array($value) || is_object($value)) {
        return htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT));
    }
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Helper untuk Memformat Argumen Fungsi secara Aman (Mencegah Limitasi json_encode)
function formatTraceArgs($args) {
    if (empty($args) || !is_array($args)) {
        return '// Tidak ada argumen';
    }
    $formatted = [];
    foreach ($args as $index => $arg) {
        $paramName = '$arg' . ($index + 1);
        if (is_object($arg)) {
            $formatted[] = "{$paramName} = Objek(" . get_class($arg) . ")";
        } elseif (is_array($arg)) {
            $json = @json_encode($arg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
            if ($json === false || json_last_error() !== JSON_ERROR_NONE) {
                $formatted[] = "{$paramName} = Array[" . count($arg) . "] (Tidak dapat diserialisasikan)";
            } else {
                $formatted[] = "{$paramName} = " . $json;
            }
        } elseif (is_string($arg)) {
            $truncated = strlen($arg) > 150 ? substr($arg, 0, 150) . '...' : $arg;
            $formatted[] = "{$paramName} = String(\"" . htmlspecialchars($truncated, ENT_QUOTES, 'UTF-8') . "\")";
        } elseif (is_null($arg)) {
            $formatted[] = "{$paramName} = null";
        } elseif (is_bool($arg)) {
            $formatted[] = "{$paramName} = " . ($arg ? 'true' : 'false');
        } elseif (is_resource($arg)) {
            $formatted[] = "{$paramName} = Resource(" . get_resource_type($arg) . ")";
        } else {
            $formatted[] = "{$paramName} = " . (string)$arg;
        }
    }
    return implode("\n\n", $formatted);
}

// 3. Logika Pengelompokan Stack Trace Berdasarkan File
$groupedTraces = [];
foreach ($exception->getTrace() as $index => $trace) {
    $file = $trace['file'] ?? 'Internal PHP Call';
    
    if (!isset($groupedTraces[$file])) {
        $groupedTraces[$file] = [
            'file_path' => $file,
            'traces' => []
        ];
    }
    
    $groupedTraces[$file]['traces'][] = [
        'original_index' => $index + 1,
        'line' => $trace['line'] ?? '?',
        'class' => $trace['class'] ?? '',
        'type' => $trace['type'] ?? '',
        'function' => $trace['function'] ?? '',
        'args' => $trace['args'] ?? null
    ];
}

// 4. Pengelompokan Lanjutan Berdasarkan Klasifikasi Kode (User, Abiesoft, Pihak Ke-3)
$categorizedTraces = [
    'user' => [
        'name' => 'Kode Aplikasi Anda (User Code)',
        'description' => 'Log aktivitas kode yang Anda kembangkan di folder modules.',
        'color' => 'rose',
        'files' => []
    ],
    'abiesoft' => [
        'name' => 'Sistem Utama Abiesoft',
        'description' => 'Aktivitas engine inti dari framework Abiesoft.',
        'color' => 'indigo',
        'files' => []
    ],
    'vendor' => [
        'name' => 'Pihak Ke-3 (Vendor) & Core PHP',
        'description' => 'Library external (composer packages) atau internal execution PHP.',
        'color' => 'slate',
        'files' => []
    ]
];

foreach ($groupedTraces as $filePath => $group) {
    $normalizedPath = str_replace('\\', '/', $filePath);
    
    // Aturan filter Kode buatan User sendiri:
    $isUserCode = (strpos($normalizedPath, 'src/modules/') !== false) 
                  && (strpos($normalizedPath, 'handler.go') === false) 
                  && (strpos($normalizedPath, '/templates/') === false);

    // Aturan filter Sistem Abiesoft:
    $isAbiesoftSystem = (stripos($normalizedPath, 'abiesoft') !== false);

    if ($isUserCode) {
        $categorizedTraces['user']['files'][$filePath] = $group;
    } elseif ($isAbiesoftSystem) {
        $categorizedTraces['abiesoft']['files'][$filePath] = $group;
    } else {
        $categorizedTraces['vendor']['files'][$filePath] = $group;
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exception: <?= escapeHtml($exception->getMessage()) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Desain scrollbar kustom agar serasi dengan tema gelap */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #0f172a;
        }
        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 font-sans antialiased min-h-screen flex flex-col selection:bg-rose-500/30 selection:text-rose-200 relative">

    <div class="flex-grow max-w-7xl w-full mx-auto p-4 md:p-8 space-y-6">
        
        <!-- Header Utama / Alert Error -->
        <header class="bg-slate-900/60 border border-rose-500/30 rounded-2xl p-6 md:p-8 shadow-2xl backdrop-blur-md relative overflow-hidden">
            <!-- Background Accent Glow -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-rose-500/10 rounded-full blur-3xl -mr-20 -mt-20"></div>
            
            <div class="relative z-10">
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="text-xs font-bold uppercase tracking-wider bg-rose-500/20 text-rose-400 border border-rose-500/30 px-3 py-1 rounded-full">
                        <?= escapeHtml(get_class($exception)) ?>
                    </span>
                    <span class="text-xs font-semibold bg-slate-800 text-slate-400 px-3 py-1 rounded-full">
                        HTTP Status: <?= $statusCode ?>
                    </span>
                </div>
                
                <h1 class="text-2xl md:text-4xl font-extrabold text-rose-400 leading-tight tracking-tight break-words">
                    <?= escapeHtml($exception->getMessage()) ?>
                </h1>
                
                <div class="mt-4 flex flex-wrap items-center text-sm text-slate-400 gap-2 font-mono bg-slate-950/50 p-3 rounded-lg border border-slate-800 justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-slate-500">File Utama:</span>
                        <span class="text-slate-200 break-all"><?= escapeHtml($exception->getFile()) ?></span>
                        <span class="text-slate-500">pada baris</span>
                        <span class="text-rose-400 font-bold bg-rose-500/10 px-2 py-0.5 rounded"><?= $exception->getLine() ?></span>
                    </div>
                    <button onclick="openCodeViewer(<?= escapeHtml(json_encode($exception->getFile())) ?>, <?= (int)$exception->getLine() ?>)" class="flex items-center gap-1.5 text-xs bg-rose-500/20 hover:bg-rose-500 text-rose-300 hover:text-white px-3 py-1.5 rounded-lg transition border border-rose-500/30 font-sans font-semibold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                        Intip Kode Pemicu
                    </button>
                </div>
            </div>
        </header>

        <!-- Informasi Sistem & Diagnostic Cards -->
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Info Framework -->
            <div class="bg-slate-900/40 border border-slate-800 p-4 rounded-xl shadow-lg flex items-center gap-3">
                <div class="p-3 bg-indigo-500/10 text-indigo-400 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Framework</p>
                    <p class="text-sm font-semibold text-slate-200">
                        <?= $frameworkName ?> <span class="text-slate-400 text-xs"><?= $frameworkVersion ? 'v' . $frameworkVersion : '' ?></span>
                    </p>
                </div>
            </div>

            <!-- Versi PHP -->
            <div class="bg-slate-900/40 border border-slate-800 p-4 rounded-xl shadow-lg flex items-center gap-3">
                <div class="p-3 bg-blue-500/10 text-blue-400 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Versi PHP</p>
                    <p class="text-sm font-semibold text-slate-200">PHP <?= $phpVersion ?></p>
                </div>
            </div>

            <!-- Environment / Lingkungan -->
            <div class="bg-slate-900/40 border border-slate-800 p-4 rounded-xl shadow-lg flex items-center gap-3">
                <div class="p-3 bg-emerald-500/10 text-emerald-400 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 002 2h2m4.6-2.876A9 9 0 1111.24 3.047l.248-.005V5"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Environment</p>
                    <p class="text-sm font-semibold capitalize text-slate-200"><?= $environment ?></p>
                </div>
            </div>

            <!-- Server OS -->
            <div class="bg-slate-900/40 border border-slate-800 p-4 rounded-xl shadow-lg flex items-center gap-3">
                <div class="p-3 bg-amber-500/10 text-amber-400 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">OS & Web Server</p>
                    <div class="text-sm font-semibold text-slate-200 truncate" title="<?= $os ?> / <?= $serverSoftware ?>">
                        <div class="w-full h-[30px] overflow-hidden"><?= $os ?> <span class="text-slate-400 text-xs">/ <?= explode('/', $serverSoftware)[0] ?></span></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Area Navigasi Diagnostik Utama -->
        <main class="bg-slate-900/50 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl">
            <!-- Bar Tab Navigasi -->
            <div class="flex border-b border-slate-800 bg-slate-900/80 px-4 overflow-x-auto">
                <button onclick="switchTab('stacktrace')" id="tab-stacktrace" class="tab-btn whitespace-nowrap px-4 py-3 text-sm font-semibold border-b-2 border-rose-500 text-rose-400 focus:outline-none transition">
                    Stack Trace
                </button>
                <button onclick="switchTab('request')" id="tab-request" class="tab-btn whitespace-nowrap px-4 py-3 text-sm font-medium border-b-2 border-transparent text-slate-400 hover:text-slate-200 focus:outline-none transition">
                    Detail Request
                </button>
                <button onclick="switchTab('servervars')" id="tab-servervars" class="tab-btn whitespace-nowrap px-4 py-3 text-sm font-medium border-b-2 border-transparent text-slate-400 hover:text-slate-200 focus:outline-none transition">
                    Informasi Server
                </button>
            </div>

            <!-- Konten Panel Tab -->
            <div class="p-4 md:p-6">
                
                <!-- Tab: Stack Trace Terkelompok -->
                <div id="content-stacktrace" class="tab-content block space-y-6">
                    
                    <!-- Eksekusi Utama (Garis Teratas Triger Error) -->
                    <div class="p-4 bg-rose-950/10 border-l-4 border-rose-500/80 hover:bg-rose-950/20 transition rounded-md flex justify-between items-center">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-rose-400 font-bold text-xs bg-rose-500/10 px-2 py-0.5 rounded">ALUR EKSEKUSI UTAMA</span>
                            </div>
                            <p class="text-slate-200 mt-2 font-mono text-xs md:text-sm break-all">
                                <?= escapeHtml($exception->getFile()) ?> : <span class="text-rose-400 font-semibold"><?= $exception->getLine() ?></span>
                            </p>
                        </div>
                    </div>

                    <!-- Iterasi Kategori (User -> Abiesoft -> Vendor) -->
                    <?php 
                    $globalGroupIndex = 0; 
                    $isFirstFileOverall = true; 
                    ?>
                    <?php foreach ($categorizedTraces as $categoryKey => $category): ?>
                        <div class="space-y-3">
                            <!-- Header Kategori Intuitif -->
                            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-<?= $category['color'] ?>-500 shadow-lg shadow-<?= $category['color'] ?>-500/50"></span>
                                    <h3 class="text-sm font-bold tracking-wider text-slate-300 font-sans uppercase">
                                        <?= $category['name'] ?>
                                    </h3>
                                </div>
                                <span class="text-xs bg-slate-900 border border-slate-800 text-slate-400 px-2.5 py-0.5 rounded-full font-mono">
                                    <?= count($category['files']) ?> File Terlibat
                                </span>
                            </div>
                            
                            <p class="text-xs text-slate-500 -mt-1 select-none"><?= $category['description'] ?></p>

                            <!-- List File di dalam Kategori Terkait -->
                            <?php if (!empty($category['files'])): ?>
                                <div class="space-y-3 mt-2">
                                    <?php foreach ($category['files'] as $filePath => $group): ?>
                                        <?php 
                                        $globalGroupIndex++; 
                                        $isInitiallyOpen = $isFirstFileOverall; 
                                        $isFirstFileOverall = false;
                                        ?>
                                        <div class="bg-slate-900/40 border border-slate-800/80 rounded-xl overflow-hidden transition duration-200">
                                            
                                            <!-- Tombol Dropdown / Header File -->
                                            <button onclick="toggleFileGroup('group-<?= $globalGroupIndex ?>')" class="w-full flex items-center justify-between p-4 bg-slate-900/60 hover:bg-slate-800/85 transition text-left focus:outline-none">
                                                <div class="flex items-center gap-3 min-w-0 pr-4">
                                                    <!-- Icon File Sesuai Kategori -->
                                                    <?php if ($categoryKey === 'user'): ?>
                                                        <svg class="w-5 h-5 text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                                                    <?php elseif ($categoryKey === 'abiesoft'): ?>
                                                        <svg class="w-5 h-5 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                                    <?php else: ?>
                                                        <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                                    <?php endif; ?>

                                                    <div class="truncate">
                                                        <span class="text-slate-500 text-[10px] block font-sans uppercase tracking-wider">Nama File</span>
                                                        <span class="text-slate-200 font-mono text-xs md:text-sm break-all font-semibold"><?= escapeHtml($filePath) ?></span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 flex-shrink-0">
                                                    <span class="text-[11px] font-semibold bg-slate-800 text-slate-300 border border-slate-700/60 px-2.5 py-0.5 rounded-full font-mono">
                                                        <?= count($group['traces']) ?> Request
                                                    </span>
                                                    <!-- Chevron Dropdown Icon -->
                                                    <svg id="chevron-group-<?= $globalGroupIndex ?>" class="w-5 h-5 text-slate-500 transition-transform duration-250 transform <?= $isInitiallyOpen ? 'rotate-180' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </button>
                                            
                                            <!-- Isi Dropdown List Error di Dalam File Terkait -->
                                            <div id="group-<?= $globalGroupIndex ?>" class="<?= $isInitiallyOpen ? 'block' : 'hidden' ?> border-t border-slate-800/65 bg-slate-950/20 p-4">
                                                <div class="relative border-l-2 border-slate-800/80 pl-4 ml-2 space-y-5">
                                                    <?php foreach ($group['traces'] as $trace): ?>
                                                        <div class="relative">
                                                            <!-- Indikator Bulat Timeline -->
                                                            <div class="absolute -left-[22px] top-1.5 w-2.5 h-2.5 rounded-full bg-<?= $category['color'] ?>-500 border-2 border-slate-950 shadow-sm"></div>
                                                            
                                                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                                                <div class="font-mono text-xs md:text-sm">
                                                                    <div class="flex items-center gap-2 flex-wrap">
                                                                        <span class="text-blue-500 font-bold text-[10px] bg-blue-500/10 px-2 py-0.5 rounded">STACK #<?= $trace['original_index'] ?></span>
                                                                        <span class="text-indigo-400 font-bold break-all">
                                                                            <?php if (!empty($trace['class'])): ?>
                                                                                <?= escapeHtml($trace['class'] . $trace['type']) ?><?= escapeHtml($trace['function']) ?>()
                                                                            <?php elseif (!empty($trace['function'])): ?>
                                                                                <?= escapeHtml($trace['function']) ?>()
                                                                            <?php else: ?>
                                                                                <span class="text-slate-500 italic">[Kode Global / Penyisipan File]</span>
                                                                            <?php endif; ?>
                                                                        </span>
                                                                    </div>
                                                                    <div class="text-slate-400 text-xs mt-1.5">
                                                                        Pemicu Error pada Baris: <span class="text-rose-400 font-bold bg-rose-500/5 px-1.5 py-0.5 rounded border border-rose-500/10"><?= $trace['line'] ?></span>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- Tombol Tampil Detail / Intip Kode -->
                                                                <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
                                                                    <?php if (isset($trace['args']) && !empty($trace['args'])): ?>
                                                                        <button onclick="toggleArgs('args-<?= $trace['original_index'] ?>')" class="text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold px-2.5 py-1 rounded transition border border-slate-700 font-sans">
                                                                            Detail Argumen
                                                                        </button>
                                                                    <?php endif; ?>
                                                                    
                                                                    <!-- Tombol Live Code View Popup -->
                                                                    <?php if ($filePath !== 'Internal PHP Call' && $trace['line'] !== '?'): ?>
                                                                        <button onclick="openCodeViewer(<?= htmlspecialchars(json_encode($filePath), ENT_QUOTES, 'UTF-8') ?>, <?= (int)$trace['line'] ?>)" class="flex items-center gap-1 text-[11px] bg-slate-800 hover:bg-rose-500/20 hover:text-rose-400 hover:border-rose-500/30 text-rose-300 font-semibold px-2.5 py-1 rounded transition border border-slate-700 font-sans">
                                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                                                            </svg>
                                                                            Lihat Kode
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>

                                                            <!-- Panel Preview Data Argumen (Diproses Secara Aman) -->
                                                            <?php if (isset($trace['args']) && !empty($trace['args'])): ?>
                                                                <div id="args-<?= $trace['original_index'] ?>" class="hidden mt-3 bg-slate-950 p-4 border border-slate-800 rounded-lg font-mono text-xs overflow-x-auto">
                                                                    <span class="text-slate-500 mb-2 block font-sans select-none">// Parameter Argumen Fungsi:</span>
                                                                    <pre class="text-amber-400 whitespace-pre-wrap"><?= formatTraceArgs($trace['args']) ?></pre>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <!-- State Kosong pada Kategori Terkait -->
                                <div class="p-6 border border-dashed border-slate-800/80 rounded-xl text-center text-slate-600 font-mono text-xs">
                                    Tidak ada record eksekusi pada kategori ini.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                </div>

                <!-- Tab: Detail Permintaan (HTTP Request) -->
                <div id="content-request" class="tab-content hidden">
                    <div class="space-y-6">
                        <!-- Request Overview Card -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800">
                                <span class="text-slate-500 text-[10px] font-mono uppercase tracking-wider">URI Permintaan</span>
                                <p class="text-sm font-semibold font-mono text-indigo-400 mt-1"><?= $requestMethod ?> <?= escapeHtml($requestUri) ?></p>
                            </div>
                            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800">
                                <span class="text-slate-500 text-[10px] font-mono uppercase tracking-wider">User Agent</span>
                                <p class="text-sm font-semibold font-mono text-slate-300 mt-1 truncate" title="<?= escapeHtml($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') ?>">
                                    <?= escapeHtml($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') ?>
                                </p>
                            </div>
                        </div>

                        <!-- Variabel GET -->
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2 font-mono">Parameter Query ($_GET)</h3>
                            <div class="bg-slate-950 border border-slate-800 rounded-xl overflow-hidden">
                                <?php if (!empty($_GET)): ?>
                                    <table class="w-full text-left font-mono text-xs divide-y divide-slate-800">
                                        <thead class="bg-slate-900/60 text-slate-400">
                                            <tr>
                                                <th class="p-3">Kunci (Key)</th>
                                                <th class="p-3">Nilai (Value)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-800">
                                            <?php foreach ($_GET as $key => $val): ?>
                                                <tr class="hover:bg-slate-900/40">
                                                    <td class="p-3 text-rose-400 font-semibold"><?= escapeHtml($key) ?></td>
                                                    <td class="p-3 text-slate-300 break-all"><?= escapeHtml($val) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="p-4 text-center text-slate-500 text-sm font-mono">Tidak ada parameter GET yang dikirim</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Variabel POST -->
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2 font-mono">Parameter Body ($_POST)</h3>
                            <div class="bg-slate-950 border border-slate-800 rounded-xl overflow-hidden">
                                <?php if (!empty($_POST)): ?>
                                    <table class="w-full text-left font-mono text-xs divide-y divide-slate-800">
                                        <thead class="bg-slate-900/60 text-slate-400">
                                            <tr>
                                                <th class="p-3">Kunci (Key)</th>
                                                <th class="p-3">Nilai (Value)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-800">
                                            <?php foreach ($_POST as $key => $val): ?>
                                                <tr class="hover:bg-slate-900/40">
                                                    <td class="p-3 text-rose-400 font-semibold"><?= escapeHtml($key) ?></td>
                                                    <td class="p-3 text-slate-300 break-all"><?= escapeHtml($val) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="p-4 text-center text-slate-500 text-sm font-mono">Tidak ada parameter POST yang dikirim</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Variabel Server & Lingkungan Terproteksi -->
                <div id="content-servervars" class="tab-content hidden space-y-8">
                    
                    <!-- UNIFIED SERVER & ENVIRONMENT VARIABLES TABLE -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300 font-mono flex items-center gap-2">
                                <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Variabel Lingkungan Server ($_SERVER)
                            </h3>
                            <span class="text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/20 px-2.5 py-0.5 rounded-full font-sans">
                                Proteksi PIN Aktif
                            </span>
                        </div>
                        
                        <p class="text-xs text-slate-500">
                            Menampilkan seluruh variabel lingkungan aktif dari array <code class="text-slate-400">$_SERVER</code> dan berkas <code class="text-slate-400">.env</code>. Variabel yang mengandung data sensitif (<code class="text-slate-400">DB_</code>, <code class="text-slate-400">APIKEY</code>, <code class="text-slate-400">PIN</code>, dan <code class="text-slate-400">SECRET_</code>) disembunyikan secara otomatis. Klik tombol mata untuk memasukkan PIN keamanan.
                        </p>

                        <div class="bg-slate-950 border border-slate-800 rounded-xl overflow-hidden">
                            <table class="w-full text-left font-mono text-xs divide-y divide-slate-800">
                                <thead class="bg-slate-900/60 text-slate-400">
                                    <tr>
                                        <th class="p-3 w-1/3">Kunci Parameter</th>
                                        <th class="p-3">Nilai (Value)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800">
                                    <?php foreach ($_SERVER as $key => $val): ?>
                                        <?php 
                                        // Deteksi kunci sensitif (DB_, APIKEY, PIN, SECRET_ di awal kalimat)
                                        $isSensitive = preg_match('/^(DB_|APIKEY|PIN|SECRET_)/i', $key);
                                        ?>
                                        <tr class="hover:bg-slate-900/30">
                                            <td class="p-3 text-slate-400 border-r border-slate-800 break-all font-semibold"><?= escapeHtml($key) ?></td>
                                            <td class="p-3">
                                                <?php if ($isSensitive): ?>
                                                    <div class="flex items-center justify-between gap-3">
                                                        <!-- Tempat penampung nilai tersamar -->
                                                        <span id="env-val-<?= escapeHtml($key) ?>" class="text-slate-500 select-all tracking-wider font-semibold font-mono">••••••••••••</span>
                                                        
                                                        <!-- Tombol Interaktif "Mata" -->
                                                        <button id="env-btn-<?= escapeHtml($key) ?>" onclick="requestRevealEnv('<?= escapeHtml($key) ?>')" class="p-1.5 bg-slate-900 hover:bg-slate-800 text-rose-400 hover:text-rose-300 rounded-lg border border-slate-800 transition flex-shrink-0" title="Tampilkan Value Sensitif">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-blue-400 break-all"><?= escapeHtml($val) ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- ==============================================
    EXTRA PREMIUM UI: LIVE SOURCE CODE VIEWER POPUP
    ============================================== -->
    <div id="code-viewer-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6 bg-slate-950/80 backdrop-blur-sm transition-opacity duration-300">
        <!-- Backdrop Close Click -->
        <div onclick="closeCodeViewer()" class="absolute inset-0 cursor-default"></div>
        
        <!-- Modal Content Box -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-5xl h-[85vh] flex flex-col shadow-2xl relative z-10 overflow-hidden transform scale-95 transition-transform duration-300">
            <!-- Modal Header -->
            <div class="p-4 border-b border-slate-800 bg-slate-900/80 backdrop-blur-md flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <span class="text-slate-500 text-[10px] font-mono uppercase tracking-wider block">Intip Berkas Kode (Hanya-Baca)</span>
                    <h3 id="code-modal-filename" class="text-slate-200 font-semibold font-mono text-xs md:text-sm truncate pr-2">NamaFile.php</h3>
                </div>
                <div class="flex items-center gap-2">
                    <span id="code-modal-line-badge" class="hidden text-xs bg-rose-500/20 text-rose-300 border border-rose-500/30 px-2.5 py-1 rounded-md font-mono">
                        Baris: -
                    </span>
                    <button onclick="closeCodeViewer()" class="p-2 text-slate-400 hover:text-slate-200 hover:bg-slate-800 rounded-lg transition" title="Tutup">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body Container (Scrollable) -->
            <div class="flex-1 overflow-auto bg-slate-950 p-4 md:p-6 relative">
                
                <!-- PRESET 1: LOADING SKELETON LAYER -->
                <div id="code-modal-loading" class="absolute inset-0 bg-slate-950 z-20 flex flex-col p-6 space-y-4 animate-pulse">
                    <div class="h-4 bg-slate-900 rounded-lg w-1/4"></div>
                    <div class="space-y-2 pt-4">
                        <div class="grid grid-cols-12 gap-4">
                            <div class="h-3 bg-slate-900 rounded col-span-1"></div>
                            <div class="h-3 bg-slate-900 rounded col-span-8"></div>
                        </div>
                        <div class="grid grid-cols-12 gap-4">
                            <div class="h-3 bg-slate-900 rounded col-span-1"></div>
                            <div class="h-3 bg-slate-900 rounded col-span-11"></div>
                        </div>
                        <div class="grid grid-cols-12 gap-4 bg-rose-950/20 py-1.5 rounded-lg">
                            <div class="h-3 bg-rose-500/30 rounded col-span-1"></div>
                            <div class="h-3 bg-rose-500/30 rounded col-span-10"></div>
                        </div>
                        <div class="grid grid-cols-12 gap-4">
                            <div class="h-3 bg-slate-900 rounded col-span-1"></div>
                            <div class="h-3 bg-slate-900 rounded col-span-9"></div>
                        </div>
                        <div class="grid grid-cols-12 gap-4">
                            <div class="h-3 bg-slate-900 rounded col-span-1"></div>
                            <div class="h-3 bg-slate-900 rounded col-span-6"></div>
                        </div>
                        <div class="grid grid-cols-12 gap-4">
                            <div class="h-3 bg-slate-900 rounded col-span-1"></div>
                            <div class="h-3 bg-slate-900 rounded col-span-11"></div>
                        </div>
                    </div>
                    <!-- Spinner Tengah -->
                    <div class="absolute inset-0 flex flex-col items-center justify-center space-y-3 bg-slate-950/40">
                        <svg class="animate-spin h-8 w-8 text-rose-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-xs text-slate-400 font-mono">Membuka berkas kode di server...</span>
                    </div>
                </div>

                <!-- PRESET 2: ERROR LAYER -->
                <div id="code-modal-error" class="hidden absolute inset-0 bg-slate-950 z-20 flex flex-col items-center justify-center p-6 text-center">
                    <div class="p-3 bg-rose-500/10 text-rose-500 rounded-full mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h4 class="text-sm font-bold text-slate-200">Gagal Menampilkan Kode</h4>
                    <p id="code-modal-error-msg" class="text-xs text-slate-400 font-mono mt-1 max-w-md">Kesalahan tidak diketahui.</p>
                </div>

                <!-- PRESET 3: REAL CODE VIEW (RENDERED LINES) -->
                <div id="code-view-container" class="font-mono text-xs md:text-sm select-text w-full h-full">
                    <div id="code-view-lines" class="space-y-0.5"></div>
                </div>

            </div>

            <!-- Modal Footer Info -->
            <div class="p-3 bg-slate-900/60 border-t border-slate-800 text-[10px] text-slate-500 font-mono flex flex-wrap justify-between gap-2">
                <span>Gunakan Mousewheel untuk menjelajah file lengkap.</span>
                <span id="code-modal-full-path" class="truncate max-w-xs md:max-w-md">Path: -</span>
            </div>
        </div>
    </div>

    <!-- =======================================================
    SECURITY SECURITY: PREMIUM COMPACT PIN VERIFICATION MODAL
    ======================================================= -->
    <div id="pin-verification-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm">
        <div onclick="closePinVerification()" class="absolute inset-0"></div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md p-6 shadow-2xl relative z-10 transform scale-95 transition-all duration-200">
            
            <!-- Header Modal Keamanan -->
            <div class="flex items-center gap-3 border-b border-slate-800 pb-4 mb-4">
                <div class="p-2 bg-rose-500/10 text-rose-400 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <div>
                    <h3 class="text-slate-200 font-bold text-sm">Verifikasi Keamanan</h3>
                    <p class="text-xs text-slate-500 font-sans">Variabel lingkungan terproteksi</p>
                </div>
            </div>

            <!-- Body Input PIN -->
            <div class="space-y-4 font-sans">
                <div>
                    <label for="security-pin-input" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">PIN Pengaman (.env)</label>
                    <input type="password" id="security-pin-input" placeholder="••••" maxlength="12" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-center text-lg tracking-widest font-mono text-rose-400 focus:outline-none focus:border-rose-500/60 transition" />
                </div>
                
                <!-- Label Pesan Kesalahan / Error Log -->
                <div id="pin-error-container" class="hidden p-3 bg-rose-500/10 border border-rose-500/20 rounded-xl flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span id="pin-error-msg" class="text-xs text-rose-400 font-mono font-medium">Kesalahan autentikasi.</span>
                </div>
            </div>

            <!-- Tombol Aksi Modal -->
            <div class="flex items-center justify-end gap-3 mt-6 border-t border-slate-800 pt-4 font-sans">
                <button onclick="closePinVerification()" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-slate-200 bg-slate-800/40 hover:bg-slate-800 rounded-lg transition">
                    Batal
                </button>
                <button id="pin-submit-button" onclick="submitSecurityPin()" class="px-5 py-2 text-xs font-semibold bg-rose-500 hover:bg-rose-600 text-white rounded-lg transition flex items-center gap-2 shadow-lg shadow-rose-500/10">
                    <span id="pin-btn-text">Verifikasi</span>
                    <svg id="pin-btn-spinner" class="hidden animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Halaman Footer -->
    <footer class="w-full text-center py-6 border-t border-slate-900 text-[11px] text-slate-600 font-mono text-sm mb-8 select-none">
        Diagnostik Error @abiesoft &bull; Waktu Saat Ini: <?php echo date('Y-m-d H:i:s'); ?>
    </footer>

    <!-- Skrip Interaktif Navigasi -->
    <script>
        // Fungsi untuk berpindah tab
        function switchTab(tabId) {
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.replace('block', 'hidden'));

            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => {
                btn.classList.remove('border-rose-500', 'text-rose-400', 'font-semibold');
                btn.classList.add('border-transparent', 'text-slate-400', 'font-medium');
            });

            document.getElementById('content-' + tabId).classList.replace('hidden', 'block');
            const targetBtn = document.getElementById('tab-' + tabId);
            targetBtn.classList.remove('border-transparent', 'text-slate-400', 'font-medium');
            targetBtn.classList.add('border-rose-500', 'text-rose-400', 'font-semibold');
        }

        // Fungsi untuk menutup/membuka detail error pada file tertentu (Dropdown)
        function toggleFileGroup(groupId) {
            const element = document.getElementById(groupId);
            const chevron = document.getElementById('chevron-' + groupId);
            
            if (element.classList.contains('hidden')) {
                element.classList.replace('hidden', 'block');
                chevron.classList.add('rotate-180');
            } else {
                element.classList.replace('block', 'hidden');
                chevron.classList.remove('rotate-180');
            }
        }

        // Fungsi untuk membuka/menutup info argumen pemanggilan (Menerima-full ID element)
        function toggleArgs(elementId) {
            const el = document.getElementById(elementId);
            if (el) {
                if (el.classList.contains('hidden')) {
                    el.classList.replace('hidden', 'block');
                } else {
                    el.classList.replace('block', 'hidden');
                }
            }
        }

        // ===========================================
        // INTERACTIVE POPUP CODE VIEWER HANDLERS (JS)
        // ===========================================
        let activeScrollTimeout = null;

        function openCodeViewer(filePath, lineTarget) {
            const modal = document.getElementById('code-viewer-modal');
            const loadingLayer = document.getElementById('code-modal-loading');
            const errorLayer = document.getElementById('code-modal-error');
            const codeContainer = document.getElementById('code-view-container');
            const linesWrapper = document.getElementById('code-view-lines');
            const titleEl = document.getElementById('code-modal-filename');
            const fullPathEl = document.getElementById('code-modal-full-path');
            const lineBadge = document.getElementById('code-modal-line-badge');

            // Reset UI States
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Kunci scroll halaman utama
            loadingLayer.classList.remove('hidden');
            errorLayer.classList.add('hidden');
            codeContainer.classList.add('hidden');
            linesWrapper.innerHTML = '';
            
            titleEl.textContent = "Sedang Membaca...";
            fullPathEl.textContent = filePath;
            lineBadge.classList.add('hidden');

            // Eksekusi AJAX Request ke Endpoint PHP Aman di Atas
            const url = `?action=get_source&file=${encodeURIComponent(filePath)}&line=${lineTarget}`;
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Gagal melakukan permintaan data.");
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    // Sembunyikan loading, perbarui header info
                    loadingLayer.classList.add('hidden');
                    codeContainer.classList.remove('hidden');
                    titleEl.textContent = data.file;
                    fullPathEl.textContent = data.path;
                    
                    lineBadge.textContent = "Baris: " + data.line;
                    lineBadge.classList.remove('hidden');

                    // Bangun & Render Elemen Baris Kode secara Dinamis
                    data.lines.forEach(lineObj => {
                        const isTarget = (lineObj.number === parseInt(data.line));
                        
                        const row = document.createElement('div');
                        row.className = `flex font-mono text-[11px] md:text-xs py-0.5 border-l-4 transition-all duration-300 ${isTarget ? 'bg-rose-950/40 border-rose-500 text-rose-100 pl-2' : 'border-transparent text-slate-300 hover:bg-slate-900/60 pl-2'}`;
                        if (isTarget) {
                            row.id = "target-error-line";
                        }

                        // Nomor Baris Kiri
                        const numberSpan = document.createElement('span');
                        numberSpan.className = `w-12 text-right pr-4 select-none font-semibold ${isTarget ? 'text-rose-400' : 'text-slate-600'}`;
                        numberSpan.textContent = lineObj.number;

                        // Konten Baris Kode Kanan
                        const codePre = document.createElement('pre');
                        codePre.className = "flex-1 overflow-x-auto whitespace-pre";
                        codePre.textContent = lineObj.content;

                        row.appendChild(numberSpan);
                        row.appendChild(codePre);
                        linesWrapper.appendChild(row);
                    });

                    // Auto-Scroll presisi ke arah baris yang memicu error
                    if (activeScrollTimeout) clearTimeout(activeScrollTimeout);
                    activeScrollTimeout = setTimeout(() => {
                        const targetEl = document.getElementById("target-error-line");
                        if (targetEl) {
                            targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, 120);
                })
                .catch(err => {
                    loadingLayer.classList.add('hidden');
                    codeContainer.classList.add('hidden');
                    errorLayer.classList.remove('hidden');
                    document.getElementById('code-modal-error-msg').textContent = err.message || "Gagal menghubungkan ke server.";
                });
        }

        function closeCodeViewer() {
            document.getElementById('code-viewer-modal').classList.add('hidden');
            document.body.style.overflow = ''; // Kembalikan scroll halaman utama
        }

        // ============================================
        // LOGIKA INTERAKTIF VERIFIKASI SENSITIF ENV PIN
        // ============================================
        let pendingRevealKey = null;

        function requestRevealEnv(key) {
            pendingRevealKey = key;
            
            const modal = document.getElementById('pin-verification-modal');
            const pinInput = document.getElementById('security-pin-input');
            const errorContainer = document.getElementById('pin-error-container');
            
            // Reset input modal
            pinInput.value = '';
            errorContainer.classList.add('hidden');
            
            // Tampilkan modal verifikasi PIN
            modal.classList.remove('hidden');
            pinInput.focus();
        }

        function closePinVerification() {
            document.getElementById('pin-verification-modal').classList.add('hidden');
            pendingRevealKey = null;
        }

        function submitSecurityPin() {
            if (!pendingRevealKey) return;

            const pinInput = document.getElementById('security-pin-input');
            const errorContainer = document.getElementById('pin-error-container');
            const errorMsg = document.getElementById('pin-error-msg');
            const btnText = document.getElementById('pin-btn-text');
            const btnSpinner = document.getElementById('pin-btn-spinner');
            const submitBtn = document.getElementById('pin-submit-button');

            const pinValue = pinInput.value.trim();

            if (pinValue === '') {
                errorContainer.classList.remove('hidden');
                errorMsg.textContent = 'Silakan masukkan PIN pengaman terlebih dahulu.';
                pinInput.focus();
                return;
            }

            // Tampilkan state loading verifikasi
            errorContainer.classList.add('hidden');
            btnText.textContent = 'Memverifikasi...';
            btnSpinner.classList.remove('hidden');
            submitBtn.disabled = true;
            pinInput.disabled = true;

            const url = `?action=get_env_value&key=${encodeURIComponent(pendingRevealKey)}&pin=${encodeURIComponent(pinValue)}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Gagal menghubungkan ke modul autentikasi server.');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    // Berhasil diverifikasi: Perbarui UI secara langsung
                    const valueContainer = document.getElementById('env-val-' + pendingRevealKey);
                    const eyeButton = document.getElementById('env-btn-' + pendingRevealKey);

                    if (valueContainer) {
                        valueContainer.textContent = data.value;
                        valueContainer.className = "text-emerald-400 font-semibold break-all";
                    }

                    // Sembunyikan atau nonaktifkan tombol intip berkas karena sudah terbuka
                    if (eyeButton) {
                        eyeButton.disabled = true;
                        eyeButton.className = "p-1.5 bg-slate-900 text-slate-700 rounded-lg border border-slate-950 cursor-not-allowed flex-shrink-0";
                        eyeButton.innerHTML = `
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        `;
                    }

                    // Tutup modal
                    closePinVerification();
                })
                .catch(err => {
                    // Tampilkan pesan error pada modal
                    errorContainer.classList.remove('hidden');
                    errorMsg.textContent = err.message || 'Terjadi kesalahan sistem.';
                    pinInput.focus();
                })
                .finally(() => {
                    // Kembalikan status tombol verifikasi ke normal
                    btnText.textContent = 'Verifikasi';
                    btnSpinner.classList.add('hidden');
                    submitBtn.disabled = false;
                    pinInput.disabled = false;
                });
        }

        // Jalankan verifikasi ketika menekan tombol "Enter" di dalam input PIN
        document.getElementById('security-pin-input').addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                submitSecurityPin();
            }
        });

        // Tutup modal-modal interaktif ketika tombol "Escape" ditekan
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeCodeViewer();
                closePinVerification();
            }
        });
    </script>
</body>
</html>