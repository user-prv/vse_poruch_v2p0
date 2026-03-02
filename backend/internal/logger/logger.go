package logger

import (
	"strings"

	"github.com/sirupsen/logrus"
	"github.com/vseporuch/v2/backend/internal/config"
)

func New(cfg config.Config) *logrus.Logger {
	log := logrus.New()
	if strings.EqualFold(cfg.LogFormat, "plain") {
		log.SetFormatter(&logrus.TextFormatter{FullTimestamp: true})
	} else {
		log.SetFormatter(&logrus.JSONFormatter{})
	}

	level, err := logrus.ParseLevel(strings.ToLower(cfg.LogLevel))
	if err != nil {
		level = logrus.InfoLevel
	}
	log.SetLevel(level)
	return log
}
