package shared

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
