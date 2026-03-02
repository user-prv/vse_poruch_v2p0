import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { apiClient } from '../api/client';
import { AsyncState } from '../shared/AsyncState';

const PREVIEW_LIMIT = 50;

function normalizeCategories(payload) {
  if (Array.isArray(payload)) {
    return payload;
  }

  if (Array.isArray(payload?.items)) {
    return payload.items;
  }

  return [];
}

export function CategoriesPage() {
  const [searchParams] = useSearchParams();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [categories, setCategories] = useState([]);
  const [listings, setListings] = useState([]);

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
        if (active) {
          setError(requestError.response?.data?.error || requestError.message || 'Не вдалося завантажити категорії');
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    }

    loadCategories();

    return () => {
      active = false;
    };
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
  const selectedListings = listings.filter((listing) => listing.category_id === selectedCategoryId);

  const renderNode = (parentId = 0, level = 0) => {
    const nodes = tree.get(parentId) || [];
    if (nodes.length === 0) {
      return null;
    }

    return (
      <ul>
        {nodes.map((category) => (
          <li key={category.id}>
            <Link to={`/categories?id=${category.id}`}>
              {'—'.repeat(level)} {category.name}
            </Link>
            {' '}
            <Link to={`/?q=${encodeURIComponent(category.name)}`}>Пошук оголошень</Link>
            {renderNode(category.id, level + 1)}
          </li>
        ))}
      </ul>
    );
  };

  return (
    <main>
      <h1>Категорії</h1>
      <p>Навігація по дереву категорій для переходу в пошук і деталі оголошень.</p>

      <AsyncState loading={loading} error={error}>
        {selectedCategory ? (
          <section>
            <h2>Категорія: {selectedCategory.name}</h2>
            {selectedListings.length === 0 ? <p>У вибраній категорії поки немає оголошень.</p> : null}
            <ul>
              {selectedListings.map((listing) => (
                <li key={listing.id}>
                  <Link to={`/item/${listing.id}`}>{listing.title}</Link>
                </li>
              ))}
            </ul>
          </section>
        ) : (
          <p>Оберіть категорію зі списку нижче.</p>
        )}

        {renderNode()}
      </AsyncState>
    </main>
  );
}
