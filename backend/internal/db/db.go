package db

import (
	"fmt"
	"time"

	"github.com/vseporuch/v2/backend/internal/config"
	"gorm.io/driver/mysql"
	"gorm.io/gorm"
)

func Connect(cfg config.Config) (*gorm.DB, error) {
	dsn := fmt.Sprintf("%s:%s@tcp(%s:%s)/%s?parseTime=true&loc=Local&charset=utf8mb4&timeout=%ds",
		cfg.DBUser,
		cfg.DBPassword,
		cfg.DBHost,
		cfg.DBPort,
		cfg.DBName,
		int(cfg.DBConnTO.Seconds()),
	)

	var db *gorm.DB
	var err error
	for i := 0; i < cfg.DBRetryCount; i++ {
		db, err = gorm.Open(mysql.Open(dsn), &gorm.Config{})
		if err == nil {
			sqlDB, sqlErr := db.DB()
			if sqlErr != nil {
				return nil, sqlErr
			}
			sqlDB.SetMaxOpenConns(cfg.DBMaxOpen)
			sqlDB.SetMaxIdleConns(cfg.DBMaxIdle)
			sqlDB.SetConnMaxLifetime(time.Duration(cfg.DBMaxLifeMin) * time.Minute)
			return db, nil
		}
		time.Sleep(1 * time.Second)
	}
	return nil, err
}
