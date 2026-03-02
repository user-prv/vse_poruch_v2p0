package config

import (
	"bufio"
	"os"
	"strconv"
	"strings"
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
	loadDotEnv()

	return Config{
		Port:         getEnv("PORT", "8080"),
		LogFormat:    getEnv("LOG_FORMAT", "json"),
		LogLevel:     getEnv("LOG_LEVEL", "info"),
		DBHost:       getEnv("DB_HOST", "127.0.0.1"),
		DBPort:       getEnv("DB_PORT", "5432"),
		DBUser:       getEnv("DB_USER", "postgres"),
		DBPassword:   getEnv("DB_PASSWORD", "postgres"),
		DBName:       getEnv("DB_NAME", "vseporuch"),
		DBSSLMode:    getEnv("DB_SSLMODE", "require"),
		DBMaxOpen:    getIntEnv("DB_MAX_OPEN", 20),
		DBMaxIdle:    getIntEnv("DB_MAX_IDLE", 10),
		DBMaxLifeMin: getIntEnv("DB_MAX_LIFE_MIN", 30),
		DBConnTO:     time.Duration(getIntEnv("DB_CONN_TIMEOUT_SEC", 5)) * time.Second,
		DBRetryCount: getIntEnv("DB_RETRY_COUNT", 3),
	}
}

func loadDotEnv() {
	for _, path := range []string{".env", "backend/.env"} {
		if loadDotEnvFile(path) {
			return
		}
	}
}

func loadDotEnvFile(path string) bool {
	file, err := os.Open(path)
	if err != nil {
		return false
	}
	defer file.Close()

	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())
		if line == "" || strings.HasPrefix(line, "#") {
			continue
		}

		parts := strings.SplitN(line, "=", 2)
		if len(parts) != 2 {
			continue
		}

		key := strings.TrimSpace(parts[0])
		if key == "" {
			continue
		}

		if _, exists := os.LookupEnv(key); exists {
			continue
		}

		value := strings.TrimSpace(parts[1])
		value = strings.Trim(value, "\"")
		value = strings.Trim(value, "'")
		_ = os.Setenv(key, value)
	}

	return true
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
