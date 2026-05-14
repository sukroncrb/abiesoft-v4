package services

import (
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
)

func GetAllSampleService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {

	rows, err := db.Query("SELECT id, nama, tech FROM sample ORDER BY id DESC")
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal: " + err.Error()
		return res
	}
	defer rows.Close()

	type Sample struct {
		ID   int    `json:"id"`
		Nama string `json:"nama"`
		Tech string `json:"tech"`
	}

	list := make([]Sample, 0)

	for rows.Next() {
		var d Sample

		if err := rows.Scan(&d.ID, &d.Nama, &d.Tech); err != nil {
			res.Status = "error"
			res.Msg = "Scan error di tengah data: " + err.Error()
			return res
		}
		list = append(list, d)
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

func CreateSampleService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	uuid := req.Params["uuid"]
	nama := req.Params["nama"]
	tech := req.Params["tech"]

	if nama == "" {
		res.Status = "error"
		res.Msg = "Field nama tidak boleh kosong"
		return res
	}

	query := "INSERT INTO sample (uuid, nama, tech) VALUES (?,?,?)"
	result, err := db.Exec(query, uuid, nama, tech)
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal simpan data: " + err.Error()
		return res
	}

	lastID, _ := result.LastInsertId()
	res.Status = "success"
	res.Msg = "Data berhasil disimpan"
	res.Data = map[string]interface{}{"id": lastID}
	return res
}

func UpdateSampleService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	id := req.Params["id"]
	nama := req.Params["nama"]
	tech := req.Params["tech"]

	if id == "" || nama == "" {
		res.Status = "error"
		res.Msg = "ID dan Nama harus diisi"
		return res
	}

	query := "UPDATE sample SET nama = ?, tech = ? WHERE id = ?"
	result, err := db.Exec(query, nama, tech, id)
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal update data: " + err.Error()
		return res
	}

	rowsAffected, _ := result.RowsAffected()
	if rowsAffected == 0 {
		res.Status = "error"
		res.Msg = "Tidak ada data yang diupdate (ID tidak ditemukan)"
		return res
	}

	res.Status = "success"
	res.Msg = "Data berhasil diperbarui"
	return res
}

func DeleteSampleService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	id := req.Params["id"]

	if id == "" {
		res.Status = "error"
		res.Msg = "ID diperlukan untuk menghapus data"
		return res
	}

	query := "DELETE FROM sample WHERE id = ?"
	result, err := db.Exec(query, id)
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal hapus data: " + err.Error()
		return res
	}

	rowsAffected, _ := result.RowsAffected()
	if rowsAffected == 0 {
		res.Status = "error"
		res.Msg = "Gagal hapus, ID tidak ditemukan"
		return res
	}

	res.Status = "success"
	res.Msg = "Data berhasil dihapus"
	return res
}
