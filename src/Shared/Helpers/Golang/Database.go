package shared

import (
	"database/sql"
	"fmt"
	"log"
	"os"

	_ "github.com/go-sql-driver/mysql"
	"github.com/joho/godotenv"
)

func ConnectDB() *sql.DB {
	// Gunakan path absolut jika di Linux/Pixelbook agar lebih aman
	err := godotenv.Load("./../.env")
	if err != nil {
		log.Println("Peringatan: File .env tidak ditemukan, menggunakan env system")
	}

	dbUser := os.Getenv("DB_USER")
	dbPass := os.Getenv("DB_PASS")
	dbHost := os.Getenv("DB_HOST")
	dbName := os.Getenv("DB_NAME")
	dbPort := os.Getenv("DB_PORT")

	// Berikan default port jika kosong
	if dbPort == "" {
		dbPort = "3306"
	}

	dsn := fmt.Sprintf("%s:%s@tcp(%s:%s)/%s",
		dbUser, dbPass, dbHost, dbPort, dbName,
	)

	db, err := sql.Open("mysql", dsn)
	if err != nil {
		log.Println("Gagal membuka koneksi driver:", err)
		return nil
	}

	// Penting: Jangan biarkan Ping mematikan seluruh aplikasi
	err = db.Ping()
	if err != nil {
		log.Println("Database tidak merespon (Ping Gagal):", err)
		// Kita tetap return db agar variabel tidak nil,
		// tapi service nanti akan menangani error saat query.
	}

	return db
}
