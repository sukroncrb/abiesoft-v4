package actions

import (
	services "abiesoft/src/GoModules/Wellcome/Services"
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
)

func HandleWellcomeAction(req shared.PiGoRequest, db *sql.DB) shared.PiGoResponse {
	var res shared.PiGoResponse

	switch req.Action {
	case "wellcome":

		return services.GetWelcomeMessage(res, db, req)

	default:
		res.Status = "error"
		res.Msg = "Action di dalam Modul Wellcome Tidak Terdaftar"
	}

	return res
}
