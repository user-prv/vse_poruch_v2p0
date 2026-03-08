import { apiClient } from './client';

export async function register(payload) {
  const { data } = await apiClient.post('/auth/register', payload);
  return data?.data ?? data;
}

export async function login(payload) {
  const { data } = await apiClient.post('/auth/login', payload);
  return data?.data ?? data;
}

export async function logout() {
  const { data } = await apiClient.post('/auth/logout');
  return data?.data ?? data;
}

export async function resetPassword(payload) {
  const { data } = await apiClient.post('/auth/reset-password', payload);
  return data?.data ?? data;
}
