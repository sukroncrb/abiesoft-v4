package services

import (
	dto "abiesoft/src/GoModules/Dto"
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
)

func GetAllSampleService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {

	rows, err := db.Query("SELECT id, nama, tech FROM sample ORDER BY id DESC")
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal Query: " + err.Error()
		return res
	}
	defer rows.Close()

	list := []dto.SampleDto{}

	for rows.Next() {
		var d dto.SampleDto

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
	res.Msg = "Data retrieved successfully"
	res.Data = list
	return res
}

func GetSampleBigDataService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	var limitStr, offsetStr string

	if val, ok := req.Params["limit"]; ok {
		limitStr = val
	}
	if val, ok := req.Params["offset"]; ok {
		offsetStr = val
	}

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
		res.Msg = "Gagal Query Big Data: " + err.Error()
		return res
	}
	defer rows.Close()

	list := make([]dto.SampleDto, 0)

	for rows.Next() {
		var m dto.SampleDto
		if err := rows.Scan(&m.ID, &m.Nama); err != nil {
			res.Status = "error"
			res.Msg = "Scan error Big Data: " + err.Error()
			return res
		}
		list = append(list, m)
	}

	if err = rows.Err(); err != nil {
		res.Status = "error"
		res.Msg = "Stream error Big Data: " + err.Error()
		return res
	}

	res.Status = "success"
	res.Msg = "Big Data retrieved successfully"
	res.Data = list
	return res
}

func GetOnlySampleService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	var id string
	if val, ok := req.Params["id"]; ok {
		id = val
	}

	rows, err := db.Query("SELECT id, nama, tech FROM sample WHERE id = ?", id)
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal mengambil data: " + err.Error()
		return res
	}
	defer rows.Close()

	list := make([]dto.SampleDto, 0)

	for rows.Next() {
		var m dto.SampleDto
		err := rows.Scan(&m.ID, &m.Nama, &m.Tech)
		if err != nil {
			continue
		}
		list = append(list, m)
	}

	res.Status = "success"
	res.Msg = "Single data retrieved successfully"
	res.Data = list
	return res
}

func CreateSampleService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	var uuid, nama, tech string

	if val, ok := req.Params["uuid"]; ok {
		uuid = val
	}
	if val, ok := req.Params["nama"]; ok {
		nama = val
	}
	if val, ok := req.Params["tech"]; ok {
		tech = val
	}

	if nama == "" {
		res.Status = "error"
		res.Msg = "Parameter 'nama' tidak boleh kosong"
		return res
	}

	query := "INSERT INTO sample (uuid, nama, tech) VALUES (?, ?, ?)"
	_, err := db.Exec(query, uuid, nama, tech)
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal menyimpan ke database: " + err.Error()
		return res
	}

	res.Status = "success"
	res.Msg = "Data berhasil disimpan oleh Go Engine"
	res.Data = map[string]interface{}{
		"uuid": uuid,
		"nama": nama,
		"tech": tech,
	}

	return res
}

func UpdateSampleService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	var id, nama, tech string

	if val, ok := req.Params["id"]; ok {
		id = val
	}
	if val, ok := req.Params["nama"]; ok {
		nama = val
	}
	if val, ok := req.Params["tech"]; ok {
		tech = val
	}

	query := "UPDATE sample SET nama = ?, tech = ? WHERE id = ?"
	_, err := db.Exec(query, nama, tech, id)
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal memperbarui database: " + err.Error()
		return res
	}

	res.Status = "success"
	res.Msg = "Data berhasil diperbarui oleh Go Engine"
	return res
}

func DeleteSampleService(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	var id string
	if val, ok := req.Params["id"]; ok {
		id = val
	}

	query := "DELETE FROM sample WHERE id = ?"
	_, err := db.Exec(query, id)
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal menghapus data dari database: " + err.Error()
		return res
	}

	res.Status = "success"
	res.Msg = "Data berhasil dihapus oleh Go Engine"
	return res
}
