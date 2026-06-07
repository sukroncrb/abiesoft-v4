<?php
/**
 * Elegant & Modern Exception Template
 * Fitur: Deteksi otomatis framework, variabel lingkungan,
 * pengelompokan stack trace berdasarkan Kategori (User, Abiesoft, Pihak Ke-3) 
 * dan File (sistem dropdown), serta detail diagnostik lengkap.
 */

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
    // Harus berada di folder "src/modules", bukan file "handler.go", dan bukan di folder "templates"
    $isUserCode = (strpos($normalizedPath, 'src/modules/') !== false) 
                  && (strpos($normalizedPath, 'handler.go') === false) 
                  && (strpos($normalizedPath, '/templates/') === false);

    // Aturan filter Sistem Abiesoft: mengandung namespace/folder "Abiesoft"
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
<body class="bg-slate-950 text-slate-200 font-sans antialiased min-h-screen flex flex-col selection:bg-rose-500/30 selection:text-rose-200">

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
                
                <div class="mt-4 flex flex-wrap items-center text-sm text-slate-400 gap-2 font-mono bg-slate-950/50 p-3 rounded-lg border border-slate-800">
                    <span class="text-slate-500">File Utama:</span>
                    <span class="text-slate-200 break-all"><?= escapeHtml($exception->getFile()) ?></span>
                    <span class="text-slate-500">pada baris</span>
                    <span class="text-rose-400 font-bold bg-rose-500/10 px-2 py-0.5 rounded"><?= $exception->getLine() ?></span>
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
                    <div class="p-4 bg-rose-950/10 border-l-4 border-rose-500/80 hover:bg-rose-950/20 transition rounded-md">
                        <div class="flex items-center gap-2">
                            <span class="text-rose-400 font-bold text-xs bg-rose-500/10 px-2 py-0.5 rounded">ALUR EKSEKUSI UTAMA</span>
                        </div>
                        <p class="text-slate-200 mt-2 font-mono text-xs md:text-sm break-all">
                            <?= escapeHtml($exception->getFile()) ?> : <span class="text-rose-400 font-semibold"><?= $exception->getLine() ?></span>
                        </p>
                    </div>

                    <!-- Iterasi Kategori (User -> Abiesoft -> Vendor) -->
                    <?php 
                    $globalGroupIndex = 0; 
                    $isFirstFileOverall = true; // Penanda agar HANYA file paling pertama di stack trace yang terbuka otomatis
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
                                        $isFirstFileOverall = false; // Setelah yang pertama diproses, set ke false
                                        ?>
                                        <div class="bg-slate-900/40 border border-slate-800/80 rounded-xl overflow-hidden transition duration-200">
                                            
                                            <!-- Tombol Dropdown / Header File -->
                                            <button onclick="toggleFileGroup('group-<?= $globalGroupIndex ?>')" class="w-full flex items-center justify-between p-4 bg-slate-900/60 hover:bg-slate-800/85 transition text-left focus:outline-none">
                                                <div class="flex items-center gap-3 min-w-0 pr-4">
                                                    <!-- Icon File Sesuai Kategori -->
                                                    <?php if ($categoryKey === 'user'): ?>
                                                        <!-- Icon User (Code Bracket) -->
                                                        <svg class="w-5 h-5 text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                                                    <?php elseif ($categoryKey === 'abiesoft'): ?>
                                                        <!-- Icon Shield/Core -->
                                                        <svg class="w-5 h-5 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                                    <?php else: ?>
                                                        <!-- Icon Package (Vendor) -->
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
                                                            
                                                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
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
                                                                
                                                                <!-- Tombol Tampil Argumen jika ada -->
                                                                <?php if (isset($trace['args']) && !empty($trace['args'])): ?>
                                                                    <div class="flex-shrink-0">
                                                                        <button onclick="toggleArgs('args-<?= $trace['original_index'] ?>')" class="text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold px-2.5 py-1 rounded transition border border-slate-700 font-sans">
                                                                            Detail Argumen
                                                                        </button>
                                                                    </div>
                                                                <?php endif; ?>
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

                <!-- Tab: Variabel Server -->
                <div id="content-servervars" class="tab-content hidden">
                    <div class="space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 font-mono">Variabel Lingkungan Server ($_SERVER)</h3>
                        <div class="bg-slate-950 border border-slate-800 rounded-xl overflow-hidden">
                            <table class="w-full text-left font-mono text-xs divide-y divide-slate-800">
                                <thead class="bg-slate-900/60 text-slate-400">
                                    <tr>
                                        <th class="p-3">Kunci Parameter</th>
                                        <th class="p-3">Nilai</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800">
                                    <?php foreach ($_SERVER as $key => $val): ?>
                                        <tr class="hover:bg-slate-900/40">
                                            <td class="p-3 text-slate-400 border-r border-slate-800 w-1/3 break-all font-semibold"><?= escapeHtml($key) ?></td>
                                            <td class="p-3 text-blue-400 break-all"><?= escapeHtml($val) ?></td>
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
    </script>
</body>
</html>