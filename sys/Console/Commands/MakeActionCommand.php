<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class MakeActionCommand extends BaseCommand
{
    public function handle(array $args): void
    {
        $isGo = false;
        $params = [];

        for ($i = 2; $i < count($args); $i++) {
            $arg = trim($args[$i]);
            if (in_array(strtolower($arg), ['--go', '-go', '--golang', '-g'])) {
                $isGo = true;
            } else if (!str_starts_with($arg, '-')) {
                $params[] = $arg;
            }
        }

        $moduleInput = $params[0] ?? null;
        $actionInput = $params[1] ?? null;

        if (!$moduleInput || !$actionInput) {
            $this->tampilkanError("Parameter kurang.\nGunakan:\n  • PHP Action    : php abiesoft make:action <module> <action>\n  • Golang Action : php abiesoft make:action <module> <action> --go");
            return;
        }

        $namaModule = ucfirst($moduleInput);
        $namaAction = ucfirst($actionInput);

        if ($isGo) {
            $actionLower = strtolower($actionInput);
            $folderPath  = dirname(__DIR__, 3) . '/src/GoModules/' . $namaModule . '/Actions';
            $filePath    = $folderPath . '/' . $actionLower . '_action.go';

            $this->buatFolder($folderPath);

            if (file_exists($filePath)) {
                $this->tampilkanError("File Go Action '{$actionLower}_action.go' sudah ada!");
                return;
            }

            $moduleLower = strtolower($moduleInput);
            $content = <<<GOCONTENT
package actions

import (
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
	"net/http"
)

// {$namaAction}{$namaModule}HTTPHandler menangani HTTP request native Go
func {$namaAction}{$namaModule}HTTPHandler(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		params := shared.ExtractParams(r)
		// TODO: Logika aksi {$namaAction} untuk modul {$namaModule}

		shared.SuccessResponse(w, map[string]interface{}{
			"action":  "{$actionLower}",
			"module":  "{$namaModule}",
			"params":  params,
			"message": "Action {$namaAction} pada modul {$namaModule} berhasil dieksekusi oleh Go Engine",
		})
	}
}
GOCONTENT;

            $this->buatFile($filePath, $content, "Go Action: {$actionLower}_action.go");
            $this->log("💡 Daftarkan handler ini di src/Modules/handler.go:", self::COLOR_YELLOW);
            echo "   mux.HandleFunc(\"GET /api-go/{$moduleLower}/{$actionLower}\", {$moduleLower}Actions.{$namaAction}{$namaModule}HTTPHandler(db))\n\n";
        } else {
            $namaClass  = "{$namaAction}{$namaModule}Action";
            $folderPath = dirname(__DIR__, 3) . '/src/Modules/' . $namaModule . '/Actions';
            $filePath   = $folderPath . '/' . $namaClass . '.php';

            $this->buatFolder($folderPath);

            if (file_exists($filePath)) {
                $this->tampilkanError("File $namaClass.php sudah ada!");
                return;
            }

            $content = <<<PHP
<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\\{$namaModule}\Actions;

use Abiesoft\System\View\ViewRenderer;

readonly class {$namaClass}
{
    public function __invoke(ViewRenderer \$view): void
    {
        // \$view->render('pages/{$moduleInput}/{$actionInput}', ['title' => 'Halaman {$moduleInput} {$actionInput}']);
    }
}
PHP;

            $this->buatFile($filePath, $content, $namaClass);
        }
    }
}