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
	case "sample-big-data":
		return services.GetSampleBigDataService(res, db, req)
	case "post-sample":
		return services.CreateSampleService(res, db, req)
	case "update-sample":
		return services.UpdateSampleService(res, db, req)
	case "delete-sample":
		return services.DeleteSampleService(res, db, req)
	default:
		res.Status = "error"
		res.Msg = "Action Tidak Terdaftar"
	}

	return res
}
