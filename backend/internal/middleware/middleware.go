package middleware

import (
	"sync"
	"time"

	"github.com/gin-contrib/cors"
	"github.com/gin-gonic/gin"
	"github.com/google/uuid"
	"github.com/sirupsen/logrus"
)

func CORS() gin.HandlerFunc {
	return cors.New(cors.Config{
		AllowOrigins: []string{"*"},
		AllowMethods: []string{"GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"},
		AllowHeaders: []string{"Origin", "Content-Type", "Authorization", "X-Request-ID"},
	})
}

func SecurityHeaders() gin.HandlerFunc {
	return func(c *gin.Context) {
		c.Header("X-Content-Type-Options", "nosniff")
		c.Header("X-Frame-Options", "DENY")
		c.Header("Referrer-Policy", "strict-origin-when-cross-origin")
		c.Header("Content-Security-Policy", "default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline';")
		c.Next()
	}
}

func RequestID() gin.HandlerFunc {
	return func(c *gin.Context) {
		requestID := c.GetHeader("X-Request-ID")
		if requestID == "" {
			requestID = uuid.NewString()
		}
		c.Set("request_id", requestID)
		c.Writer.Header().Set("X-Request-ID", requestID)
		c.Next()
	}
}

func AccessLog(log *logrus.Logger) gin.HandlerFunc {
	return func(c *gin.Context) {
		start := time.Now()
		c.Next()
		log.WithFields(logrus.Fields{
			"method":     c.Request.Method,
			"path":       c.Request.URL.Path,
			"status":     c.Writer.Status(),
			"latency_ms": time.Since(start).Milliseconds(),
			"request_id": c.GetString("request_id"),
		}).Info("request complete")
	}
}

type clientLimiter struct {
	count      int
	windowEnds time.Time
}

func RateLimit(limit int, window time.Duration) gin.HandlerFunc {
	var (
		mu      sync.Mutex
		clients = map[string]*clientLimiter{}
	)

	return func(c *gin.Context) {
		ip := c.ClientIP()
		now := time.Now()

		mu.Lock()
		entry, exists := clients[ip]
		if !exists || now.After(entry.windowEnds) {
			entry = &clientLimiter{count: 0, windowEnds: now.Add(window)}
			clients[ip] = entry
		}
		entry.count++
		count := entry.count
		reset := entry.windowEnds
		mu.Unlock()

		if count > limit {
			c.Header("Retry-After", time.Until(reset).Round(time.Second).String())
			c.AbortWithStatusJSON(429, gin.H{"error": "rate limit exceeded"})
			return
		}
		c.Next()
	}
}
