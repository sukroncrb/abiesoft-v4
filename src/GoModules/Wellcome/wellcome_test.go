package wellcome_test

import (
	shared "abiesoft/src/Shared/Helpers/Golang"
	"testing"
)

func TestPiGoResponseStructure(t *testing.T) {
	res := shared.PiGoResponse{
		Status: "success",
		Msg:    "Test OK",
		Data:   map[string]string{"module": "Wellcome"},
	}

	if res.Status != "success" {
		t.Errorf("Expected status 'success', got '%s'", res.Status)
	}

	if res.Msg != "Test OK" {
		t.Errorf("Expected msg 'Test OK', got '%s'", res.Msg)
	}
}

func TestPiGoRequestAction(t *testing.T) {
	req := shared.PiGoRequest{
		Action: "wellcome",
		Params: map[string]string{"tech": "Golang"},
	}

	if req.Action != "wellcome" {
		t.Errorf("Expected action 'wellcome', got '%s'", req.Action)
	}

	if req.Params["tech"] != "Golang" {
		t.Errorf("Expected tech 'Golang', got '%s'", req.Params["tech"])
	}
}
