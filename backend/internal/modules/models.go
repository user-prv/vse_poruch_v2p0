package modules

type User struct {
	ID    uint   `gorm:"primaryKey"`
	Email string `gorm:"uniqueIndex;not null"`
	Role  string `gorm:"index;default:user"`
}

type Category struct {
	ID       uint   `gorm:"primaryKey"`
	Name     string `gorm:"not null"`
	ParentID *uint
	IconPath string
}

type Listing struct {
	ID         uint   `gorm:"primaryKey"`
	Title      string `gorm:"index;not null"`
	Body       string
	AuthorID   uint
	CategoryID uint
	Status     string `gorm:"index;default:pending"`
}
