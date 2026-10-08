package actions

import (
	services "abiesoft/src/GoModules/Wellcome/Services"
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
	"net/http"
)

func WellcomeHTTPHandler(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		info := r.PathValue("info")
		if info == "" {
			info = r.URL.Query().Get("info")
		}
		if info == "" {
			info = "start"
		}
		data := services.GetWelcomeMessageDirect(info)
		shared.SuccessResponse(w, data)
	}
}

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
