package logger

import (
	"strings"

	"github.com/sirupsen/logrus"
)

func New(level string) *logrus.Logger {
	log := logrus.New()
	log.SetFormatter(&logrus.JSONFormatter{})

	parsed, err := logrus.ParseLevel(strings.ToLower(level))
	if err != nil {
		parsed = logrus.InfoLevel
	}
	log.SetLevel(parsed)

	return log
}
