<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($exception->getMessage()) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-200 font-sans antialiased min-h-screen p-6 md:p-12">

    <div class="max-w-6xl mx-auto">
        <div class="bg-red-900/40 border border-red-700/60 rounded-lg p-6 mb-6 shadow-lg">
            <span class="text-xs font-bold uppercase tracking-wider bg-red-700 text-white px-2 py-1 rounded">
                <?= get_class($exception) ?>
            </span>
            <h1 class="text-2xl md:text-3xl font-semibold text-red-400 mt-3 break-words">
                <?= htmlspecialchars($exception->getMessage()) ?>
            </h1>
            <div class="text-sm text-gray-400 mt-2 font-mono">
                At <span class="text-gray-200"><?= $exception->getFile() ?></span> line <span class="text-red-400 font-bold"><?= $exception->getLine() ?></span>
            </div>
        </div>

        <h2 class="text-xl font-medium text-gray-400 mb-4">Stack Trace</h2>
        <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden shadow-lg font-mono text-sm">
            <div class="divide-y divide-gray-700">
                <div class="p-4 bg-gray-800/80 hover:bg-gray-700/50 transition">
                    <span class="text-red-400 font-semibold">[Main Exec]</span>
                    <p class="text-gray-300 mt-1"><?= $exception->getFile() ?>:<?= $exception->getLine() ?></p>
                </div>
                
                <?php foreach ($exception->getTrace() as $index => $trace): ?>
                    <div class="p-4 hover:bg-gray-700/30 transition">
                        <span class="text-blue-400">#<?= $index + 1 ?></span>
                        <span class="text-purple-400 font-semibold">
                            <?= ($trace['class'] ?? '') . ($trace['type'] ?? '') . ($trace['function'] ?? '') ?>()
                        </span>
                        <p class="text-gray-400 text-xs mt-1">
                            <?= $trace['file'] ?? 'Internal PHP Call' ?>:<?= $trace['line'] ?? '?' ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</body>
</html>