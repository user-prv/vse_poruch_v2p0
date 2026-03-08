import { useEffect, useMemo, useRef, useState } from 'react';
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


function parseUserIdFromToken(token) {
  const matched = /^user-(\d+)$/.exec(String(token || ''));
  return matched ? Number(matched[1]) : null;
}

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
          setProfile(data?.data || data);
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

let leafletLoader;
function ensureLeaflet() {
  if (window.L) return Promise.resolve(window.L);
  if (leafletLoader) return leafletLoader;
  leafletLoader = new Promise((resolve, reject) => {
    if (!document.querySelector('link[data-leaflet]')) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.dataset.leaflet = 'true';
      link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
      document.head.appendChild(link);
    }
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = () => resolve(window.L);
    script.onerror = () => reject(new Error('Не вдалося завантажити карту'));
    document.body.appendChild(script);
  });
  return leafletLoader;
}

function CoordinatesPicker({ lat, lng, onChange }) {
  const mapRef = useRef(null);
  const hostRef = useRef(null);

  useEffect(() => {
    let active = true;
    let clickHandler;

    ensureLeaflet()
      .then((L) => {
        if (!active || !hostRef.current || mapRef.current) return;
        const initialLat = Number.isFinite(lat) ? lat : 50.4501;
        const initialLng = Number.isFinite(lng) ? lng : 30.5234;
        const map = L.map(hostRef.current).setView([initialLat, initialLng], Number.isFinite(lat) ? 14 : 6);
        mapRef.current = { map, marker: null, L };

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        if (Number.isFinite(lat) && Number.isFinite(lng)) {
          mapRef.current.marker = L.marker([lat, lng]).addTo(map);
        }

        clickHandler = (event) => {
          const nextLat = Number(event.latlng.lat.toFixed(6));
          const nextLng = Number(event.latlng.lng.toFixed(6));
          const current = mapRef.current;
          if (!current.marker) {
            current.marker = L.marker([nextLat, nextLng]).addTo(current.map);
          } else {
            current.marker.setLatLng([nextLat, nextLng]);
          }
          onChange(nextLat, nextLng);
        };

        map.on('click', clickHandler);
      })
      .catch(() => {
        // no-op, parent form will still allow manual coordinates
      });

    return () => {
      active = false;
      if (mapRef.current) {
        if (clickHandler) {
          mapRef.current.map.off('click', clickHandler);
        }
        mapRef.current.map.remove();
        mapRef.current = null;
      }
    };
  }, []);

  return <div ref={hostRef} className="listing-map-picker" aria-label="Карта для вибору координат" />;
}

function DashboardListingFormTab({ userId, editId, onSaved }) {
  const [form, setForm] = useState({
    title: '',
    body: '',
    category_id: '',
    price: '',
    currency: 'UAH',
    lat: '',
    lng: '',
    status: 'pending',
  });
  const [photos, setPhotos] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const safeCategories = Array.isArray(categories) ? categories : [];

  useEffect(() => {
    let active = true;

    async function loadContext() {
      try {
        const categoriesResponse = await apiClient.get('/categories');
        const categoriesPayload = categoriesResponse.data?.data || categoriesResponse.data;
        const normalizedCategories = Array.isArray(categoriesPayload)
          ? categoriesPayload
          : Array.isArray(categoriesPayload?.items)
            ? categoriesPayload.items
            : [];
        if (active) {
          setCategories(normalizedCategories);
        }
        if (editId) {
          const { data } = await apiClient.get(`/listings/${editId}`);
          if (active) {
            const listing = data?.data || data;
            setForm({
              title: listing.title || '',
              body: listing.body || '',
              category_id: String(listing.category_id || ''),
              price: String(listing.price ?? ''),
              currency: listing.currency || 'UAH',
              lat: listing.lat == null ? '' : String(listing.lat),
              lng: listing.lng == null ? '' : String(listing.lng),
              status: listing.status || 'pending',
            });
            const normalizedPhotos = Array.isArray(listing.photo_paths) ? listing.photo_paths : [];
            setPhotos(normalizedPhotos.map((path, idx) => ({ id: `${path}-${idx}`, path })));
          }
        } else if (active) {
          setForm({ title: '', body: '', category_id: '', price: '', currency: 'UAH', lat: '', lng: '', status: 'pending' });
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
      const uploadPayload = data?.data || data;
      setPhotos((prev) => [...prev, { id: `${uploadPayload.path}-${Date.now()}`, path: uploadPayload.path }]);
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

    const normalizedAuthorId = Number(userId);
    const normalizedCategoryId = Number(form.category_id);
    const normalizedPrice = Number(form.price);
    const normalizedLat = form.lat === '' ? null : Number(form.lat);
    const normalizedLng = form.lng === '' ? null : Number(form.lng);

    if (!Number.isInteger(normalizedAuthorId) || normalizedAuthorId <= 0) {
      setLoading(false);
      setError('Не вдалося визначити користувача. Оновіть сторінку та увійдіть повторно.');
      return;
    }

    if (form.title.trim().length < 3) {
      setLoading(false);
      setError('Назва оголошення має містити щонайменше 3 символи');
      return;
    }

    if (!Number.isInteger(normalizedCategoryId) || normalizedCategoryId <= 0) {
      setLoading(false);
      setError('Оберіть категорію оголошення');
      return;
    }

    if (form.price !== '' && (!Number.isFinite(normalizedPrice) || normalizedPrice < 0)) {
      setLoading(false);
      setError('Ціна повинна бути числом, не меншим за 0');
      return;
    }

    if ((normalizedLat !== null && !Number.isFinite(normalizedLat)) || (normalizedLng !== null && !Number.isFinite(normalizedLng))) {
      setLoading(false);
      setError('Координати повинні бути коректними числами');
      return;
    }

    if (!allowedStatuses.includes(form.status)) {
      setLoading(false);
      setError('Некоректний перехід статусу для оголошення');
      return;
    }

    const payload = {
      title: form.title.trim(),
      body: form.body,
      author_id: normalizedAuthorId,
      category_id: normalizedCategoryId,
      price: Number.isFinite(normalizedPrice) && normalizedPrice > 0 ? normalizedPrice : 0,
      currency: form.currency || 'UAH',
      lat: normalizedLat,
      lng: normalizedLng,
      status: editId ? form.status : 'pending',
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
        setForm({ title: '', body: '', category_id: '', price: '', currency: 'UAH', lat: '', lng: '', status: 'pending' });
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
    <section className="listing-editor-shell">
      <article className="listing-editor-card">
        <h2 className="listing-editor-title">Додати оголошення</h2>
        <p className="listing-editor-subtitle">Можна додати до 5 фото. Координати обираються на карті натисканням на точку.</p>

        <form onSubmit={saveListing} className="listing-editor-form">
          <input
            className="listing-editor-input"
            placeholder="Назва (наприклад: Продам велосипед)"
            value={form.title}
            onChange={(event) => setForm((prev) => ({ ...prev, title: event.target.value }))}
          />

          <textarea
            className="listing-editor-textarea"
            placeholder="Опис (стан, деталі, контакти)"
            value={form.body}
            onChange={(event) => setForm((prev) => ({ ...prev, body: event.target.value }))}
          />

          <select
            className="listing-editor-select"
            value={form.category_id}
            onChange={(event) => setForm((prev) => ({ ...prev, category_id: event.target.value }))}
          >
            <option value="">— Обери категорію —</option>
            {safeCategories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>

          <div className="listing-editor-row">
            <input
              className="listing-editor-input"
              placeholder="Ціна"
              type="number"
              min="0"
              value={form.price}
              onChange={(event) => setForm((prev) => ({ ...prev, price: event.target.value }))}
            />
            <input
              className="listing-editor-input"
              placeholder="Валюта"
              value={form.currency}
              onChange={(event) => setForm((prev) => ({ ...prev, currency: event.target.value.toUpperCase() }))}
            />
          </div>

          <CoordinatesPicker
            lat={form.lat === '' ? null : Number(form.lat)}
            lng={form.lng === '' ? null : Number(form.lng)}
            onChange={(nextLat, nextLng) => setForm((prev) => ({ ...prev, lat: String(nextLat), lng: String(nextLng) }))}
          />

          <div className="listing-editor-row">
            <input
              className="listing-editor-input"
              placeholder="lat"
              value={form.lat}
              onChange={(event) => setForm((prev) => ({ ...prev, lat: event.target.value }))}
            />
            <input
              className="listing-editor-input"
              placeholder="lng"
              value={form.lng}
              onChange={(event) => setForm((prev) => ({ ...prev, lng: event.target.value }))}
            />
          </div>

          <select
            className="listing-editor-select"
            value={form.status}
            onChange={(event) => setForm((prev) => ({ ...prev, status: event.target.value }))}
          >
            {allowedStatuses.map((status) => (
              <option key={status} value={status}>
                {status}
              </option>
            ))}
          </select>

          <input className="listing-editor-input" type="file" accept="image/*" onChange={uploadPhoto} />

          {photos.length > 0 ? (
            <ul className="listing-photo-list">
              {photos.map((photo, index) => (
                <li key={photo.id}>
                  <span>{photo.path}</span>
                  <div>
                    <button type="button" onClick={() => movePhoto(index, -1)}>
                      ↑
                    </button>
                    <button type="button" onClick={() => movePhoto(index, 1)}>
                      ↓
                    </button>
                    <button type="button" onClick={() => removePhoto(photo.id)}>
                      Видалити
                    </button>
                  </div>
                </li>
              ))}
            </ul>
          ) : null}

          {error ? <p role="alert" className="listing-editor-error">{error}</p> : null}
          {success ? <p className="listing-editor-success">{success}</p> : null}

          <div className="listing-editor-actions">
            <button type="submit" disabled={loading} className="listing-editor-save-btn">
              {loading ? 'Збереження...' : 'Зберегти'}
            </button>
          </div>
        </form>
      </article>
    </section>
  );
}

export function DashboardPage() {
  const user = useAppStore((state) => state.user);
  const token = useAppStore((state) => state.token);
  const [tab, setTab] = useState(TABS.profile);
  const resolvedUserId = user?.id || user?.ID || parseUserIdFromToken(token);
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
          userId={resolvedUserId}
          onEdit={(id) => {
            setEditId(id);
            setTab(TABS.editor);
          }}
        />
      ) : null}
      {tab === TABS.editor ? (
        <DashboardListingFormTab
          userId={resolvedUserId}
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
