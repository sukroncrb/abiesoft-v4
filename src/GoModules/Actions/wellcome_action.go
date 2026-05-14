package actions

import (
	services "abiesoft/src/GoModules/Services"
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
)

func HandleWellcomeAction(req shared.PiGoRequest, db *sql.DB) shared.PiGoResponse {
	var res shared.PiGoResponse

	switch req.Action {
	case "wellcome":
		return services.GetWelcomeMessage(res, db, req)
	case "sample-all-data":
		return services.GetAllSampleService(res, db, req)
	case "sample-only-data":
		return services.GetOnlySampleService(res, db, req)
	default:
		res.Status = "error"
		res.Msg = "Action Tidak Terdaftar"
	}

	return res
}
