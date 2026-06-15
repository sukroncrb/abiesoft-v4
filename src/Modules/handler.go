package modules

import (
	sampleActions "abiesoft/src/GoModules/Sample/Actions"
	wellcomeActions "abiesoft/src/GoModules/Wellcome/Actions"
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
	"strings"
)

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
