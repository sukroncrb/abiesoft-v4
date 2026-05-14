package services

import (
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
)

func GetAllSampleService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {

	rows, err := db.Query("SELECT id, nama FROM sample ")
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal: " + err.Error()
		return res
	}
	defer rows.Close()

	type Sample struct {
		ID   int    `json:"id"`
		Nama string `json:"nama"`
	}

	list := make([]Sample, 0)

	for rows.Next() {
		var m Sample

		if err := rows.Scan(&m.ID, &m.Nama); err != nil {
			res.Status = "error"
			res.Msg = "Scan error di tengah data: " + err.Error()
			return res
		}
		list = append(list, m)
	}

	if err = rows.Err(); err != nil {
		res.Status = "error"
		res.Msg = "Error saat membaca stream data: " + err.Error()
		return res
	}

	res.Status = "success"
	res.Data = list
	return res
}

func GetSampleBigDataService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {

	limitStr := req.Params["limit"]
	offsetStr := req.Params["offset"]

	if limitStr == "" {
		limitStr = "100"
	}
	if offsetStr == "" {
		offsetStr = "0"
	}

	query := "SELECT id, nama FROM sample LIMIT ? OFFSET ?"
	rows, err := db.Query(query, limitStr, offsetStr)

	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal Query: " + err.Error()
		return res
	}
	defer rows.Close()

	type Sample struct {
		ID   int    `json:"id"`
		Nama string `json:"nama"`
	}

	list := []Sample{}

	for rows.Next() {
		var m Sample
		if err := rows.Scan(&m.ID, &m.Nama); err != nil {
			res.Status = "error"
			res.Msg = "Scan error: " + err.Error()
			return res
		}
		list = append(list, m)
	}

	if err = rows.Err(); err != nil {
		res.Status = "error"
		res.Msg = "Stream error: " + err.Error()
		return res
	}

	res.Status = "success"
	res.Msg = "Data retrieved"
	res.Data = list
	return res
}

func GetOnlySampleService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {

	id := req.Params["id"]
	rows, err := db.Query("SELECT id, nama FROM sample WHERE id = ? ", id)

	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal mengambil data: " + err.Error()
		return res
	}
	defer rows.Close()

	type Sample struct {
		ID   int    `json:"id"`
		Nama string `json:"nama"`
	}
	list := []Sample{}

	for rows.Next() {
		var m Sample
		err := rows.Scan(&m.ID, &m.Nama)
		if err != nil {
			continue
		}
		list = append(list, m)
	}

	res.Status = "success"
	res.Data = list

	return res
}
