package main

import (
	"bufio"
	"bytes"
	"database/sql"
	"encoding/json"
	"fmt"
	"net"
	"os"
	"os/signal"
	"runtime"
	"syscall"

	modules "abiesoft/src/Modules"
	shared "abiesoft/src/Shared/Helpers/Golang"
)

func main() {
	var l net.Listener
	var err error

	if runtime.GOOS == "windows" {

		tcpAddr := "127.0.0.1:8081"
		l, err = net.Listen("tcp", tcpAddr)
		if err != nil {
			fmt.Printf("Gagal membuat TCP Listener di %s: %v\n", tcpAddr, err)
			return
		}
		fmt.Printf("Go Engine berjalan di Windows menggunakan TCP: %s\n", tcpAddr)
	} else {

		socketPath := "./../sys/pigo/pigo.sock"
		os.Remove(socketPath)

		l, err = net.Listen("unix", socketPath)
		if err != nil {
			fmt.Printf("Gagal membuat Unix socket di %s: %v\n", socketPath, err)
			return
		}
		os.Chmod(socketPath, 0666)
		fmt.Printf("Go Engine berjalan di Linux menggunakan Unix Socket: %s\n", socketPath)
	}
	defer l.Close()

	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, syscall.SIGINT, syscall.SIGTERM)
	go func() {
		<-sigChan
		l.Close()
		if runtime.GOOS != "windows" {
			os.Remove("./../sys/pigo/pigo.sock")
		}
		os.Exit(0)
	}()

	// 3. Hubungkan ke Database MySQL melalui Core Helper AbieSoft
	db := shared.ConnectDB()
	if err := db.Ping(); err != nil {
		fmt.Printf("Koneksi DB Gagal: %v\n", err)
	}
	defer db.Close()

	// 4. Loop tanpa henti untuk menerima koneksi masuk dari PHP
	for {
		conn, err := l.Accept()
		if err != nil {
			continue
		}
		go handleConnection(conn, db)
	}
}

func handleConnection(conn net.Conn, db *sql.DB) {
	defer conn.Close()

	reader := bufio.NewReader(conn)
	buf, err := reader.ReadBytes('\n')
	if err != nil || len(buf) == 0 {
		return
	}

	cleanBuf := bytes.ReplaceAll(buf, []byte("\r"), []byte(""))
	cleanBuf = bytes.ReplaceAll(cleanBuf, []byte("\n"), []byte(""))
	cleanBuf = bytes.Trim(cleanBuf, "\x00 ")

	if len(cleanBuf) == 0 {
		return
	}

	var req shared.PiGoRequest
	err = json.Unmarshal(cleanBuf, &req)
	if err != nil {
		resErr, _ := json.Marshal(map[string]interface{}{
			"status": "error",
			"msg":    "Invalid JSON request payload: " + err.Error(),
		})
		conn.Write(resErr)
		return
	}

	res := modules.HandleRequest(req, db)

	finalRes, err := json.Marshal(res)
	if err != nil {
		resErr, _ := json.Marshal(map[string]interface{}{"status": "error", "msg": "Failed to marshal Go response"})
		conn.Write(resErr)
		return
	}

	conn.Write(append(finalRes, '\n'))
}
