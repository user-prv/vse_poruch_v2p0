import { apiClient } from './client';

export async function fetchListings(params = {}) {
  const { data } = await apiClient.get('/listings', { params });
  return data;
}

export async function fetchCategories() {
  const { data } = await apiClient.get('/categories');
  return data;
}

export async function moderateListing(listingId, status, reason = "") {
  const { data } = await apiClient.post(`/admin/listings/${listingId}/moderate`, { status, reason });
  return data;
}

export async function verifyUser(userId) {
  const { data } = await apiClient.post(`/admin/users/${userId}/verify`);
  return data;
}

export async function blockUser(userId) {
  const { data } = await apiClient.post(`/admin/users/${userId}/block`);
  return data;
}

export async function setCategoryIcon(categoryId, icon_path) {
  const { data } = await apiClient.post(`/admin/categories/${categoryId}/icon`, { icon_path });
  return data;
}

export async function fetchUserProfile(userId) {
  const { data } = await apiClient.get(`/profile/${userId}`);
  return data;
}

export async function createCategory(payload) {
  const { data } = await apiClient.post('/categories', payload);
  return data;
}

export async function updateCategory(categoryId, payload) {
  const { data } = await apiClient.put(`/categories/${categoryId}`, payload);
  return data;
}

export async function deleteCategory(categoryId) {
  const { data } = await apiClient.delete(`/categories/${categoryId}`);
  return data;
}
