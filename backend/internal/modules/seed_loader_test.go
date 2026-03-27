package modules

import (
	"os"
	"path/filepath"
	"testing"
)

func TestFindSeedSQLPath_FindsBackendSeedSQL(t *testing.T) {
	wd, err := os.Getwd()
	if err != nil {
		t.Fatalf("getwd: %v", err)
	}
	t.Cleanup(func() {
		_ = os.Chdir(wd)
	})

	tmp := t.TempDir()
	if err := os.MkdirAll(filepath.Join(tmp, "backend"), 0o755); err != nil {
		t.Fatalf("mkdir backend dir: %v", err)
	}
	if err := os.WriteFile(filepath.Join(tmp, "backend", "seed.sql"), []byte("SELECT 1;"), 0o644); err != nil {
		t.Fatalf("write seed: %v", err)
	}
	if err := os.Chdir(tmp); err != nil {
		t.Fatalf("chdir: %v", err)
	}

	got, err := findSeedSQLPath()
	if err != nil {
		t.Fatalf("findSeedSQLPath returned error: %v", err)
	}

	want := filepath.Join("backend", "seed.sql")
	if got != want {
		t.Fatalf("findSeedSQLPath() = %q, want %q", got, want)
	}
}

func TestFindSeedSQLPath_PrefersRootSeedSQL(t *testing.T) {
	wd, err := os.Getwd()
	if err != nil {
		t.Fatalf("getwd: %v", err)
	}
	t.Cleanup(func() {
		_ = os.Chdir(wd)
	})

	tmp := t.TempDir()
	if err := os.MkdirAll(filepath.Join(tmp, "backend"), 0o755); err != nil {
		t.Fatalf("mkdir backend dir: %v", err)
	}
	if err := os.WriteFile(filepath.Join(tmp, "seed.sql"), []byte("SELECT 1;"), 0o644); err != nil {
		t.Fatalf("write root seed: %v", err)
	}
	if err := os.WriteFile(filepath.Join(tmp, "backend", "seed.sql"), []byte("SELECT 2;"), 0o644); err != nil {
		t.Fatalf("write backend seed: %v", err)
	}
	if err := os.Chdir(tmp); err != nil {
		t.Fatalf("chdir: %v", err)
	}

	got, err := findSeedSQLPath()
	if err != nil {
		t.Fatalf("findSeedSQLPath returned error: %v", err)
	}

	if got != "seed.sql" {
		t.Fatalf("findSeedSQLPath() = %q, want %q", got, "seed.sql")
	}
}
