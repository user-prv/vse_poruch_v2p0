package modules

import "testing"

func TestValidateListingPayload(t *testing.T) {
	validLat := 50.45
	validLng := 30.52
	ok := listingRequest{Title: "Valid title", CategoryID: 1, Currency: "UAH", Latitude: &validLat, Longitude: &validLng}
	if got := validateListingPayload(ok); got != "" {
		t.Fatalf("expected valid payload, got %q", got)
	}

	tooShort := listingRequest{Title: "ab", CategoryID: 1}
	if got := validateListingPayload(tooShort); got == "" {
		t.Fatal("expected title validation error")
	}

	badCurrency := listingRequest{Title: "Valid", CategoryID: 1, Currency: "USDT"}
	if got := validateListingPayload(badCurrency); got == "" {
		t.Fatal("expected currency validation error")
	}

	badLat := 99.1
	withBadLat := listingRequest{Title: "Valid", CategoryID: 1, Latitude: &badLat}
	if got := validateListingPayload(withBadLat); got == "" {
		t.Fatal("expected lat validation error")
	}
}
