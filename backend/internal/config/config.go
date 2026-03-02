package config

import (
	"os"
	"strconv"
	"time"
)

type Config struct {
	Port         string
	LogFormat    string
	LogLevel     string
	DBHost       string
	DBPort       string
	DBUser       string
	DBPassword   string
	DBName       string
	DBSSLMode    string
	DBMaxOpen    int
	DBMaxIdle    int
	DBMaxLifeMin int
	DBConnTO     time.Duration
	DBRetryCount int
}

func Load() Config {
	return Config{
		Port:         getEnv("PORT", "8080"),
		LogFormat:    getEnv("LOG_FORMAT", "json"),
		LogLevel:     getEnv("LOG_LEVEL", "info"),
		DBHost:       getEnv("DB_HOST", "127.0.0.1"),
		DBPort:       getEnv("DB_PORT", "5432"),
		DBUser:       getEnv("DB_USER", "postgres"),
		DBPassword:   getEnv("DB_PASSWORD", "postgres"),
		DBName:       getEnv("DB_NAME", "vseporuch"),
		DBSSLMode:    getEnv("DB_SSLMODE", "disable"),
		DBMaxOpen:    getIntEnv("DB_MAX_OPEN", 20),
		DBMaxIdle:    getIntEnv("DB_MAX_IDLE", 10),
		DBMaxLifeMin: getIntEnv("DB_MAX_LIFE_MIN", 30),
		DBConnTO:     time.Duration(getIntEnv("DB_CONN_TIMEOUT_SEC", 5)) * time.Second,
		DBRetryCount: getIntEnv("DB_RETRY_COUNT", 3),
	}
}

func getEnv(key, fallback string) string {
	if value, ok := os.LookupEnv(key); ok {
		return value
	}
	return fallback
}

func getIntEnv(key string, fallback int) int {
	v, ok := os.LookupEnv(key)
	if !ok {
		return fallback
	}
	parsed, err := strconv.Atoi(v)
	if err != nil {
		return fallback
	}
	return parsed
}
