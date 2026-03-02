import { useEffect, useMemo, useState } from 'react';
import {
  blockUser,
  fetchCategories,
  fetchListings,
  fetchUserProfile,
  moderateListing,
  setCategoryIcon,
  verifyUser,
} from '../api/admin';

const LISTING_STATUSES = ['pending', 'active', 'blocked', 'deleted'];

function toRole(value) {
  return String(value || '').trim().toLowerCase();
}

export function AdminPage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

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

  const [bulkListingStatus, setBulkListingStatus] = useState('blocked');
  const [categoryIconPath, setCategoryIconPath] = useState('/uploads/category_icons/default.svg');

  const [actionLog, setActionLog] = useState([]);

  const [currentRole, setCurrentRole] = useState('');
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

      const listingItems = listingResponse?.items || [];
      setListings(listingItems);
      setCategories(Array.isArray(categoryResponse) ? categoryResponse : []);

      const authorIds = [...new Set(listingItems.map((item) => item.author_id).filter(Boolean))].slice(0, 30);
      const usersLoaded = await Promise.allSettled(authorIds.map((id) => fetchUserProfile(id)));
      const profiles = usersLoaded
        .filter((item) => item.status === 'fulfilled')
        .map((item) => item.value)
        .filter(Boolean);
      setUsers(profiles);
      const adminUser = profiles.find((profile) => toRole(profile?.role) === 'admin');
      setCurrentRole(adminUser?.role || currentRole);
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

  const updateSelectedCategoryIcons = async () => {
    if (!canManageAdmin || selectedCategoryIds.length === 0) return;
    const results = await Promise.allSettled(selectedCategoryIds.map((id) => setCategoryIcon(id, categoryIconPath.trim())));
    const success = results.filter((item) => item.status === 'fulfilled').length;
    addLog(`Категорії: ${success}/${selectedCategoryIds.length} іконок оновлено.`, success === selectedCategoryIds.length ? 'ok' : 'warn');
    setSelectedCategoryIds([]);
    await loadData();
  };

  return (
    <main>
      <h1>Admin panel</h1>
      <p>Модулі parity: оголошення, користувачі, категорії + журнал дій.</p>

      <div>
        <button onClick={loadData} disabled={loading}>{loading ? 'Оновлюємо...' : 'Оновити дані'}</button>
        {!canManageAdmin ? <p role="alert">Недостатньо прав: операції керування доступні тільки для role=admin.</p> : null}
        {error ? <p role="alert">{error}</p> : null}
      </div>

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
              </tr>
            ))}
          </tbody>
        </table>
      </section>

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
                <td>{item.status || 'active'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>

      <section aria-labelledby="admin-categories">
        <h2 id="admin-categories">Категорії</h2>
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
                <td>{item.name}</td>
                <td>{item.parent_id || '-'}</td>
                <td>{item.icon_path || '-'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>

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
    </main>
  );
}
