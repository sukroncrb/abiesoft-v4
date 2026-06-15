package dto

type SampleDto struct {
	ID   int    `json:"id"`
	Nama string `json:"nama"`
	Tech string `json:"tech,omitempty"`
}
