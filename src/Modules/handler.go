package modules

import (


	sampleActions "abiesoft/src/GoModules/Sample/Actions"
	wellcomeActions "abiesoft/src/GoModules/Wellcome/Actions"
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
	"net/http"
	"strings"
)

// RegisterRoutes mendaftarkan seluruh rute /api-go/* langsung ke router native Go
func RegisterRoutes(mux *http.ServeMux, db *sql.DB) {
	// Wellcome module
	mux.HandleFunc("GET /api-go/wellcome/{info}", wellcomeActions.WellcomeHTTPHandler(db))
	mux.HandleFunc("GET /api-go/wellcome", wellcomeActions.WellcomeHTTPHandler(db))

	// Sample module
	mux.HandleFunc("GET /api-go/sample", sampleActions.GetAllSampleHTTPHandler(db))
	mux.HandleFunc("GET /api-go/sample/{id}", sampleActions.GetOnlySampleHTTPHandler(db))
	mux.HandleFunc("GET /api-go/sample/{offset}/{limit}", sampleActions.GetBigDataSampleHTTPHandler(db))
	mux.HandleFunc("POST /api-go/sample", sampleActions.SaveSampleHTTPHandler(db))
	mux.HandleFunc("PUT /api-go/sample/{id}", sampleActions.UpdateSampleHTTPHandler(db))
	mux.HandleFunc("DELETE /api-go/sample/{id}", sampleActions.DeleteSampleHTTPHandler(db))


}

func HandleRequest(req shared.PiGoRequest, db *sql.DB) shared.PiGoResponse {

	if req.Action == "wellcome" {
		return wellcomeActions.HandleWellcomeAction(req, db)
	}

	if strings.HasPrefix(req.Action, "sample-") || strings.HasSuffix(req.Action, "-sample") {
		return sampleActions.HandleSampleAction(req, db)
	}

	return shared.PiGoResponse{
		Status: "error",
		Msg:    "Action Modul global tidak dikenali",
	}
}
