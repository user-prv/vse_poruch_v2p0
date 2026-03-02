package main

import (
	"net/http"

	authhandler "vseporuch/backend/internal/modules/auth/handler"
	userhandler "vseporuch/backend/internal/modules/users/handler"
	"vseporuch/backend/internal/modules/users/repository"
	"vseporuch/backend/internal/modules/users/service"

	"vseporuch/backend/internal/config"
	"vseporuch/backend/internal/db"
	"vseporuch/backend/internal/logger"
	"vseporuch/backend/internal/middleware"

	"github.com/gin-contrib/cors"
	"github.com/gin-gonic/gin"
	"github.com/joho/godotenv"
)

func main() {
	_ = godotenv.Load()
	cfg := config.Load()
	log := logger.New(cfg.LogLevel)

	database, err := db.Connect(cfg)
	if err != nil {
		log.WithError(err).Fatal("failed to connect database")
	}

	if cfg.AppEnv == "production" {
		gin.SetMode(gin.ReleaseMode)
	}

	r := gin.New()
	r.Use(gin.Recovery())
	r.Use(cors.Default())
	r.Use(middleware.RequestID())
	r.Use(middleware.RequestLogger(log))

	r.GET("/health", func(c *gin.Context) {
		sqlDB, err := database.DB()
		if err != nil || sqlDB.Ping() != nil {
			c.JSON(http.StatusServiceUnavailable, gin.H{"status": "down"})
			return
		}
		c.JSON(http.StatusOK, gin.H{"status": "ok"})
	})

	api := r.Group("/api/v1")
	authhandler.NewAuthHandler().RegisterRoutes(api)

	userRepo := repository.NewUserRepository(database)
	userService := service.NewUserService(userRepo)
	userhandler.NewUserHandler(userService).RegisterRoutes(api)

	if err := r.Run(":" + cfg.AppPort); err != nil {
		log.WithError(err).Fatal("server exited")
	}
}
