package health

import (
	"net/http"

	"github.com/gin-gonic/gin"
	"github.com/vseporuch/v2/backend/internal/response"
	"gorm.io/gorm"
)

type Handler struct {
	db *gorm.DB
}

func NewHandler(db *gorm.DB) *Handler {
	return &Handler{db: db}
}

func (h *Handler) Health(c *gin.Context) {
	response.JSON(c, http.StatusOK, gin.H{"status": "ok"})
}

func (h *Handler) Ready(c *gin.Context) {
	sqlDB, err := h.db.DB()
	if err != nil {
		response.Error(c, http.StatusServiceUnavailable, "db unavailable")
		return
	}
	if err = sqlDB.Ping(); err != nil {
		response.Error(c, http.StatusServiceUnavailable, "db unavailable")
		return
	}
	response.JSON(c, http.StatusOK, gin.H{"status": "ready"})
}
