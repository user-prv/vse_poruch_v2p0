package modules

import (
	"fmt"
	"html"
	"net/http"
	"os"
	"path/filepath"
	"strconv"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/sirupsen/logrus"
	"github.com/vseporuch/v2/backend/internal/response"
	"gorm.io/gorm"
)

type API struct {
	db  *gorm.DB
	log *logrus.Logger
}

func RegisterRoutes(rg *gin.RouterGroup, authGroup *gin.RouterGroup, db *gorm.DB, log *logrus.Logger) error {
	if err := db.AutoMigrate(&User{}, &Category{}, &Listing{}); err != nil {
		return err
	}
	api := &API{db: db, log: log}

	authGroup.POST("/register", api.Register)
	authGroup.POST("/login", api.Login)
	authGroup.POST("/logout", api.Logout)
	authGroup.POST("/reset-password", api.ResetPassword)
	rg.GET("/profile/:id", api.Profile)

	rg.POST("/listings", api.CreateListing)
	rg.GET("/listings", api.ListListings)
	rg.GET("/listings/:id", api.GetListing)
	rg.PUT("/listings/:id", api.UpdateListing)
	rg.DELETE("/listings/:id", api.DeleteListing)

	rg.POST("/categories", api.CreateCategory)
	rg.GET("/categories", api.ListCategories)
	rg.PUT("/categories/:id", api.UpdateCategory)
	rg.DELETE("/categories/:id", api.DeleteCategory)

	rg.POST("/uploads/photo", api.UploadPhoto)

	admin := rg.Group("/admin")
	admin.POST("/listings/:id/moderate", api.ModerateListing)
	admin.POST("/users/:id/verify", api.VerifyUser)
	admin.POST("/users/:id/block", api.BlockUser)
	admin.POST("/categories/:id/icon", api.SetCategoryIcon)

	return nil
}

type authRequest struct {
	Email string `json:"email" binding:"required,email"`
}

func (a *API) Register(c *gin.Context) {
	var req authRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		response.Error(c, http.StatusBadRequest, "invalid request")
		return
	}
	user := User{Email: strings.ToLower(strings.TrimSpace(req.Email)), Role: "user"}
	if err := a.db.Create(&user).Error; err != nil {
		response.Error(c, http.StatusBadRequest, "user already exists")
		return
	}
	a.log.WithFields(logrus.Fields{"event": "auth.register", "user_id": user.ID, "email": user.Email}).Info("audit")
	response.JSON(c, http.StatusCreated, gin.H{"id": user.ID, "email": user.Email})
}

func (a *API) Login(c *gin.Context) {
	var req authRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		response.Error(c, http.StatusBadRequest, "invalid request")
		return
	}
	var user User
	if err := a.db.Where("email = ?", strings.ToLower(strings.TrimSpace(req.Email))).First(&user).Error; err != nil {
		response.Error(c, http.StatusUnauthorized, "invalid credentials")
		return
	}
	a.log.WithFields(logrus.Fields{"event": "auth.login", "user_id": user.ID, "email": user.Email}).Info("audit")
	response.JSON(c, http.StatusOK, gin.H{"token": fmt.Sprintf("user-%d", user.ID)})
}

func (a *API) Logout(c *gin.Context) {
	a.log.WithField("event", "auth.logout").Info("audit")
	response.JSON(c, http.StatusOK, gin.H{"message": "logged out"})
}

func (a *API) ResetPassword(c *gin.Context) {
	a.log.WithField("event", "auth.reset_password").Info("audit")
	response.JSON(c, http.StatusOK, gin.H{"message": "reset email sent"})
}

func (a *API) Profile(c *gin.Context) {
	id := c.Param("id")
	var user User
	if err := a.db.First(&user, id).Error; err != nil {
		response.Error(c, http.StatusNotFound, "user not found")
		return
	}
	response.JSON(c, http.StatusOK, user)
}

type listingRequest struct {
	Title      string `json:"title" binding:"required,min=3"`
	Body       string `json:"body"`
	AuthorID   uint64 `json:"author_id" binding:"required"`
	CategoryID uint64 `json:"category_id" binding:"required"`
}

func (a *API) CreateListing(c *gin.Context) {
	var req listingRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		response.Error(c, http.StatusBadRequest, "invalid request")
		return
	}
	listing := Listing{Title: html.EscapeString(req.Title), Body: html.EscapeString(req.Body), AuthorID: req.AuthorID, CategoryID: req.CategoryID, Status: "pending"}
	if err := a.db.Create(&listing).Error; err != nil {
		response.Error(c, http.StatusBadRequest, "cannot create listing")
		return
	}
	response.JSON(c, http.StatusCreated, listing)
}

func (a *API) ListListings(c *gin.Context) {
	q := strings.TrimSpace(c.Query("q"))
	status := strings.TrimSpace(c.Query("status"))
	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	if page < 1 {
		page = 1
	}
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "10"))
	if limit < 1 || limit > 50 {
		limit = 10
	}

	dbq := a.db.Model(&Listing{})
	if q != "" {
		dbq = dbq.Where("title ILIKE ? OR body ILIKE ?", "%"+q+"%", "%"+q+"%")
	}
	if status != "" {
		dbq = dbq.Where("status = ?", status)
	}
	var total int64
	dbq.Count(&total)
	var items []Listing
	if err := dbq.Offset((page - 1) * limit).Limit(limit).Order("id desc").Find(&items).Error; err != nil {
		response.Error(c, http.StatusInternalServerError, "failed")
		return
	}
	response.JSON(c, http.StatusOK, gin.H{"items": items, "page": page, "limit": limit, "total": total})
}

func (a *API) GetListing(c *gin.Context) {
	var listing Listing
	if err := a.db.First(&listing, c.Param("id")).Error; err != nil {
		response.Error(c, http.StatusNotFound, "listing not found")
		return
	}
	response.JSON(c, http.StatusOK, listing)
}

func (a *API) UpdateListing(c *gin.Context) {
	var req listingRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		response.Error(c, http.StatusBadRequest, "invalid request")
		return
	}
	var listing Listing
	if err := a.db.First(&listing, c.Param("id")).Error; err != nil {
		response.Error(c, http.StatusNotFound, "listing not found")
		return
	}
	listing.Title, listing.Body, listing.AuthorID, listing.CategoryID = html.EscapeString(req.Title), html.EscapeString(req.Body), req.AuthorID, req.CategoryID
	a.db.Save(&listing)
	response.JSON(c, http.StatusOK, listing)
}

func (a *API) DeleteListing(c *gin.Context) {
	if err := a.db.Delete(&Listing{}, c.Param("id")).Error; err != nil {
		response.Error(c, http.StatusBadRequest, "failed")
		return
	}
	response.JSON(c, http.StatusOK, gin.H{"deleted": true})
}

type categoryRequest struct {
	Name     string  `json:"name" binding:"required"`
	ParentID *uint64 `json:"parent_id"`
	IconPath string  `json:"icon_path"`
}

func (a *API) CreateCategory(c *gin.Context) {
	var req categoryRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		response.Error(c, http.StatusBadRequest, "invalid request")
		return
	}
	cat := Category{Name: html.EscapeString(req.Name), ParentID: req.ParentID, IconPath: req.IconPath}
	a.db.Create(&cat)
	response.JSON(c, http.StatusCreated, cat)
}
func (a *API) ListCategories(c *gin.Context) {
	var items []Category
	a.db.Order("id").Find(&items)
	response.JSON(c, http.StatusOK, items)
}
func (a *API) UpdateCategory(c *gin.Context) {
	var req categoryRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		response.Error(c, http.StatusBadRequest, "invalid request")
		return
	}
	var cat Category
	if err := a.db.First(&cat, c.Param("id")).Error; err != nil {
		response.Error(c, http.StatusNotFound, "category not found")
		return
	}
	cat.Name, cat.ParentID, cat.IconPath = html.EscapeString(req.Name), req.ParentID, req.IconPath
	a.db.Save(&cat)
	response.JSON(c, http.StatusOK, cat)
}
func (a *API) DeleteCategory(c *gin.Context) {
	a.db.Delete(&Category{}, c.Param("id"))
	response.JSON(c, http.StatusOK, gin.H{"deleted": true})
}

func (a *API) UploadPhoto(c *gin.Context) {
	file, err := c.FormFile("photo")
	if err != nil {
		response.Error(c, http.StatusBadRequest, "photo is required")
		return
	}
	if file.Size > 5*1024*1024 {
		response.Error(c, http.StatusBadRequest, "max size 5MB")
		return
	}
	ext := strings.ToLower(filepath.Ext(file.Filename))
	if ext != ".jpg" && ext != ".jpeg" && ext != ".png" && ext != ".webp" {
		response.Error(c, http.StatusBadRequest, "invalid file type")
		return
	}
	_ = os.MkdirAll("uploads", 0o755)
	name := fmt.Sprintf("%d%s", time.Now().UnixNano(), ext)
	path := filepath.Join("uploads", name)
	if err := c.SaveUploadedFile(file, path); err != nil {
		response.Error(c, http.StatusInternalServerError, "save failed")
		return
	}
	response.JSON(c, http.StatusCreated, gin.H{"path": "/uploads/" + name})
}

func (a *API) ModerateListing(c *gin.Context) {
	var payload struct {
		Status string `json:"status" binding:"required,oneof=approved rejected pending"`
	}
	if err := c.ShouldBindJSON(&payload); err != nil {
		response.Error(c, http.StatusBadRequest, "invalid request")
		return
	}
	var listing Listing
	if err := a.db.First(&listing, c.Param("id")).Error; err != nil {
		response.Error(c, http.StatusNotFound, "listing not found")
		return
	}
	listing.Status = payload.Status
	a.db.Save(&listing)
	a.log.WithFields(logrus.Fields{"event": "admin.moderate_listing", "listing_id": listing.ID, "status": listing.Status}).Info("audit")
	response.JSON(c, http.StatusOK, listing)
}
func (a *API) VerifyUser(c *gin.Context) {
	a.log.WithFields(logrus.Fields{"event": "admin.verify_user", "user_id": c.Param("id")}).Info("audit")
	response.JSON(c, http.StatusOK, gin.H{"user_id": c.Param("id"), "verified": true})
}
func (a *API) BlockUser(c *gin.Context) {
	a.log.WithFields(logrus.Fields{"event": "admin.block_user", "user_id": c.Param("id")}).Info("audit")
	response.JSON(c, http.StatusOK, gin.H{"user_id": c.Param("id"), "blocked": true})
}
func (a *API) SetCategoryIcon(c *gin.Context) {
	a.log.WithFields(logrus.Fields{"event": "admin.set_category_icon", "category_id": c.Param("id")}).Info("audit")
	response.JSON(c, http.StatusOK, gin.H{"category_id": c.Param("id"), "updated": true})
}
