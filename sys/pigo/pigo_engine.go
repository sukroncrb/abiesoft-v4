package main

import (
	"encoding/json"
	"fmt"
	"net"
	"os"

	modules "abiesoft/src/Modules"
	shared "abiesoft/src/Shared"
)

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
		conn, err := l.Accept()
		if err != nil {
			continue
		}

		buf := make([]byte, 4096)
		n, _ := conn.Read(buf)

		var req shared.PiGoRequest
		json.Unmarshal(buf[:n], &req)

		res := modules.HandleRequest(req)

		finalRes, _ := json.Marshal(res)
		conn.Write(finalRes)
		conn.Close()
	}
}
