package modules

import (
	actions "abiesoft/src/GoModules/Actions"
	shared "abiesoft/src/Shared"
)

func HandleRequest(req shared.PiGoRequest) shared.PiGoResponse {
	switch req.Action {
	case "wellcome", "user_save":
		return actions.HandleWellcomeAction(req)
	default:
		return shared.PiGoResponse{Status: "error", Msg: "404 Not Found"}
	}
}
