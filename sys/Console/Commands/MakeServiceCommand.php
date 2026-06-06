<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class MakeServiceCommand extends BaseCommand
{
    public function handle(array $args): void
    {
        $moduleInput = $args[2] ?? null;
        $namaInput   = $args[3] ?? null;

        if (!$moduleInput || !$namaInput) {
            $this->tampilkanError("Parameter kurang.\nGunakan: php abiesoft make:service <module> <nama>");
            return;
        }

        $namaModule = ucfirst($moduleInput);
        $nama       = ucfirst($namaInput);
        $namaClass  = "{$nama}Repository";

        $folderPath = dirname(__DIR__, 3) . '/src/Modules/' . $namaModule . '/Services';
        $filePath   = $folderPath . '/' . $namaClass . '.php';

        $this->buatFolder($folderPath);

        if (file_exists($filePath)) {
            $this->tampilkanError("File $namaClass.php sudah ada!");
            return;
        }

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace Abiesoft\App\Modules\\{$namaModule}\Services;

        use Abiesoft\App\Shared\Helpers\Service;
        use Abiesoft\System\Database\DB;
        use Abiesoft\System\Utilities\Input;

        class {$namaClass} extends Service
        {
            private \$db;

            public function __construct()
            {
                \$this->db = (new DB)->terhubung();
            }

            public function getAll()
            {
                /*


                    ---------------------------------------------------------------
                    Menampilkan semua data
                    ---------------------------------------------------------------
                */
            }

            public function getOnly()
            {
                /*


                    ---------------------------------------------------------------
                    Menampilkan 1 data
                    ---------------------------------------------------------------
                */
            }

            public function post()
            {
                \$input = new Input();
                if(\$input->get("__method") == "DELETE"){
                    \$this->drop();
                }else{
                    if(\$input->get("id") != "" || \$input->get("uuid") != ""){
                        \$this->replace();
                    }else{
                        \$this->keep();
                    }
                }
            }

            protected function keep()
            {
                /*


                    ---------------------------------------------------------------
                    Menambah data
                    ---------------------------------------------------------------
                */
            }

            protected function replace()
            {
                /*


                    ---------------------------------------------------------------
                    Memperbarui data
                    ---------------------------------------------------------------
                */
            }

            protected function drop()
            {
                /*


                    ---------------------------------------------------------------
                    Menghapus data
                    ---------------------------------------------------------------
                */
            }
        }
        PHP;

        $this->buatFile($filePath, $content, $namaClass);
    }
}