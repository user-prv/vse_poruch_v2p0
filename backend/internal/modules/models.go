package modules

type User struct {
	ID    uint   `gorm:"primaryKey"`
	Email string `gorm:"size:255;uniqueIndex;not null"`
	Role  string `gorm:"size:32;index;default:user"`
}

type Category struct {
	ID       uint   `gorm:"primaryKey"`
	Name     string `gorm:"not null"`
	ParentID *uint
	IconPath string
}

type Listing struct {
	ID         uint   `gorm:"primaryKey"`
	Title      string `gorm:"size:255;index;not null"`
	Body       string
	AuthorID   uint
	CategoryID uint
	Status     string `gorm:"size:32;index;default:pending"`
}
