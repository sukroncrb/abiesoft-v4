<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class MakeGoServiceCommand extends BaseCommand
{
    public function handle(array $args): void
    {
        if (!isset($args[2])) {
            echo "\033[31mError: Nama service belum ditentukan.\033[0m\n";
            echo "Gunakan: php abiesoft make:goservice [nama]\n";
            return;
        }

        $name = strtolower($args[2]);
        $ucName = ucfirst($name);
        $fileName = $name . "_service.go";
        $targetDir = __DIR__ . "/../../../src/GoModules/Services/";
        $fullPath = $targetDir . $fileName;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (file_exists($fullPath)) {
            echo "\033[31mError: File $fileName sudah ada.\033[0m\n";
            return;
        }

        $template = $this->getTemplate($name, $ucName);

        if (file_put_contents($fullPath, $template)) {
            $path = "src/GoModules/".explode("GoModules",$fullPath)[1];
            echo "\033[32m✔ Berhasil:\033[0m Go Service dibuat di $path\n";
        } else {
            echo "\033[31m✘ Gagal membuat file.\033[0m\n";
        }
    }

    private function getTemplate($name, $ucName): string
    {
        return <<<GOCONTENT
            package services

            import (
                shared "abiesoft/src/Shared/Helpers/Golang"
                "database/sql"
            )

            func GetAll{$ucName}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
                rows, err := db.Query("SELECT id, nama, tech FROM {$name} ORDER BY id DESC")
                if err != nil {
                    res.Status = "error"
                    res.Msg = "Gagal: " + err.Error()
                    return res
                }
                defer rows.Close()

                type {$ucName} struct {
                    ID   int    `json:"id"`
                    Nama string `json:"nama"`
                    Tech string `json:"tech"`
                }

                list := make([]{$ucName}, 0)
                for rows.Next() {
                    var d {$ucName}
                    if err := rows.Scan(&d.ID, &d.Nama, &d.Tech); err != nil {
                        res.Status = "error"
                        res.Msg = "Scan error: " + err.Error()
                        return res
                    }
                    list = append(list, d)
                }

                res.Status = "success"
                res.Data = list
                return res
            }

            func Get{$ucName}BigDataService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
                limitStr := req.Params["limit"]
                offsetStr := req.Params["offset"]

                if limitStr == "" { limitStr = "100" }
                if offsetStr == "" { offsetStr = "0" }

                query := "SELECT id, nama FROM {$name} LIMIT ? OFFSET ?"
                rows, err := db.Query(query, limitStr, offsetStr)
                if err != nil {
                    res.Status = "error"
                    res.Msg = "Gagal Query: " + err.Error()
                    return res
                }
                defer rows.Close()

                type {$ucName} struct {
                    ID   int    `json:"id"`
                    Nama string `json:"nama"`
                }

                list := []{$ucName}{}
                for rows.Next() {
                    var m {$ucName}
                    if err := rows.Scan(&m.ID, &m.Nama); err != nil {
                        res.Status = "error"
                        res.Msg = "Scan error: " + err.Error()
                        return res
                    }
                    list = append(list, m)
                }

                res.Status = "success"
                res.Data = list
                return res
            }

            func GetOnly{$ucName}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
                id := req.Params["id"]
                rows, err := db.Query("SELECT id, nama FROM {$name} WHERE id = ? ", id)
                if err != nil {
                    res.Status = "error"
                    res.Msg = "Gagal: " + err.Error()
                    return res
                }
                defer rows.Close()

                type {$ucName} struct {
                    ID   int    `json:"id"`
                    Nama string `json:"nama"`
                }
                list := []{$ucName}{}
                for rows.Next() {
                    var m {$ucName}
                    err := rows.Scan(&m.ID, &m.Nama)
                    if err != nil { continue }
                    list = append(list, m)
                }

                res.Status = "success"
                res.Data = list
                return res
            }

            func Create{$ucName}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
                uuid := req.Params["uuid"]
                nama := req.Params["nama"]
                tech := req.Params["tech"]

                if nama == "" {
                    res.Status = "error"
                    res.Msg = "Field nama tidak boleh kosong"
                    return res
                }

                query := "INSERT INTO {$name} (uuid, nama, tech) VALUES (?,?,?)"
                result, err := db.Exec(query, uuid, nama, tech)
                if err != nil {
                    res.Status = "error"
                    res.Msg = "Gagal simpan: " + err.Error()
                    return res
                }

                lastID, _ := result.LastInsertId()
                res.Status = "success"
                res.Msg = "Data disimpan"
                res.Data = map[string]interface{}{"id": lastID}
                return res
            }

            func Update{$ucName}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
                id := req.Params["id"]
                nama := req.Params["nama"]
                tech := req.Params["tech"]

                query := "UPDATE {$name} SET nama = ?, tech = ? WHERE id = ?"
                _, err := db.Exec(query, nama, tech, id)
                if err != nil {
                    res.Status = "error"
                    res.Msg = "Gagal update: " + err.Error()
                    return res
                }

                res.Status = "success"
                res.Msg = "Data diperbarui"
                return res
            }

            func Delete{$ucName}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
                id := req.Params["id"]
                query := "DELETE FROM {$name} WHERE id = ?"
                _, err := db.Exec(query, id)
                if err != nil {
                    res.Status = "error"
                    res.Msg = "Gagal hapus: " + err.Error()
                    return res
                }

                res.Status = "success"
                res.Msg = "Data dihapus"
                return res
            }
            GOCONTENT;
    }
}