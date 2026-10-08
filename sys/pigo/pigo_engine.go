package main

import (
	"bufio"
	"bytes"
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"net"
	"net/http"
	"net/http/httputil"
	"net/url"
	"os"
	"os/signal"
	"path/filepath"
	"runtime"
	"strings"
	"syscall"
	"time"

	modules "abiesoft/src/Modules"
	shared "abiesoft/src/Shared/Helpers/Golang"
	"github.com/joho/godotenv"
)

func main() {
	// 1. Muat Environment
	_ = godotenv.Load(".env", "../.env", "../../.env", "./sys/pigo/.env")

	serverHost := os.Getenv("SERVER_HOST")
	if serverHost == "" {
		serverHost = "127.0.0.1"
	}

	serverPort := os.Getenv("SERVER_PORT")
	if serverPort == "" {
		serverPort = "3000"
	}

	phpPort := os.Getenv("PHP_PORT")
	if phpPort == "" {
		phpPort = "8002"
	}

	// 2. Hubungkan ke Database MySQL
	db := shared.ConnectDB()
	if db != nil {
		if err := db.Ping(); err != nil {
			log.Printf("Peringatan: Koneksi DB belum siap: %v\n", err)
		} else {
			log.Println("✔ Database MySQL terhubung.")
		}
		defer db.Close()
	}

	// 3. Setup Native HTTP Router untuk Go (/api-go/*)
	goMux := http.NewServeMux()
	modules.RegisterRoutes(goMux, db)

	// 4. Setup Reverse Proxy ke PHP Internal Server
	phpTargetURL := fmt.Sprintf("http://127.0.0.1:%s", phpPort)
	phpTarget, err := url.Parse(phpTargetURL)
	if err != nil {
		log.Fatalf("URL Backend PHP invalid: %v", err)
	}
	phpProxy := httputil.NewSingleHostReverseProxy(phpTarget)

	// Kustomisasi error proxy jika PHP belum aktif
	phpProxy.ErrorHandler = func(w http.ResponseWriter, r *http.Request, proxyErr error) {
		log.Printf("[Proxy PHP Error] %s -> %v\n", r.URL.Path, proxyErr)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadGateway)
		_ = json.NewEncoder(w).Encode(map[string]interface{}{
			"code":    502,
			"status":  "error",
			"message": fmt.Sprintf("PHP Backend Service belum berjalan pada port %s", phpPort),
		})
	}

	// 5. Gateway Dispatcher:
	// Jika URL dimulai dengan /api-go/ -> Tangani langsung oleh Go Native Mux!
	// Selain itu (misal /api/, /, assets, dll) -> Oper ke PHP Proxy!
	gatewayHandler := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		// CORS headers
		w.Header().Set("Access-Control-Allow-Origin", "*")
		w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization, X-Requested-With")

		if r.Method == http.MethodOptions {
			w.WriteHeader(http.StatusOK)
			return
		}

		cleanPath := r.URL.Path
		if strings.HasPrefix(cleanPath, "/api-go/") || cleanPath == "/api-go" {
			// NATIVE GOLANG ENGINE (100% Bebas dari PHP)
			goMux.ServeHTTP(w, r)
			return
		}

		// Rute lainnya (/api/*, web view, auth, latte templates) diarahkan ke PHP
		phpProxy.ServeHTTP(w, r)
	})

	// 6. Jalankan Legacy Socket Listener di Background Goroutine
	go startLegacySocketListener(db)

	// 7. Jalankan HTTP Gateway Server
	addr := fmt.Sprintf("%s:%s", serverHost, serverPort)
	server := &http.Server{
		Addr:    addr,
		Handler: gatewayHandler,
	}

	// Tangkap signal graceful shutdown
	stopChan := make(chan os.Signal, 1)
	signal.Notify(stopChan, os.Interrupt, syscall.SIGTERM)

	go func() {
		fmt.Printf("\n\033[32m✔ AbieSoft Gateway Aktif di http://%s\033[0m\n", addr)
		fmt.Printf("  • \033[36m[Golang Direct]\033[0m http://%s/api-go/ (Native High-Performance)\n", addr)
		fmt.Printf("  • \033[35m[PHP Core]\033[0m      http://%s/api/ dan Web Pages (via PHP internal :%s)\n\n", addr, phpPort)
		if err := server.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			log.Fatalf("Gagal menjalankan HTTP Gateway: %v", err)
		}
	}()

	<-stopChan
	log.Println("\nMematikan AbieSoft Gateway...")
	ctx, cancel := context.WithTimeout(context.Background(), 3*time.Second)
	defer cancel()
	_ = server.Shutdown(ctx)
	cleanupSocket()
	log.Println("AbieSoft Gateway berhasil dimatikan.")
}

func getSocketPath() string {
	candidates := []string{
		"sys/pigo/pigo.sock",
		"./sys/pigo/pigo.sock",
		"pigo.sock",
		"./../sys/pigo/pigo.sock",
	}
	for _, c := range candidates {
		dir := filepath.Dir(c)
		if fi, err := os.Stat(dir); err == nil && fi.IsDir() {
			return c
		}
	}
	return "sys/pigo/pigo.sock"
}

func cleanupSocket() {
	if runtime.GOOS != "windows" {
		_ = os.Remove(getSocketPath())
	}
}

func startLegacySocketListener(db *sql.DB) {
	var l net.Listener
	var err error

	if runtime.GOOS == "windows" {
		tcpAddr := "127.0.0.1:8081"
		l, err = net.Listen("tcp", tcpAddr)
		if err != nil {
			return
		}
	} else {
		sockPath := getSocketPath()
		_ = os.Remove(sockPath)
		l, err = net.Listen("unix", sockPath)
		if err != nil {
			return
		}
		_ = os.Chmod(sockPath, 0666)
	}
	defer l.Close()

	for {
		conn, err := l.Accept()
		if err != nil {
			return
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
