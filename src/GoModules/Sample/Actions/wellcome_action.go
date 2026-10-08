package actions

import (
	services "abiesoft/src/GoModules/Sample/Services"
	shared "abiesoft/src/Shared/Helpers/Golang"
	"crypto/rand"
	"database/sql"
	"fmt"
	"net/http"
	"strings"
	"time"
)

func generateSimpleUUID() string {
	b := make([]byte, 16)
	_, err := rand.Read(b)
	if err != nil {
		return fmt.Sprintf("%d", time.Now().UnixNano())
	}
	return fmt.Sprintf("%x-%x-%x-%x-%x", b[0:4], b[4:6], b[6:8], b[8:10], b[10:])
}

func GetAllSampleHTTPHandler(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var res shared.PiGoResponse
		req := shared.PiGoRequest{
			Action: "sample-all-data",
			Params: shared.ExtractParams(r),
		}
		result := services.GetAllSampleService(res, db, req)
		if result.Status == "error" {
			shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
			return
		}
		shared.SuccessResponse(w, result.Data)
	}
}

func GetOnlySampleHTTPHandler(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var res shared.PiGoResponse
		params := shared.ExtractParams(r)
		if id := r.PathValue("id"); id != "" {
			params["id"] = id
		}
		req := shared.PiGoRequest{
			Action: "sample-only-data",
			Params: params,
		}
		result := services.GetOnlySampleService(res, db, req)
		if result.Status == "error" {
			shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
			return
		}
		shared.SuccessResponse(w, result.Data)
	}
}

func GetBigDataSampleHTTPHandler(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var res shared.PiGoResponse
		params := shared.ExtractParams(r)
		if offset := r.PathValue("offset"); offset != "" {
			params["offset"] = offset
		}
		if limit := r.PathValue("limit"); limit != "" {
			params["limit"] = limit
		}
		req := shared.PiGoRequest{
			Action: "sample-big-data",
			Params: params,
		}
		result := services.GetSampleBigDataService(res, db, req)
		if result.Status == "error" {
			shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
			return
		}
		shared.SuccessResponse(w, result.Data)
	}
}

func SaveSampleHTTPHandler(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var res shared.PiGoResponse
		params := shared.ExtractParams(r)

		methodOverride := strings.ToUpper(params["__method"])
		id := params["id"]

		// Normalisasi parameter tech
		tech := strings.ToLower(strings.TrimSpace(params["tech"]))
		if tech == "on" || tech == "golang" {
			params["tech"] = "Golang"
		} else {
			params["tech"] = "PHP"
		}

		if methodOverride == "DELETE" && id != "" {
			req := shared.PiGoRequest{
				Action: "delete-sample",
				Params: params,
			}
			result := services.DeleteSampleService(res, db, req)
			if result.Status == "error" {
				shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
				return
			}
			shared.SuccessResponse(w, result.Msg)
			return
		}

		if strings.TrimSpace(params["nama"]) == "" {
			shared.ErrorResponse(w, http.StatusBadRequest, "Field nama tidak boleh kosong")
			return
		}

		if id != "" {
			req := shared.PiGoRequest{
				Action: "update-sample",
				Params: params,
			}
			result := services.UpdateSampleService(res, db, req)
			if result.Status == "error" {
				shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
				return
			}
			shared.SuccessResponse(w, result.Msg)
			return
		}

		// Create
		if params["uuid"] == "" {
			params["uuid"] = generateSimpleUUID()
		}

		req := shared.PiGoRequest{
			Action: "post-sample",
			Params: params,
		}
		result := services.CreateSampleService(res, db, req)
		if result.Status == "error" {
			shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
			return
		}
		shared.SuccessResponse(w, result.Data)
	}
}

func UpdateSampleHTTPHandler(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var res shared.PiGoResponse
		params := shared.ExtractParams(r)
		if id := r.PathValue("id"); id != "" {
			params["id"] = id
		}
		tech := strings.ToLower(strings.TrimSpace(params["tech"]))
		if tech == "on" || tech == "golang" {
			params["tech"] = "Golang"
		} else {
			params["tech"] = "PHP"
		}
		req := shared.PiGoRequest{
			Action: "update-sample",
			Params: params,
		}
		result := services.UpdateSampleService(res, db, req)
		if result.Status == "error" {
			shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
			return
		}
		shared.SuccessResponse(w, result.Msg)
	}
}

func DeleteSampleHTTPHandler(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var res shared.PiGoResponse
		params := shared.ExtractParams(r)
		if id := r.PathValue("id"); id != "" {
			params["id"] = id
		}
		req := shared.PiGoRequest{
			Action: "delete-sample",
			Params: params,
		}
		result := services.DeleteSampleService(res, db, req)
		if result.Status == "error" {
			shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
			return
		}
		shared.SuccessResponse(w, result.Msg)
	}
}

func HandleSampleAction(req shared.PiGoRequest, db *sql.DB) shared.PiGoResponse {
	var res shared.PiGoResponse

	switch req.Action {
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
		res.Msg = "Action Sample Tidak Terdaftar"
	}

	return res
}
