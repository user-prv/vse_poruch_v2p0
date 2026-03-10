package modules

import "testing"

func TestNormalizeStatus(t *testing.T) {
	cases := map[string]string{
		"":                     "draft",
		" ACTIVE ":             "active",
		"pending_verification": "pending_verification",
		"bad":                  "",
	}
	for in, want := range cases {
		if got := normalizeStatus(in); got != want {
			t.Fatalf("normalizeStatus(%q)=%q want %q", in, got, want)
		}
	}
}
