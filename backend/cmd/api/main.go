package main

import (
	"expvar"
	"net/http"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/vseporuch/v2/backend/internal/config"
	"github.com/vseporuch/v2/backend/internal/db"
	"github.com/vseporuch/v2/backend/internal/health"
	"github.com/vseporuch/v2/backend/internal/logger"
	"github.com/vseporuch/v2/backend/internal/middleware"
	"github.com/vseporuch/v2/backend/internal/modules"
	"github.com/vseporuch/v2/backend/internal/response"
)

func main() {
	cfg := config.Load()
	log := logger.New(cfg)

	database, err := db.Connect(cfg)
	if err != nil {
		log.WithError(err).Fatal("failed to connect db")
	}

	r := gin.New()
	r.Use(middleware.CORS())
	r.Use(middleware.SecurityHeaders())
	r.Use(middleware.RequestID())
	r.Use(gin.Recovery())
	r.Use(middleware.Metrics())
	r.Use(middleware.AccessLog(log))

	r.GET("/metrics", gin.WrapH(expvar.Handler()))

	healthHandler := health.NewHandler(database)
	r.GET("/health", healthHandler.Health)
	r.GET("/ready", healthHandler.Ready)

	v1 := r.Group("/api/v1")
	v1.Use(middleware.RateLimit(300, time.Minute))
	v1.GET("/ping", func(c *gin.Context) {
		response.JSON(c, http.StatusOK, gin.H{"message": "pong"})
	})

	authGroup := v1.Group("/auth")
	authGroup.Use(middleware.RateLimit(40, time.Minute))

	if err = modules.RegisterRoutes(v1, authGroup, database, log); err != nil {
		log.WithError(err).Fatal("failed to init modules")
	}

	r.Static("/uploads", "./uploads")

	if err = r.Run(":" + cfg.Port); err != nil {
		log.WithError(err).Fatal("server stopped")
	}
}
