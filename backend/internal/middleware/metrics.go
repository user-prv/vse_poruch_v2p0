package middleware

import (
	"expvar"
	"strconv"
	"time"

	"github.com/gin-gonic/gin"
)

var (
	httpRequests = expvar.NewMap("http_requests_total")
	httpLatency  = expvar.NewMap("http_request_latency_ms_total")
)

func Metrics() gin.HandlerFunc {
	return func(c *gin.Context) {
		start := time.Now()
		c.Next()
		path := c.FullPath()
		if path == "" {
			path = c.Request.URL.Path
		}
		key := c.Request.Method + " " + path + " " + strconv.Itoa(c.Writer.Status())
		httpRequests.Add(key, 1)
		httpLatency.Add(key, time.Since(start).Milliseconds())
	}
}
