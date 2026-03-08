package modules

type User struct {
	ID        uint   `json:"id" gorm:"primaryKey;autoIncrement"`
	Email     string `json:"email" gorm:"size:255;uniqueIndex;not null"`
	Role      string `json:"role" gorm:"size:32;index;default:user"`
	IsBlocked bool   `json:"is_blocked" gorm:"not null;default:false"`
	Verified  bool   `json:"verified" gorm:"not null;default:false"`
}

type Category struct {
	ID       uint   `json:"id" gorm:"primaryKey;autoIncrement"`
	Name     string `json:"name" gorm:"not null"`
	ParentID *uint  `json:"parent_id"`
	IconPath string `json:"icon_path"`
}

type Listing struct {
	ID         uint     `json:"id" gorm:"primaryKey;autoIncrement"`
	Title      string   `json:"title" gorm:"size:255;index;not null"`
	Body       string   `json:"body"`
	AuthorID   uint     `json:"author_id" gorm:"not null"`
	CategoryID uint     `json:"category_id" gorm:"not null"`
	Price      uint     `json:"price" gorm:"not null;default:0"`
	Currency   string   `json:"currency" gorm:"size:8;not null;default:UAH"`
	Latitude   *float64 `json:"lat"`
	Longitude  *float64 `json:"lng"`
	Status     string   `json:"status" gorm:"size:32;index;default:pending"`
	PhotoPaths string   `json:"photo_paths" gorm:"type:text;default:'[]'"`
}
