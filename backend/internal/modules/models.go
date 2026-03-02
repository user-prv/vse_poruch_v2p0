package modules

type User struct {
	ID    uint64 `gorm:"primaryKey;autoIncrement;type:bigint"`
	Email string `gorm:"size:255;uniqueIndex;not null"`
	Role  string `gorm:"size:32;index;default:user"`
}

type Category struct {
	ID       uint64  `gorm:"primaryKey;autoIncrement;type:bigint"`
	Name     string  `gorm:"not null"`
	ParentID *uint64 `gorm:"type:bigint"`
	IconPath string
}

type Listing struct {
	ID         uint64 `gorm:"primaryKey;autoIncrement;type:bigint"`
	Title      string `gorm:"size:255;index;not null"`
	Body       string
	AuthorID   uint64 `gorm:"type:bigint;not null"`
	CategoryID uint64 `gorm:"type:bigint;not null"`
	Status     string `gorm:"size:32;index;default:pending"`
}
