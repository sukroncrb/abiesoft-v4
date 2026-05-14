package services

import (
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
)

func GetAllSampleService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {

	rows, err := db.Query("SELECT id, migration FROM migrations ")

	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal mengambil data: " + err.Error()
		return res
	}
	defer rows.Close()

	type Migration struct {
		ID        int    `json:"id"`
		Migration string `json:"migration"`
	}
	list := []Migration{}

	for rows.Next() {
		var m Migration
		err := rows.Scan(&m.ID, &m.Migration)
		if err != nil {
			continue
		}
		list = append(list, m)
	}

	res.Status = "success"
	res.Data = list

	return res
}

func GetOnlySampleService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {

	id := req.Params["id"]
	rows, err := db.Query("SELECT id, migration FROM migrations WHERE id = ? ", id)

	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal mengambil data: " + err.Error()
		return res
	}
	defer rows.Close()

	type Migration struct {
		ID        int    `json:"id"`
		Migration string `json:"migration"`
	}
	list := []Migration{}

	for rows.Next() {
		var m Migration
		err := rows.Scan(&m.ID, &m.Migration)
		if err != nil {
			continue
		}
		list = append(list, m)
	}

	res.Status = "success"
	res.Data = list

	return res
}
