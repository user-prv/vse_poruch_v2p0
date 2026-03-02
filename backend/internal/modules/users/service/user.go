package service

import (
	"context"

	"vseporuch/backend/internal/modules/users/repository"
)

type UserService struct {
	repo *repository.UserRepository
}

func NewUserService(repo *repository.UserRepository) *UserService {
	return &UserService{repo: repo}
}

func (s *UserService) List(ctx context.Context) ([]repository.User, error) {
	return s.repo.List(ctx)
}
