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

	err := godotenv.Load("./../.env")
	if err != nil {
		log.Println("Peringatan: File .env tidak ditemukan, menggunakan env system")
	}

	dbUser := os.Getenv("DB_USER")
	dbPass := os.Getenv("DB_PASS")
	dbHost := os.Getenv("DB_HOST")
	dbName := os.Getenv("DB_NAME")
	dbPort := os.Getenv("DB_PORT")

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

	err = db.Ping()
	if err != nil {
		log.Println("Database tidak merespon (Ping Gagal):", err)
	}

	return db
}
