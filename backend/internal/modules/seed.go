package modules

import (
	"errors"
	"fmt"
	"os"
	"path/filepath"
	"strings"

	"github.com/sirupsen/logrus"
	"gorm.io/gorm"
)

var seedSQLCandidates = []string{
	"seed.sql",
	filepath.Join("backend", "seed.sql"),
}

func AutoSeedFromFile(db *gorm.DB, log *logrus.Logger) error {
	if db == nil {
		return errors.New("db is nil")
	}
	if log == nil {
		return errors.New("log is nil")
	}

	seedPath, err := findSeedSQLPath()
	if err != nil {
		if errors.Is(err, os.ErrNotExist) {
			log.WithField("event", "seed.skip").Info("seed.sql not found, skipping test data load")
			return nil
		}
		return err
	}

	contentBytes, err := os.ReadFile(seedPath)
	if err != nil {
		return fmt.Errorf("read %s: %w", seedPath, err)
	}
	content := strings.TrimSpace(string(contentBytes))
	if content == "" {
		log.WithFields(logrus.Fields{"event": "seed.skip", "path": seedPath}).Info("seed.sql is empty, skipping test data load")
		return nil
	}

	var usersCount, categoriesCount, listingsCount int64
	if err := db.Model(&User{}).Count(&usersCount).Error; err != nil {
		return fmt.Errorf("count users: %w", err)
	}
	if err := db.Model(&Category{}).Count(&categoriesCount).Error; err != nil {
		return fmt.Errorf("count categories: %w", err)
	}
	if err := db.Model(&Listing{}).Count(&listingsCount).Error; err != nil {
		return fmt.Errorf("count listings: %w", err)
	}

	if usersCount > 0 || categoriesCount > 0 || listingsCount > 0 {
		log.WithFields(logrus.Fields{
			"event":            "seed.skip",
			"path":             seedPath,
			"users_count":      usersCount,
			"categories_count": categoriesCount,
			"listings_count":   listingsCount,
		}).Info("database already has data, skipping seed.sql load")
		return nil
	}

	if err := db.Exec(content).Error; err != nil {
		return fmt.Errorf("exec %s: %w", seedPath, err)
	}

	log.WithFields(logrus.Fields{"event": "seed.loaded", "path": seedPath}).Info("seed.sql loaded into database")
	return nil
}

func findSeedSQLPath() (string, error) {
	for _, candidate := range seedSQLCandidates {
		if _, err := os.Stat(candidate); err == nil {
			return candidate, nil
		}
	}
	return "", os.ErrNotExist
}
