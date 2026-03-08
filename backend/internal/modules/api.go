package modules

import (
	"encoding/json"
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

var allowedListingStatuses = map[string]struct{}{
	"active":  {},
	"blocked": {},
	"deleted": {},
	"pending": {},
}

var adminEmails = map[string]struct{}{
	"6353577@gmail.com": {},
}

func normalizeEmail(email string) string {
	return strings.ToLower(strings.TrimSpace(email))
}

func shouldAssignAdminRole(email string) bool {
	_, ok := adminEmails[normalizeEmail(email)]
	return ok
}

func normalizeStatus(status string) string {
	normalized := strings.ToLower(strings.TrimSpace(status))
	if normalized == "" {
		return "pending"
	}
	if _, ok := allowedListingStatuses[normalized]; ok {
		return normalized
	}
	return ""
}

func listingToResponse(listing Listing) gin.H {
	photoPaths := []string{}
	if listing.PhotoPaths != "" {
		_ = json.Unmarshal([]byte(listing.PhotoPaths), &photoPaths)
	}

	return gin.H{
		"id":          listing.ID,
		"title":       listing.Title,
		"body":        listing.Body,
		"author_id":   listing.AuthorID,
		"category_id": listing.CategoryID,
		"price":       listing.Price,
		"currency":    listing.Currency,
		"lat":         listing.Latitude,
		"lng":         listing.Longitude,
		"status":      listing.Status,
		"photo_paths": photoPaths,
	}
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

func parseBearerUserID(authHeader string) uint {
	value := strings.TrimSpace(authHeader)
	if value == "" {
		return 0
	}
	if strings.HasPrefix(strings.ToLower(value), "bearer ") {
		value = strings.TrimSpace(value[7:])
	}
	parts := strings.SplitN(value, "-", 2)
	if len(parts) != 2 || parts[0] != "user" {
		return 0
	}
	id, err := strconv.Atoi(parts[1])
	if err != nil || id <= 0 {
		return 0
	}
	return uint(id)
}

func (a *API) Register(c *gin.Context) {
	var req authRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		response.Error(c, http.StatusBadRequest, "invalid request")
		return
	}
	user := User{Email: normalizeEmail(req.Email), Role: "user"}
	if shouldAssignAdminRole(user.Email) {
		user.Role = "admin"
	}
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
	if err := a.db.Where("email = ?", normalizeEmail(req.Email)).First(&user).Error; err != nil {
		response.Error(c, http.StatusUnauthorized, "invalid credentials")
		return
	}
	if shouldAssignAdminRole(user.Email) && user.Role != "admin" {
		user.Role = "admin"
		if err := a.db.Model(&user).Update("role", "admin").Error; err != nil {
			response.Error(c, http.StatusInternalServerError, "failed to update user role")
			return
		}
	}
	if user.IsBlocked {
		response.Error(c, http.StatusForbidden, "user is blocked")
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
	Title      string   `json:"title" binding:"required,min=3"`
	Body       string   `json:"body"`
	AuthorID   uint     `json:"author_id"`
	CategoryID uint     `json:"category_id" binding:"required"`
	Price      uint     `json:"price"`
	Currency   string   `json:"currency"`
	Latitude   *float64 `json:"lat"`
	Longitude  *float64 `json:"lng"`
	Status     string   `json:"status"`
	PhotoPaths []string `json:"photo_paths"`
}

func (a *API) CreateListing(c *gin.Context) {
	var req listingRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		response.Error(c, http.StatusBadRequest, "invalid request")
		return
	}
	status := normalizeStatus(req.Status)
	if status == "" {
		response.Error(c, http.StatusBadRequest, "invalid status")
		return
	}
	authorID := req.AuthorID
	if authorID == 0 {
		authorID = parseBearerUserID(c.GetHeader("Authorization"))
	}
	if authorID == 0 {
		response.Error(c, http.StatusBadRequest, "author_id is required")
		return
	}
	var author User
	if err := a.db.First(&author, authorID).Error; err != nil {
		response.Error(c, http.StatusBadRequest, "author not found")
		return
	}
	if author.IsBlocked {
		response.Error(c, http.StatusForbidden, "author is blocked")
		return
	}
	var category Category
	if err := a.db.First(&category, req.CategoryID).Error; err != nil {
		response.Error(c, http.StatusBadRequest, "category not found")
		return
	}
	if status == "active" {
		status = "pending"
	}
	photoBytes, _ := json.Marshal(req.PhotoPaths)
	currency := strings.ToUpper(strings.TrimSpace(req.Currency))
	if currency == "" {
		currency = "UAH"
	}
	listing := Listing{Title: html.EscapeString(req.Title), Body: html.EscapeString(req.Body), AuthorID: authorID, CategoryID: req.CategoryID, Price: req.Price, Currency: currency, Latitude: req.Latitude, Longitude: req.Longitude, Status: status, PhotoPaths: string(photoBytes)}
	if err := a.db.Create(&listing).Error; err != nil {
		response.Error(c, http.StatusBadRequest, "cannot create listing")
		return
	}
	response.JSON(c, http.StatusCreated, listingToResponse(listing))
}

func (a *API) ListListings(c *gin.Context) {
	q := strings.TrimSpace(c.Query("q"))
	statusQuery := strings.TrimSpace(c.Query("status"))
	status := ""
	if statusQuery != "" {
		status = normalizeStatus(statusQuery)
	}
	authorID, _ := strconv.Atoi(c.Query("author_id"))
	if statusQuery != "" && status == "" {
		response.Error(c, http.StatusBadRequest, "invalid status")
		return
	}
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
	} else {
		dbq = dbq.Where("status = ?", "active")
	}
	if authorID > 0 {
		dbq = dbq.Where("author_id = ?", authorID)
	}
	var total int64
	dbq.Count(&total)
	var items []Listing
	if err := dbq.Offset((page - 1) * limit).Limit(limit).Order("id desc").Find(&items).Error; err != nil {
		response.Error(c, http.StatusInternalServerError, "failed")
		return
	}
	serialized := make([]gin.H, 0, len(items))
	for _, item := range items {
		serialized = append(serialized, listingToResponse(item))
	}
	response.JSON(c, http.StatusOK, gin.H{"items": serialized, "page": page, "limit": limit, "total": total})
}

func (a *API) GetListing(c *gin.Context) {
	var listing Listing
	if err := a.db.First(&listing, c.Param("id")).Error; err != nil {
		response.Error(c, http.StatusNotFound, "listing not found")
		return
	}
	response.JSON(c, http.StatusOK, listingToResponse(listing))
}

func (a *API) UpdateListing(c *gin.Context) {
	var req listingRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		response.Error(c, http.StatusBadRequest, "invalid request")
		return
	}
	status := normalizeStatus(req.Status)
	if status == "" {
		response.Error(c, http.StatusBadRequest, "invalid status")
		return
	}
	var listing Listing
	if err := a.db.First(&listing, c.Param("id")).Error; err != nil {
		response.Error(c, http.StatusNotFound, "listing not found")
		return
	}
	photoBytes, _ := json.Marshal(req.PhotoPaths)
	currency := strings.ToUpper(strings.TrimSpace(req.Currency))
	if currency == "" {
		currency = "UAH"
	}
	listing.Title, listing.Body, listing.AuthorID, listing.CategoryID, listing.Price, listing.Currency, listing.Latitude, listing.Longitude, listing.Status, listing.PhotoPaths = html.EscapeString(req.Title), html.EscapeString(req.Body), req.AuthorID, req.CategoryID, req.Price, currency, req.Latitude, req.Longitude, status, string(photoBytes)
	a.db.Save(&listing)
	response.JSON(c, http.StatusOK, listingToResponse(listing))
}

func (a *API) DeleteListing(c *gin.Context) {
	var listing Listing
	if err := a.db.First(&listing, c.Param("id")).Error; err != nil {
		response.Error(c, http.StatusNotFound, "listing not found")
		return
	}
	listing.Status = "deleted"
	a.db.Save(&listing)
	response.JSON(c, http.StatusOK, gin.H{"deleted": true, "status": listing.Status})
}

type categoryRequest struct {
	Name     string `json:"name" binding:"required"`
	ParentID *uint  `json:"parent_id"`
	IconPath string `json:"icon_path"`
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
		Status string `json:"status" binding:"required"`
	}
	if err := c.ShouldBindJSON(&payload); err != nil {
		response.Error(c, http.StatusBadRequest, "invalid request")
		return
	}
	status := normalizeStatus(payload.Status)
	if status == "" {
		response.Error(c, http.StatusBadRequest, "invalid status")
		return
	}
	var listing Listing
	if err := a.db.First(&listing, c.Param("id")).Error; err != nil {
		response.Error(c, http.StatusNotFound, "listing not found")
		return
	}
	listing.Status = status
	a.db.Save(&listing)
	a.log.WithFields(logrus.Fields{"event": "admin.moderate_listing", "listing_id": listing.ID, "status": listing.Status}).Info("audit")
	response.JSON(c, http.StatusOK, listingToResponse(listing))
}
func (a *API) VerifyUser(c *gin.Context) {
	a.log.WithFields(logrus.Fields{"event": "admin.verify_user", "user_id": c.Param("id")}).Info("audit")
	var user User
	if err := a.db.First(&user, c.Param("id")).Error; err != nil {
		response.Error(c, http.StatusNotFound, "user not found")
		return
	}
	user.Verified = true
	a.db.Save(&user)
	response.JSON(c, http.StatusOK, gin.H{"user_id": user.ID, "verified": user.Verified})
}
func (a *API) BlockUser(c *gin.Context) {
	a.log.WithFields(logrus.Fields{"event": "admin.block_user", "user_id": c.Param("id")}).Info("audit")
	var user User
	if err := a.db.First(&user, c.Param("id")).Error; err != nil {
		response.Error(c, http.StatusNotFound, "user not found")
		return
	}
	user.IsBlocked = true
	a.db.Save(&user)
	response.JSON(c, http.StatusOK, gin.H{"user_id": user.ID, "blocked": user.IsBlocked})
}
func (a *API) SetCategoryIcon(c *gin.Context) {
	a.log.WithFields(logrus.Fields{"event": "admin.set_category_icon", "category_id": c.Param("id")}).Info("audit")
	var payload struct {
		IconPath string `json:"icon_path" binding:"required"`
	}
	if err := c.ShouldBindJSON(&payload); err != nil {
		response.Error(c, http.StatusBadRequest, "invalid request")
		return
	}
	var category Category
	if err := a.db.First(&category, c.Param("id")).Error; err != nil {
		response.Error(c, http.StatusNotFound, "category not found")
		return
	}
	category.IconPath = strings.TrimSpace(payload.IconPath)
	a.db.Save(&category)
	response.JSON(c, http.StatusOK, gin.H{"category_id": category.ID, "updated": true, "icon_path": category.IconPath})
}
