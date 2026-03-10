import { useEffect, useMemo, useRef, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { apiClient } from '../api/client';
import { AsyncState } from '../shared/AsyncState';
import { distanceKm } from '../shared/listingUtils';

const PREVIEW_LIMIT = 100;
const KYIV = { lat: 50.4501, lng: 30.5234 };

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

function normalizeCategories(payload) {
  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload?.items)) return payload.items;
  return [];
}

function CategoryMap({ items, activeId, onSelect }) {
  const hostRef = useRef(null);
  const mapRef = useRef(null);

  useEffect(() => {
    let mounted = true;
    ensureLeaflet().then((L) => {
      if (!mounted || !hostRef.current || mapRef.current) return;
      const map = L.map(hostRef.current).setView([KYIV.lat, KYIV.lng], 11);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
      mapRef.current = { L, map, markers: new Map() };
    }).catch(() => {});

    return () => {
      mounted = false;
      if (mapRef.current) {
        mapRef.current.map.remove();
        mapRef.current = null;
      }
    };
  }, []);

  useEffect(() => {
    const mapState = mapRef.current;
    if (!mapState) return;
    mapState.markers.forEach((m) => m.remove());
    mapState.markers.clear();

    const withCoords = items.filter((i) => Number.isFinite(i.lat) && Number.isFinite(i.lng));
    if (withCoords.length === 0) {
      mapState.map.setView([KYIV.lat, KYIV.lng], 11);
      return;
    }

    const bounds = [];
    withCoords.forEach((item) => {
      const marker = mapState.L.marker([item.lat, item.lng]).addTo(mapState.map);
      marker.bindPopup(item.title || `#${item.id}`);
      marker.on('click', () => onSelect(item.id));
      if (item.id === activeId) marker.openPopup();
      mapState.markers.set(item.id, marker);
      bounds.push([item.lat, item.lng]);
    });
    mapState.map.fitBounds(bounds, { padding: [20, 20] });
  }, [items, activeId, onSelect]);

  return <div ref={hostRef} className="category-map-host" aria-label="Карта категорії" />;
}

export function CategoriesPage() {
  const [searchParams] = useSearchParams();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [categories, setCategories] = useState([]);
  const [listings, setListings] = useState([]);
  const [sort, setSort] = useState('price_asc');
  const [activeListingId, setActiveListingId] = useState(null);

  const selectedCategoryId = Number(searchParams.get('id') || 0);

  useEffect(() => {
    let active = true;
    async function loadCategories() {
      setLoading(true);
      setError('');
      try {
        const [categoriesResponse, listingsResponse] = await Promise.all([
          apiClient.get('/categories'),
          apiClient.get('/listings', { params: { page: 1, limit: PREVIEW_LIMIT } }),
        ]);
        if (active) {
          setCategories(normalizeCategories(categoriesResponse.data));
          setListings(listingsResponse.data.items || []);
        }
      } catch (requestError) {
        if (active) setError(requestError.response?.data?.error || requestError.message || 'Не вдалося завантажити категорії');
      } finally {
        if (active) setLoading(false);
      }
    }
    loadCategories();
    return () => { active = false; };
  }, []);

  const tree = useMemo(() => {
    const byParent = new Map();
    categories.forEach((category) => {
      const key = category.parent_id ?? 0;
      const group = byParent.get(key) || [];
      group.push(category);
      byParent.set(key, group);
    });
    return byParent;
  }, [categories]);

  const selectedCategory = categories.find((category) => category.id === selectedCategoryId);
  const selectedListings = useMemo(() => {
    const base = listings
      .filter((listing) => listing.category_id === selectedCategoryId)
      .map((item) => ({ ...item, distance: distanceKm(KYIV, { lat: item.lat, lng: item.lng }) }));
    if (sort === 'price_desc') return [...base].sort((a, b) => (b.price || 0) - (a.price || 0));
    if (sort === 'distance') return [...base].sort((a, b) => (a.distance ?? 1e9) - (b.distance ?? 1e9));
    return [...base].sort((a, b) => (a.price || 0) - (b.price || 0));
  }, [listings, selectedCategoryId, sort]);

  const renderNode = (parentId = 0, level = 0) => {
    const nodes = tree.get(parentId) || [];
    if (nodes.length === 0) return null;
    return (
      <ul>
        {nodes.map((category) => (
          <li key={category.id}>
            <Link to={`/categories?id=${category.id}`}>{'—'.repeat(level)} {category.name}</Link> <Link to={`/?q=${encodeURIComponent(category.name)}`}>Пошук оголошень</Link>
            {renderNode(category.id, level + 1)}
          </li>
        ))}
      </ul>
    );
  };

  return (
    <main>
      <h1>Категорії</h1>
      <AsyncState loading={loading} error={error}>
        {selectedCategory ? (
          <section>
            <h2>Категорія: {selectedCategory.name}</h2>
            <label>
              Сортування:
              <select value={sort} onChange={(event) => setSort(event.target.value)}>
                <option value="price_asc">Ціна ↑</option>
                <option value="price_desc">Ціна ↓</option>
                <option value="distance">Відстань</option>
              </select>
            </label>
            {selectedListings.length === 0 ? <p>У вибраній категорії поки немає оголошень.</p> : null}
            <div className="category-layout">
              <ul>
                {selectedListings.map((listing) => (
                  <li
                    key={listing.id}
                    onMouseEnter={() => setActiveListingId(listing.id)}
                    className={`category-list-item ${listing.id === activeListingId ? 'is-active' : ''}`}
                  >
                    <Link to={`/item/${listing.id}`}>{listing.title}</Link> · {listing.price || 0} UAH · {listing.distance == null ? 'нема координат' : `${listing.distance} км`}
                    <button type="button" onClick={() => setActiveListingId(listing.id)}>Показати на мапі</button>
                  </li>
                ))}
              </ul>
              <CategoryMap items={selectedListings} activeId={activeListingId} onSelect={setActiveListingId} />
            </div>
          </section>
        ) : (
          <p>Оберіть категорію зі списку нижче.</p>
        )}
        {renderNode()}
      </AsyncState>
    </main>
  );
}
