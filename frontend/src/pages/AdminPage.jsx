import { useEffect, useMemo, useState } from 'react';
import { useAppStore } from '../shared/store';
import {
  blockUser,
  createCategory,
  deleteCategory,
  fetchCategories,
  fetchListings,
  fetchUserProfile,
  moderateListing,
  setCategoryIcon,
  updateCategory,
  verifyUser,
} from '../api/admin';

const LISTING_STATUSES = ['draft', 'pending_verification', 'active', 'rejected', 'archived'];

const ADMIN_TABS = {
  users: 'users',
  listings: 'listings',
  verification: 'verification',
  categories: 'categories',
};

function normalizeCollection(payload) {
  if (Array.isArray(payload)) {
    return payload;
  }

  if (Array.isArray(payload?.items)) {
    return payload.items;
  }

  if (Array.isArray(payload?.data)) {
    return payload.data;
  }

  if (Array.isArray(payload?.data?.items)) {
    return payload.data.items;
  }

  return [];
}

function toRole(value) {
  return String(value || '').trim().toLowerCase();
}

export function AdminPage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState(ADMIN_TABS.users);

  const [listings, setListings] = useState([]);
  const [categories, setCategories] = useState([]);
  const [users, setUsers] = useState([]);

  const [listingStatusFilter, setListingStatusFilter] = useState('all');
  const [listingSearch, setListingSearch] = useState('');
  const [userSearch, setUserSearch] = useState('');
  const [categorySearch, setCategorySearch] = useState('');

  const [selectedListingIds, setSelectedListingIds] = useState([]);
  const [selectedUserIds, setSelectedUserIds] = useState([]);
  const [selectedCategoryIds, setSelectedCategoryIds] = useState([]);

  const [bulkListingStatus, setBulkListingStatus] = useState('pending_verification');
  const [categoryIconPath, setCategoryIconPath] = useState('/uploads/category_icons/default.svg');
  const [newCategoryName, setNewCategoryName] = useState('');
  const [editingCategoryId, setEditingCategoryId] = useState(null);
  const [editingCategoryName, setEditingCategoryName] = useState('');

  const [actionLog, setActionLog] = useState([]);

  const sessionUser = useAppStore((state) => state.user);
  const currentRole = sessionUser?.role || sessionUser?.Role || '';
  const canManageAdmin = toRole(currentRole) === 'admin';

  const addLog = (message, status = 'ok') => {
    setActionLog((prev) => [{ id: Date.now() + Math.random(), at: new Date().toISOString(), message, status }, ...prev].slice(0, 30));
  };

  const loadData = async () => {
    setLoading(true);
    setError('');

    try {
      const [listingResponse, categoryResponse] = await Promise.all([
        fetchListings({ limit: 50, status: listingStatusFilter === 'all' ? undefined : listingStatusFilter, q: listingSearch || undefined }),
        fetchCategories(),
      ]);

      const listingItems = normalizeCollection(listingResponse);
      setListings(listingItems);
      setCategories(normalizeCollection(categoryResponse));

      const authorIds = [...new Set(listingItems.map((item) => item.author_id).filter(Boolean))].slice(0, 30);
      const usersLoaded = await Promise.allSettled(authorIds.map((id) => fetchUserProfile(id)));
      const profiles = usersLoaded
        .filter((item) => item.status === 'fulfilled')
        .map((item) => item.value)
        .filter(Boolean);
      setUsers(profiles);
    } catch (requestError) {
      setError(requestError.response?.data?.error || requestError.message || 'Не вдалося завантажити адмін-дані.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [listingStatusFilter]);

  const visibleListings = useMemo(() => {
    const q = listingSearch.trim().toLowerCase();
    return listings.filter((item) => {
      if (!q) return true;
      return [item.title, item.body, String(item.id), String(item.author_id)].some((part) => String(part || '').toLowerCase().includes(q));
    });
  }, [listings, listingSearch]);

  const visibleUsers = useMemo(() => {
    const q = userSearch.trim().toLowerCase();
    return users.filter((item) => {
      if (!q) return true;
      return [item.email, String(item.id), item.status, item.role].some((part) => String(part || '').toLowerCase().includes(q));
    });
  }, [users, userSearch]);

  const visibleCategories = useMemo(() => {
    const q = categorySearch.trim().toLowerCase();
    return categories.filter((item) => {
      if (!q) return true;
      return [item.name, String(item.id), item.icon_path].some((part) => String(part || '').toLowerCase().includes(q));
    });
  }, [categories, categorySearch]);

  const toggleSelection = (collection, setCollection, id) => {
    setCollection(collection.includes(id) ? collection.filter((value) => value !== id) : [...collection, id]);
  };

  const moderateSelectedListings = async () => {
    if (!canManageAdmin || selectedListingIds.length === 0) return;
    const results = await Promise.allSettled(selectedListingIds.map((id) => moderateListing(id, bulkListingStatus)));
    const success = results.filter((item) => item.status === 'fulfilled').length;
    addLog(`Оголошення: ${success}/${selectedListingIds.length} змінено на статус ${bulkListingStatus}.`, success === selectedListingIds.length ? 'ok' : 'warn');
    setSelectedListingIds([]);
    await loadData();
  };

  const handleListingStatus = async (listingId, status) => {
    if (!canManageAdmin) return;
    const reason = status === 'rejected' ? 'Потрібне доопрацювання' : '';
    await moderateListing(listingId, status, reason);
    addLog(`Оголошення #${listingId}: статус ${status}.`);
    await loadData();
  };

  const handleBlockUser = async (userId) => {
    if (!canManageAdmin) return;
    await blockUser(userId);
    addLog(`Користувач #${userId} заблокований.`);
    await loadData();
  };

  const handleVerifyUser = async (userId) => {
    if (!canManageAdmin) return;
    await verifyUser(userId);
    addLog(`Користувач #${userId} верифікований.`);
    await loadData();
  };

  const verifySelectedUsers = async () => {
    if (!canManageAdmin || selectedUserIds.length === 0) return;
    const results = await Promise.allSettled(selectedUserIds.map((id) => verifyUser(id)));
    const success = results.filter((item) => item.status === 'fulfilled').length;
    addLog(`Користувачі: ${success}/${selectedUserIds.length} верифіковано.`, success === selectedUserIds.length ? 'ok' : 'warn');
    setSelectedUserIds([]);
  };

  const blockSelectedUsers = async () => {
    if (!canManageAdmin || selectedUserIds.length === 0) return;
    const results = await Promise.allSettled(selectedUserIds.map((id) => blockUser(id)));
    const success = results.filter((item) => item.status === 'fulfilled').length;
    addLog(`Користувачі: ${success}/${selectedUserIds.length} заблоковано.`, success === selectedUserIds.length ? 'ok' : 'warn');
    setSelectedUserIds([]);
  };

  const handleCreateCategory = async (event) => {
    event.preventDefault();
    if (!canManageAdmin) return;
    const name = newCategoryName.trim();
    if (!name) return;
    await createCategory({ name });
    setNewCategoryName('');
    addLog(`Створено категорію "${name}".`);
    await loadData();
  };

  const handleStartEditCategory = (item) => {
    setEditingCategoryId(item.id);
    setEditingCategoryName(item.name || '');
  };

  const handleSaveCategory = async (categoryId) => {
    if (!canManageAdmin) return;
    const currentCategory = categories.find((category) => category.id === categoryId);
    const nextName = editingCategoryName.trim();
    if (!currentCategory || !nextName) return;
    await updateCategory(categoryId, {
      name: nextName,
      parent_id: currentCategory.parent_id || null,
      icon_path: currentCategory.icon_path || '',
    });
    addLog(`Категорію #${categoryId} оновлено.`);
    setEditingCategoryId(null);
    setEditingCategoryName('');
    await loadData();
  };

  const handleDeleteCategory = async (categoryId) => {
    if (!canManageAdmin) return;
    await deleteCategory(categoryId);
    addLog(`Категорію #${categoryId} видалено.`, 'warn');
    await loadData();
  };

  const updateSelectedCategoryIcons = async () => {
    if (!canManageAdmin || selectedCategoryIds.length === 0) return;
    const results = await Promise.allSettled(selectedCategoryIds.map((id) => setCategoryIcon(id, categoryIconPath.trim())));
    const success = results.filter((item) => item.status === 'fulfilled').length;
    addLog(`Категорії: ${success}/${selectedCategoryIds.length} іконок оновлено.`, success === selectedCategoryIds.length ? 'ok' : 'warn');
    setSelectedCategoryIds([]);
    await loadData();
  };

  return (
    <main className="admin-page-shell">
      <section className="admin-card">
        <h1 className="admin-title">Адмінка</h1>

        <nav className="admin-tabs" aria-label="Адмін вкладки">
          <button type="button" className={`admin-tab-btn ${activeTab === ADMIN_TABS.users ? 'active' : ''}`} onClick={() => setActiveTab(ADMIN_TABS.users)}>
            Користувачі
          </button>
          <button type="button" className={`admin-tab-btn ${activeTab === ADMIN_TABS.listings ? 'active' : ''}`} onClick={() => setActiveTab(ADMIN_TABS.listings)}>
            Оголошення
          </button>
          <button type="button" className={`admin-tab-btn ${activeTab === ADMIN_TABS.verification ? 'active' : ''}`} onClick={() => setActiveTab(ADMIN_TABS.verification)}>
            Верифікація
          </button>
          <button type="button" className={`admin-tab-btn ${activeTab === ADMIN_TABS.categories ? 'active' : ''}`} onClick={() => setActiveTab(ADMIN_TABS.categories)}>
            Категорії
          </button>
          <button type="button" className="admin-tab-btn admin-tab-map">На карту</button>
        </nav>

        <div className="admin-actions-row">
          <button onClick={loadData} disabled={loading}>{loading ? 'Оновлюємо...' : 'Оновити дані'}</button>
          {!canManageAdmin ? <p role="alert">Недостатньо прав: операції керування доступні тільки для role=admin.</p> : null}
          {error ? <p role="alert">{error}</p> : null}
        </div>

        {activeTab === ADMIN_TABS.listings ? (
          <section aria-labelledby="admin-listings">
            <h2 id="admin-listings">Модерація оголошень</h2>
            <label>
              Фільтр статусу:
              <select value={listingStatusFilter} onChange={(event) => setListingStatusFilter(event.target.value)}>
                <option value="all">all</option>
                {LISTING_STATUSES.map((status) => (
                  <option key={status} value={status}>{status}</option>
                ))}
              </select>
            </label>
            <label>
              Пошук:
              <input value={listingSearch} onChange={(event) => setListingSearch(event.target.value)} placeholder="id / title / author" />
            </label>
            <div>
              <select value={bulkListingStatus} onChange={(event) => setBulkListingStatus(event.target.value)}>
                {LISTING_STATUSES.map((status) => (
                  <option key={status} value={status}>{status}</option>
                ))}
              </select>
              <button onClick={moderateSelectedListings} disabled={!canManageAdmin || selectedListingIds.length === 0}>Bulk moderate</button>
            </div>
            <table>
              <thead>
                <tr>
                  <th />
                  <th>ID</th>
                  <th>Title</th>
                  <th>Author</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {visibleListings.map((item) => (
                  <tr key={item.id}>
                    <td>
                      <input
                        type="checkbox"
                        checked={selectedListingIds.includes(item.id)}
                        onChange={() => toggleSelection(selectedListingIds, setSelectedListingIds, item.id)}
                      />
                    </td>
                    <td>{item.id}</td>
                    <td>{item.title}</td>
                    <td>{item.author_id}</td>
                    <td>{item.status}</td>
                    <td>
                      <button onClick={() => handleListingStatus(item.id, 'active')} disabled={!canManageAdmin}>Підтвердити</button>
                      <button onClick={() => handleListingStatus(item.id, 'rejected')} disabled={!canManageAdmin}>Відхилити</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </section>
        ) : null}

        {activeTab === ADMIN_TABS.users || activeTab === ADMIN_TABS.verification ? (
          <section aria-labelledby="admin-users">
            <h2 id="admin-users">Верифікація / блокування користувачів</h2>
            <label>
              Пошук:
              <input value={userSearch} onChange={(event) => setUserSearch(event.target.value)} placeholder="id / email / role" />
            </label>
            <div>
              <button onClick={verifySelectedUsers} disabled={!canManageAdmin || selectedUserIds.length === 0}>Bulk verify</button>
              <button onClick={blockSelectedUsers} disabled={!canManageAdmin || selectedUserIds.length === 0}>Bulk block</button>
            </div>
            <table>
              <thead>
                <tr>
                  <th />
                  <th>ID</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {visibleUsers.map((item) => (
                  <tr key={item.id}>
                    <td>
                      <input type="checkbox" checked={selectedUserIds.includes(item.id)} onChange={() => toggleSelection(selectedUserIds, setSelectedUserIds, item.id)} />
                    </td>
                    <td>{item.id}</td>
                    <td>{item.email}</td>
                    <td>{item.role || 'user'}</td>
                    <td>{item.is_blocked ? 'blocked' : 'active'}</td>
                    <td>
                      <button onClick={() => handleVerifyUser(item.id)} disabled={!canManageAdmin}>Verify</button>
                      <button onClick={() => handleBlockUser(item.id)} disabled={!canManageAdmin}>Block</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </section>
        ) : null}

        {activeTab === ADMIN_TABS.categories ? (
          <section aria-labelledby="admin-categories">
            <h2 id="admin-categories">Категорії</h2>
            <form onSubmit={handleCreateCategory}>
              <input value={newCategoryName} onChange={(event) => setNewCategoryName(event.target.value)} placeholder="Нова категорія" />
              <button type="submit" disabled={!canManageAdmin}>Додати категорію</button>
            </form>
            <label>
              Пошук:
              <input value={categorySearch} onChange={(event) => setCategorySearch(event.target.value)} placeholder="id / name" />
            </label>
            <label>
              icon_path:
              <input value={categoryIconPath} onChange={(event) => setCategoryIconPath(event.target.value)} />
            </label>
            <button onClick={updateSelectedCategoryIcons} disabled={!canManageAdmin || selectedCategoryIds.length === 0}>Bulk update icons</button>
            <table>
              <thead>
                <tr>
                  <th />
                  <th>ID</th>
                  <th>Name</th>
                  <th>Parent</th>
                  <th>Icon</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {visibleCategories.map((item) => (
                  <tr key={item.id}>
                    <td>
                      <input
                        type="checkbox"
                        checked={selectedCategoryIds.includes(item.id)}
                        onChange={() => toggleSelection(selectedCategoryIds, setSelectedCategoryIds, item.id)}
                      />
                    </td>
                    <td>{item.id}</td>
                    <td>
                      {editingCategoryId === item.id ? (
                        <input value={editingCategoryName} onChange={(event) => setEditingCategoryName(event.target.value)} />
                      ) : (
                        item.name
                      )}
                    </td>
                    <td>{item.parent_id || '-'}</td>
                    <td>{item.icon_path || '-'}</td>
                    <td>
                      {editingCategoryId === item.id ? (
                        <>
                          <button type="button" onClick={() => handleSaveCategory(item.id)} disabled={!canManageAdmin}>Зберегти</button>
                          <button type="button" onClick={() => setEditingCategoryId(null)}>Скасувати</button>
                        </>
                      ) : (
                        <button type="button" onClick={() => handleStartEditCategory(item)} disabled={!canManageAdmin}>Редагувати</button>
                      )}
                      <button type="button" onClick={() => handleDeleteCategory(item.id)} disabled={!canManageAdmin}>Видалити</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </section>
        ) : null}

        <section aria-labelledby="admin-audit-log">
          <h2 id="admin-audit-log">Журнал дій</h2>
          <ul>
            {actionLog.map((entry) => (
              <li key={entry.id}>
                [{entry.status}] {entry.at}: {entry.message}
              </li>
            ))}
          </ul>
        </section>
      </section>
    </main>
  );
}
