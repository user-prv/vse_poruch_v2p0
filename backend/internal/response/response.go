package response

import "github.com/gin-gonic/gin"

type Envelope struct {
	Data  interface{} `json:"data,omitempty"`
	Error string      `json:"error,omitempty"`
}

func JSON(c *gin.Context, status int, data interface{}) {
	c.JSON(status, Envelope{Data: data})
}

func Error(c *gin.Context, status int, message string) {
	c.JSON(status, Envelope{Error: message})
}
