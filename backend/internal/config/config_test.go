package config

import (
	"strings"
	"testing"
)

func TestDSNContainsExpectedParts(t *testing.T) {
	cfg := Config{
		DBHost:              "db.example",
		DBPort:              "5432",
		DBUser:              "user",
		DBPassword:          "pass",
		DBName:              "name",
		DBSSLMode:           "require",
		DBConnectTimeoutSec: 7,
	}

	dsn := cfg.DSN()
	expected := []string{"host=db.example", "port=5432", "user=user", "password=pass", "dbname=name", "sslmode=require", "connect_timeout=7"}
	for _, item := range expected {
		if !strings.Contains(dsn, item) {
			t.Fatalf("dsn %q does not include %q", dsn, item)
		}
	}
}
