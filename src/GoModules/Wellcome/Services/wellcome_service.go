package services

import (
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
	"fmt"
)

func GetWelcomeMessageDirect(info string) string {
	if info == "start" {
		info = "Halo, Programmer!"
	} else if info == "pengenalan" {
		info = "Ini adalah framework hybrid"
	} else if info == "fitur1" {
		info = "Anda bisa gunakan golang untuk api"
	} else if info == "fitur2" {
		info = "Anda juga bisa gunakan php untuk api"
	} else if info == "ending" {
		info = "Selamat mencoba."
	}
	return fmt.Sprintf("[Go Api Say] %s", info)
}

func GetWelcomeMessage(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	info := req.Params["info"]
	res.Status = "success"
	res.Data = GetWelcomeMessageDirect(info)
	return res
}
