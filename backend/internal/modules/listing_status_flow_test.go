package modules

import "testing"

func TestNextStatusOnCreate(t *testing.T) {
	if got := nextStatusOnCreate("draft"); got != "draft" {
		t.Fatalf("expected draft, got %s", got)
	}
	if got := nextStatusOnCreate("active"); got != "pending_verification" {
		t.Fatalf("expected pending_verification, got %s", got)
	}
}

func TestNextStatusOnUpdate(t *testing.T) {
	cases := []struct {
		name     string
		prev     string
		req      string
		expected string
	}{
		{name: "draft to draft", prev: "draft", req: "draft", expected: "draft"},
		{name: "draft to active", prev: "draft", req: "active", expected: "pending_verification"},
		{name: "active to draft", prev: "active", req: "draft", expected: "pending_verification"},
	}

	for _, tc := range cases {
		t.Run(tc.name, func(t *testing.T) {
			if got := nextStatusOnUpdate(tc.prev, tc.req); got != tc.expected {
				t.Fatalf("expected %s, got %s", tc.expected, got)
			}
		})
	}
}
