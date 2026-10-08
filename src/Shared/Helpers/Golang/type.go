package shared

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
)

type PiGoRequest struct {
	Action    string            `json:"action"`
	Params    map[string]string `json:"params"`
	Timestamp int64             `json:"timestamp"`
}

type PiGoResponse struct {
	Code   int         `json:"code,omitempty"`
	Status string      `json:"status"`
	Data   interface{} `json:"data"`
	Msg    string      `json:"msg,omitempty"`
}

func JSONResponse(w http.ResponseWriter, code int, status string, data interface{}, msg string) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(code)
	resp := PiGoResponse{
		Code:   code,
		Status: status,
		Data:   data,
		Msg:    msg,
	}
	_ = json.NewEncoder(w).Encode(resp)
}

func SuccessResponse(w http.ResponseWriter, data interface{}) {
	JSONResponse(w, http.StatusOK, "success", data, "")
}

func ErrorResponse(w http.ResponseWriter, code int, msg string) {
	JSONResponse(w, code, "error", msg, msg)
}

func ExtractParams(r *http.Request) map[string]string {
	params := make(map[string]string)

	// 1. Path values (Go 1.22+)
	for _, key := range []string{"id", "offset", "limit", "info"} {
		if val := r.PathValue(key); val != "" {
			params[key] = val
		}
	}

	// 2. Query params
	for k, v := range r.URL.Query() {
		if len(v) > 0 {
			params[k] = v[0]
		}
	}

	// 3. Form data (baik multipart maupun urlencoded)
	_ = r.ParseMultipartForm(32 << 20)
	if r.MultipartForm != nil && r.MultipartForm.Value != nil {
		for k, v := range r.MultipartForm.Value {
			if len(v) > 0 {
				params[k] = v[0]
			}
		}
	}
	if r.PostForm != nil {
		for k, v := range r.PostForm {
			if len(v) > 0 {
				params[k] = v[0]
			}
		}
	}
	if r.Form != nil {
		for k, v := range r.Form {
			if len(v) > 0 {
				if _, exists := params[k]; !exists {
					params[k] = v[0]
				}
			}
		}
	}

	// 4. JSON body jika request application/json
	contentType := r.Header.Get("Content-Type")
	if strings.Contains(contentType, "application/json") {
		var jsonMap map[string]interface{}
		bodyBytes, err := io.ReadAll(r.Body)
		if err == nil && len(bodyBytes) > 0 {
			_ = json.Unmarshal(bodyBytes, &jsonMap)
			for k, val := range jsonMap {
				params[k] = fmt.Sprintf("%v", val)
			}
			// Restore body agar bisa dibaca lagi jika diperlukan
			r.Body = io.NopCloser(bytes.NewBuffer(bodyBytes))
		}
	}

	return params
}
