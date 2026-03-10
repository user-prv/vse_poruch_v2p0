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
	ID              uint     `json:"id" gorm:"primaryKey;autoIncrement"`
	Title           string   `json:"title" gorm:"size:255;index;not null"`
	Body            string   `json:"body"`
	AuthorID        uint     `json:"author_id" gorm:"not null"`
	CategoryID      uint     `json:"category_id" gorm:"not null"`
	Price           uint     `json:"price" gorm:"not null;default:0"`
	Currency        string   `json:"currency" gorm:"size:8;not null;default:UAH"`
	Latitude        *float64 `json:"lat"`
	Longitude       *float64 `json:"lng"`
	Status          string   `json:"status" gorm:"size:32;index;default:draft"`
	RejectionReason string   `json:"rejection_reason" gorm:"size:500"`
	UpdatedByUserID *uint    `json:"updated_by_user_id"`
	PhotoPaths      string   `json:"photo_paths" gorm:"type:text;default:'[]'"`
	CreatedAt       int64    `json:"created_at" gorm:"autoCreateTime:milli"`
	UpdatedAt       int64    `json:"updated_at" gorm:"autoUpdateTime:milli"`
}

type ListingStatusHistory struct {
	ID         uint   `json:"id" gorm:"primaryKey;autoIncrement"`
	ListingID  uint   `json:"listing_id" gorm:"index;not null"`
	FromStatus string `json:"from_status" gorm:"size:32;index"`
	ToStatus   string `json:"to_status" gorm:"size:32;index;not null"`
	Reason     string `json:"reason" gorm:"size:500"`
	ChangedBy  uint   `json:"changed_by" gorm:"not null;default:0"`
	CreatedAt  int64  `json:"created_at" gorm:"autoCreateTime:milli"`
}

type Notification struct {
	ID        uint   `json:"id" gorm:"primaryKey;autoIncrement"`
	UserID    uint   `json:"user_id" gorm:"index;not null"`
	Message   string `json:"message" gorm:"size:500;not null"`
	CreatedAt int64  `json:"created_at" gorm:"autoCreateTime:milli"`
}
