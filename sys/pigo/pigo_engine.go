package main

import (
	"encoding/json"
	"fmt"
	"net"
	"os"
)

type PiGoRequest struct {
	Action    string            `json:"action"`
	Params    map[string]string `json:"params"`
	Timestamp int64             `json:"timestamp"`
}

type PiGoResponse struct {
	Status string      `json:"status"`
	Data   interface{} `json:"data"`
	Msg    string      `json:"msg"`
}

func main() {
	socketPath := "./../sys/pigo/pigo.sock"
	os.Remove(socketPath)
	l, err := net.Listen("unix", socketPath)
	if err != nil {
		fmt.Printf("Gagal membuat socket di %s: %v\n", socketPath, err)
		return
	}
	defer l.Close()
	os.Chmod(socketPath, 0777)

	for {
		conn, _ := l.Accept()
		buf := make([]byte, 4096)
		n, _ := conn.Read(buf)

		var req PiGoRequest
		json.Unmarshal(buf[:n], &req)

		var res PiGoResponse

		switch req.Action {
		case "wellcome":
			paramInfo := req.Params["info"]
			res.Status = "success"
			res.Data = fmt.Sprintf("[Go Api Say] %s.", paramInfo)
		default:
			res.Status = "error"
			res.Msg = "404 | Not Found"
		}

		finalRes, _ := json.Marshal(res)
		conn.Write(finalRes)
		conn.Close()
	}
}
