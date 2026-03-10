package modules

import (
	"os"
	"strings"
	"testing"
)

func TestSeedSQL_HasExpectedDataShape(t *testing.T) {
	contentBytes, err := os.ReadFile("../../seed.sql")
	if err != nil {
		t.Fatalf("read seed.sql: %v", err)
	}
	content := string(contentBytes)

	if !strings.Contains(content, "INSERT INTO categories") {
		t.Fatal("seed must contain category insert")
	}
	if !strings.Contains(content, "generate_series(1, 30)") {
		t.Fatal("seed must generate 30 listings")
	}
	if !strings.Contains(content, "1 + (gs % 10)") {
		t.Fatal("seed listings must reference valid category range")
	}

	requiredStatuses := []string{"draft", "pending_verification", "active", "rejected", "archived"}
	for _, status := range requiredStatuses {
		if !strings.Contains(content, status) {
			t.Fatalf("seed missing status %q", status)
		}
	}
}
