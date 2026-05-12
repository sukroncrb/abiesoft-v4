package actions

import (
	shared "abiesoft/src/Shared"
	"fmt"
)

func HandleWellcomeAction(req shared.PiGoRequest) shared.PiGoResponse {
	var res shared.PiGoResponse

	switch req.Action {
	case "wellcome":
		info := req.Params["info"]
		res.Status = "success"
		res.Data = fmt.Sprintf("[Go Api Say] %s", info)
	default:
		res.Status = "error"
		res.Msg = "Action Not Found"
	}

	return res
}
