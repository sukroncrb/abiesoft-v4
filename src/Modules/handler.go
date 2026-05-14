package modules

import (
	actions "abiesoft/src/GoModules/Actions"
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
)

func HandleRequest(req shared.PiGoRequest, db *sql.DB) shared.PiGoResponse {
	return actions.HandleWellcomeAction(req, db)
}
