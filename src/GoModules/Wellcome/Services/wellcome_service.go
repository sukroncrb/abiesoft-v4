package services

import (
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
	"fmt"
)

func GetWelcomeMessage(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	info := req.Params["info"]
	res.Status = "success"
	res.Data = fmt.Sprintf("[Go Api Say] %s", info)
	return res
}
