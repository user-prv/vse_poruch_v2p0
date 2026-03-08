package modules

type User struct {
	ID    uint   `gorm:"primaryKey;autoIncrement"`
	Email string `gorm:"size:255;uniqueIndex;not null"`
	Role  string `gorm:"size:32;index;default:user"`
}

type Category struct {
	ID       uint   `gorm:"primaryKey;autoIncrement"`
	Name     string `gorm:"not null"`
	ParentID *uint
	IconPath string
}

type Listing struct {
	ID         uint   `gorm:"primaryKey;autoIncrement"`
	Title      string `gorm:"size:255;index;not null"`
	Body       string
	AuthorID   uint   `gorm:"not null"`
	CategoryID uint   `gorm:"not null"`
	Price      uint   `gorm:"not null;default:0"`
	Currency   string `gorm:"size:8;not null;default:UAH"`
	Latitude   *float64
	Longitude  *float64
	Status     string `gorm:"size:32;index;default:pending"`
	PhotoPaths string `gorm:"type:text;default:'[]'"`
}
