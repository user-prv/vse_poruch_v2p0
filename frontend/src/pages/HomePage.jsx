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
  const status = searchParams.get('status') || '';

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
              status: status || undefined,
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
  }, [page, q, status]);

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
    const nextStatus = String(formData.get('status') || '').trim();

    if (nextQ) {
      next.set('q', nextQ);
    }
    if (nextStatus) {
      next.set('status', nextStatus);
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
    <main>
      <h1>Оголошення</h1>
      <p>Перегляд і пошук активних оголошень за сценаріями legacy.</p>

      <form onSubmit={applyFilters} aria-label="listing-filters">
        <input name="q" defaultValue={q} placeholder="Пошук по назві або опису" />
        <select name="status" defaultValue={status}>
          <option value="">Усі статуси</option>
          <option value="active">active</option>
          <option value="pending">pending</option>
          <option value="approved">approved</option>
          <option value="rejected">rejected</option>
        </select>
        <button type="submit">Фільтрувати</button>
      </form>

      <AsyncState loading={loading} error={error}>
        <p>
          Знайдено: <strong>{total}</strong>. Сторінка {page} з {totalPages}.
        </p>

        <ul>
          {listings.map((listing) => (
            <li key={listing.id}>
              <article>
                <h2>
                  <Link to={`/item/${listing.id}`}>{listing.title}</Link>
                </h2>
                <p>{listing.body || 'Опис відсутній.'}</p>
                <p>
                  Категорія: {categoryById[listing.category_id]?.name || `#${listing.category_id}`} · Статус: {listing.status}
                </p>
                <p>
                  <Link to={`/user/${listing.author_id}`}>Профіль автора #{listing.author_id}</Link>
                </p>
              </article>
            </li>
          ))}
        </ul>

        {listings.length === 0 ? <p>Немає оголошень за цими фільтрами.</p> : null}

        <nav aria-label="pagination">
          <button type="button" disabled={page <= 1} onClick={() => changePage(page - 1)}>
            Попередня
          </button>
          <span> {page} / {totalPages} </span>
          <button type="button" disabled={page >= totalPages} onClick={() => changePage(page + 1)}>
            Наступна
          </button>
        </nav>
      </AsyncState>
    </main>
  );
}
