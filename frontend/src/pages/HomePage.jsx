import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { apiClient } from '../api/client';
import { AsyncState } from '../shared/AsyncState';

const PAGE_SIZE = 10;

function normalizeCategories(payload) {
  if (Array.isArray(payload)) {
    return payload;
  }

  if (Array.isArray(payload?.items)) {
    return payload.items;
  }

  return [];
}

export function HomePage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [listings, setListings] = useState([]);
  const [total, setTotal] = useState(0);
  const [categories, setCategories] = useState([]);

  const page = Math.max(1, Number(searchParams.get('page') || 1));
  const q = searchParams.get('q') || '';

  useEffect(() => {
    let active = true;

    async function loadData() {
      setLoading(true);
      setError('');
      try {
        const [listingsResponse, categoriesResponse] = await Promise.all([
          apiClient.get('/listings', {
            params: {
              page,
              limit: PAGE_SIZE,
              q: q || undefined,
            },
          }),
          apiClient.get('/categories'),
        ]);

        if (!active) {
          return;
        }

        setListings(listingsResponse.data.items || []);
        setTotal(listingsResponse.data.total || 0);
        setCategories(normalizeCategories(categoriesResponse.data));
      } catch (requestError) {
        if (!active) {
          return;
        }
        setError(requestError.response?.data?.error || requestError.message || 'Не вдалося завантажити оголошення');
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    }

    loadData();

    return () => {
      active = false;
    };
  }, [page, q]);

  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  const categoryById = useMemo(
    () => Object.fromEntries(categories.map((category) => [category.id, category])),
    [categories],
  );

  const applyFilters = (event) => {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const next = new URLSearchParams();
    const nextQ = String(formData.get('q') || '').trim();

    if (nextQ) {
      next.set('q', nextQ);
    }
    next.set('page', '1');

    setSearchParams(next);
  };

  const changePage = (nextPage) => {
    const next = new URLSearchParams(searchParams);
    next.set('page', String(nextPage));
    setSearchParams(next);
  };

  return (
    <main className="home-page">
      <section className="market-grid">
        <aside className="results-panel">
          <div className="panel-head">
            <h1>Поруч</h1>
            <p>Карта товарів/послуг поблизу</p>

            <form onSubmit={applyFilters} aria-label="listing-filters" className="search-row">
              <input name="q" defaultValue={q} placeholder="Пошук: суші, зарядка, ковбаса..." />
              <button type="submit">Знайти</button>
            </form>

            <p className="geo-hint">
              Геолокація визначається автоматично при вході (одноразово). Якщо браузер питає дозвіл — натисни “Allow”. Якщо
              відмовиш — показуватиметься карта за останньою збереженою локацією або за Києвом.
            </p>

          </div>

          <AsyncState loading={loading} error={error}>
            <div className="results-header">Результати: {total}</div>

            {listings.length === 0 ? <p className="empty-state">Немає оголошень за цими фільтрами.</p> : null}

            <ul className="listing-list">
              {listings.map((listing) => (
                <li key={listing.id}>
                  <article className="listing-card">
                    <img
                      className="listing-thumb"
                      src={`https://picsum.photos/seed/vseporuch-${listing.id}/96/72`}
                      alt={listing.title}
                      loading="lazy"
                    />

                    <div className="listing-content">
                      <div className="listing-title-row">
                        <h2>
                          <Link to={`/item/${listing.id}`}>{listing.title}</Link>
                        </h2>
                        <strong>{listing.price ? `${listing.price} UAH` : `${listing.id * 50} UAH`}</strong>
                      </div>
                      <p>
                        {categoryById[listing.category_id]?.name || `Категорія #${listing.category_id}`} • {6 + (listing.id % 5)}.{listing.id % 10}{' '}
                        км
                      </p>

                      <div className="listing-actions">
                        <Link to={`/item/${listing.id}`}>Детальніше →</Link>
                        <Link to={`/user/${listing.author_id}`}>На мапі</Link>
                      </div>
                    </div>
                  </article>
                </li>
              ))}
            </ul>

            <nav aria-label="pagination" className="pagination-row">
              <button type="button" disabled={page <= 1} onClick={() => changePage(page - 1)}>
                Попередня
              </button>
              <span>
                {page} / {totalPages}
              </span>
              <button type="button" disabled={page >= totalPages} onClick={() => changePage(page + 1)}>
                Наступна
              </button>
            </nav>
          </AsyncState>
        </aside>

        <section className="map-panel" aria-label="map">
          <iframe
            title="Карта"
            src="https://www.openstreetmap.org/export/embed.html?bbox=30.39%2C50.39%2C30.67%2C50.53&layer=mapnik&marker=50.4501%2C30.5234"
          />
        </section>
      </section>
    </main>
  );
}
