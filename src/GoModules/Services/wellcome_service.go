package services

import "fmt"

func GetWelcomeMessage(info string) string {
	return fmt.Sprintf("[Go Api Say] %s.", info)
}
