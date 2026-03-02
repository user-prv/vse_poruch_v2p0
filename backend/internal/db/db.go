package db

import (
	"fmt"
	"time"

	"github.com/vseporuch/v2/backend/internal/config"
	"gorm.io/driver/postgres"
	"gorm.io/gorm"
)

func Connect(cfg config.Config) (*gorm.DB, error) {
	dsn := fmt.Sprintf("host=%s port=%s user=%s password=%s dbname=%s sslmode=%s connect_timeout=%d",
		cfg.DBHost,
		cfg.DBPort,
		cfg.DBUser,
		cfg.DBPassword,
		cfg.DBName,
		cfg.DBSSLMode,
		int(cfg.DBConnTO.Seconds()),
	)

	var db *gorm.DB
	var err error
	for i := 0; i < cfg.DBRetryCount; i++ {
		db, err = gorm.Open(postgres.Open(dsn), &gorm.Config{})
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
