package main

import (
	"net/http"

	"github.com/gin-gonic/gin"
	"github.com/sirupsen/logrus"
	"github.com/vseporuch/v2/backend/internal/config"
	"github.com/vseporuch/v2/backend/internal/db"
	"github.com/vseporuch/v2/backend/internal/health"
	"github.com/vseporuch/v2/backend/internal/middleware"
	"github.com/vseporuch/v2/backend/internal/modules"
	"github.com/vseporuch/v2/backend/internal/response"
)

func main() {
	cfg := config.Load()
	log := logger()

	database, err := db.Connect(cfg)
	if err != nil {
		log.WithError(err).Fatal("failed to connect db")
	}

	r := gin.New()
	r.Use(middleware.CORS())
	r.Use(middleware.RequestID())
	r.Use(gin.Recovery())
	r.Use(middleware.AccessLog(log))

	healthHandler := health.NewHandler(database)
	r.GET("/health", healthHandler.Health)
	r.GET("/ready", healthHandler.Ready)

	v1 := r.Group("/api/v1")
	v1.GET("/ping", func(c *gin.Context) {
		response.JSON(c, http.StatusOK, gin.H{"message": "pong"})
	})

	if err = modules.RegisterRoutes(v1, database); err != nil {
		log.WithError(err).Fatal("failed to init modules")
	}

	r.Static("/uploads", "./uploads")

	if err = r.Run(":" + cfg.Port); err != nil {
		log.WithError(err).Fatal("server stopped")
	}
}

func logger() *logrus.Logger {
	log := logrus.New()
	log.SetFormatter(&logrus.JSONFormatter{})
	return log
}
