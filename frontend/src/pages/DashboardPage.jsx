import { useEffect, useMemo, useState } from 'react';
import { apiClient } from '../api/client';
import { AsyncState } from '../shared/AsyncState';
import { useAppStore } from '../shared/store';

const TABS = {
  profile: 'profile',
  listings: 'listings',
  editor: 'editor',
};

const STATUS_LABELS = {
  active: 'active',
  blocked: 'blocked',
  deleted: 'deleted',
  pending: 'pending',
};

function statusBadgeStyle(status) {
  if (status === 'active') return { color: '#166534' };
  if (status === 'blocked') return { color: '#b91c1c' };
  if (status === 'deleted') return { color: '#6b7280' };
  return { color: '#92400e' };
}

function DashboardProfileTab({ user, refreshKey }) {
  const [profile, setProfile] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    async function loadProfile() {
      const userId = user?.id || user?.ID;
      if (!userId) {
        setLoading(false);
        return;
      }
      setLoading(true);
      setError('');
      try {
        const { data } = await apiClient.get(`/profile/${userId}`);
        if (active) {
          setProfile(data);
        }
      } catch (requestError) {
        if (active) {
          setError(requestError.response?.data?.error || requestError.message || 'Не вдалося завантажити профіль');
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    }
    loadProfile();
    return () => {
      active = false;
    };
  }, [user?.id || user?.ID, refreshKey]);

  return (
    <AsyncState loading={loading} error={error}>
      <h2>Профіль</h2>
      <p>Email: {profile?.email || user?.email || user?.Email}</p>
      <p>Роль: {profile?.role || user?.role || user?.Role || 'user'}</p>
      <p>Редагування профілю буде додано окремим API етапом.</p>
    </AsyncState>
  );
}

function DashboardListingsTab({ userId, onEdit }) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [items, setItems] = useState([]);

  const loadListings = async () => {
    if (!userId) {
      setItems([]);
      setLoading(false);
      return;
    }
    setLoading(true);
    setError('');
    try {
      const { data } = await apiClient.get('/listings', {
        params: { author_id: userId, limit: 50 },
      });
      setItems(data?.items || []);
    } catch (requestError) {
      setError(requestError.response?.data?.error || requestError.message || 'Не вдалося завантажити оголошення');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadListings();
  }, [userId]);

  const handleDelete = async (listingId) => {
    try {
      await apiClient.delete(`/listings/${listingId}`);
      await loadListings();
    } catch (requestError) {
      setError(requestError.response?.data?.error || requestError.message || 'Не вдалося видалити оголошення');
    }
  };

  return (
    <AsyncState loading={loading} error={error}>
      <h2>Мої оголошення</h2>
      {items.length === 0 ? <p>Ще немає оголошень.</p> : null}
      {items.map((item) => (
        <article key={item.id} style={{ border: '1px solid #ddd', borderRadius: 8, padding: 10, marginBottom: 10 }}>
          <h3>{item.title}</h3>
          <p style={statusBadgeStyle(item.status)}>
            Статус: <strong>{STATUS_LABELS[item.status] || item.status}</strong>
          </p>
          <p>{item.body || 'Опис відсутній'}</p>
          <button type="button" onClick={() => onEdit(item.id)}>
            Редагувати
          </button>{' '}
          <button type="button" onClick={() => handleDelete(item.id)}>
            Видалити
          </button>
        </article>
      ))}
    </AsyncState>
  );
}

function DashboardListingFormTab({ userId, editId, onSaved }) {
  const [form, setForm] = useState({ title: '', body: '', category_id: '', status: 'pending' });
  const [photos, setPhotos] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  useEffect(() => {
    let active = true;

    async function loadContext() {
      try {
        const categoriesResponse = await apiClient.get('/categories');
        if (active) {
          setCategories(categoriesResponse.data || []);
        }
        if (editId) {
          const { data } = await apiClient.get(`/listings/${editId}`);
          if (active) {
            setForm({
              title: data.title || '',
              body: data.body || '',
              category_id: String(data.category_id || ''),
              status: data.status || 'pending',
            });
            setPhotos((data.photo_paths || []).map((path, idx) => ({ id: `${path}-${idx}`, path })));
          }
        } else if (active) {
          setForm({ title: '', body: '', category_id: '', status: 'pending' });
          setPhotos([]);
        }
      } catch (requestError) {
        if (active) {
          setError(requestError.response?.data?.error || requestError.message || 'Не вдалося завантажити дані форми');
        }
      }
    }

    loadContext();

    return () => {
      active = false;
    };
  }, [editId]);

  const allowedStatuses = useMemo(() => {
    if (form.status === 'blocked') {
      return ['blocked', 'pending', 'deleted'];
    }
    if (form.status === 'deleted') {
      return ['deleted', 'pending'];
    }
    return ['pending', 'active', 'deleted'];
  }, [form.status]);

  const uploadPhoto = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    setError('');

    const payload = new FormData();
    payload.append('photo', file);

    try {
      const { data } = await apiClient.post('/uploads/photo', payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      setPhotos((prev) => [...prev, { id: `${data.path}-${Date.now()}`, path: data.path }]);
    } catch (requestError) {
      setError(requestError.response?.data?.error || requestError.message || 'Не вдалося завантажити фото');
    } finally {
      event.target.value = '';
    }
  };

  const movePhoto = (index, direction) => {
    setPhotos((prev) => {
      const next = [...prev];
      const targetIndex = index + direction;
      if (targetIndex < 0 || targetIndex >= next.length) {
        return prev;
      }
      [next[index], next[targetIndex]] = [next[targetIndex], next[index]];
      return next;
    });
  };

  const removePhoto = (id) => {
    setPhotos((prev) => prev.filter((photo) => photo.id !== id));
  };

  const saveListing = async (event) => {
    event.preventDefault();
    setLoading(true);
    setError('');
    setSuccess('');

    if (!form.title.trim()) {
      setLoading(false);
      setError('Вкажіть назву оголошення');
      return;
    }

    if (!allowedStatuses.includes(form.status)) {
      setLoading(false);
      setError('Некоректний перехід статусу для оголошення');
      return;
    }

    const payload = {
      title: form.title,
      body: form.body,
      author_id: userId,
      category_id: Number(form.category_id),
      status: form.status,
      photo_paths: photos.map((photo) => photo.path),
    };

    try {
      if (editId) {
        await apiClient.put(`/listings/${editId}`, payload);
      } else {
        await apiClient.post('/listings', payload);
      }
      setSuccess(editId ? 'Оголошення оновлено' : 'Оголошення створено');
      if (!editId) {
        setForm({ title: '', body: '', category_id: '', status: 'pending' });
        setPhotos([]);
      }
      onSaved();
    } catch (requestError) {
      setError(requestError.response?.data?.error || requestError.message || 'Помилка збереження оголошення');
    } finally {
      setLoading(false);
    }
  };

  return (
    <section>
      <h2>{editId ? `Редагування #${editId}` : 'Створення оголошення'}</h2>
      <p>Бізнес-правила статусів: blocked → тільки pending/deleted, deleted → тільки pending.</p>
      <form onSubmit={saveListing}>
        <label>
          Назва
          <input value={form.title} onChange={(event) => setForm((prev) => ({ ...prev, title: event.target.value }))} />
        </label>
        <label>
          Опис
          <textarea value={form.body} onChange={(event) => setForm((prev) => ({ ...prev, body: event.target.value }))} />
        </label>
        <label>
          Категорія
          <select
            value={form.category_id}
            onChange={(event) => setForm((prev) => ({ ...prev, category_id: event.target.value }))}
          >
            <option value="">— Оберіть категорію —</option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
        </label>
        <label>
          Статус
          <select value={form.status} onChange={(event) => setForm((prev) => ({ ...prev, status: event.target.value }))}>
            {allowedStatuses.map((status) => (
              <option key={status} value={status}>
                {status}
              </option>
            ))}
          </select>
        </label>

        <section>
          <h3>Фото</h3>
          <input type="file" accept="image/*" onChange={uploadPhoto} />
          <ul>
            {photos.map((photo, index) => (
              <li key={photo.id}>
                {photo.path}
                <button type="button" onClick={() => movePhoto(index, -1)}>
                  ↑
                </button>
                <button type="button" onClick={() => movePhoto(index, 1)}>
                  ↓
                </button>
                <button type="button" onClick={() => removePhoto(photo.id)}>
                  Видалити
                </button>
              </li>
            ))}
          </ul>
        </section>

        {error ? <p role="alert">{error}</p> : null}
        {success ? <p>{success}</p> : null}
        <button type="submit" disabled={loading}>
          {loading ? 'Збереження...' : 'Зберегти'}
        </button>
      </form>
    </section>
  );
}

export function DashboardPage() {
  const user = useAppStore((state) => state.user);
  const [tab, setTab] = useState(TABS.profile);
  const [editId, setEditId] = useState(null);
  const [profileRefresh, setProfileRefresh] = useState(0);

  return (
    <main>
      <h1>Dashboard</h1>
      <nav aria-label="dashboard-tabs" style={{ marginBottom: 16 }}>
        <button type="button" onClick={() => setTab(TABS.profile)}>
          Профіль
        </button>{' '}
        <button type="button" onClick={() => setTab(TABS.listings)}>
          Мої оголошення
        </button>{' '}
        <button type="button" onClick={() => setTab(TABS.editor)}>
          Створення / редагування
        </button>
      </nav>

      {tab === TABS.profile ? <DashboardProfileTab user={user} refreshKey={profileRefresh} /> : null}
      {tab === TABS.listings ? (
        <DashboardListingsTab
          userId={user?.id || user?.ID}
          onEdit={(id) => {
            setEditId(id);
            setTab(TABS.editor);
          }}
        />
      ) : null}
      {tab === TABS.editor ? (
        <DashboardListingFormTab
          userId={user?.id || user?.ID}
          editId={editId}
          onSaved={() => {
            setProfileRefresh((prev) => prev + 1);
            setTab(TABS.listings);
          }}
        />
      ) : null}
    </main>
  );
}
