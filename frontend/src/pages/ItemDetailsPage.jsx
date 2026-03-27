import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { apiClient } from '../api/client';
import { AsyncState } from '../shared/AsyncState';
import { unwrapApiData } from '../shared/apiPayload';

function buildMockGallery(id) {
  return [1, 2, 3].map((index) => ({
    id: `${id}-${index}`,
    url: `https://picsum.photos/seed/listing-${id}-${index}/900/600`,
    alt: `Фото оголошення ${id} #${index}`,
  }));
}

function normalizeCategories(payload) {
  const data = unwrapApiData(payload);
  if (Array.isArray(data)) {
    return data;
  }
  if (Array.isArray(data?.items)) {
    return data.items;
  }
  return [];
}

export function ItemDetailsPage() {
  const { id } = useParams();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [listing, setListing] = useState(null);
  const [author, setAuthor] = useState(null);
  const [categories, setCategories] = useState([]);
  const [activePhoto, setActivePhoto] = useState(0);

  useEffect(() => {
    let active = true;

    async function loadDetails() {
      setLoading(true);
      setError('');
      setActivePhoto(0);

      try {
        const listingResponse = await apiClient.get(`/listings/${id}`);
        if (!active) {
          return;
        }

        const listingData = unwrapApiData(listingResponse.data);
        setListing(listingData);

        const shouldLoadAuthor = Boolean(listingData.author_id);
        const [authorResponse, categoriesResponse] = await Promise.all([
          shouldLoadAuthor ? apiClient.get(`/profile/${listingData.author_id}`) : Promise.resolve({ data: null }),
          apiClient.get('/categories'),
        ]);

        if (!active) {
          return;
        }

        setAuthor(unwrapApiData(authorResponse.data));
        setCategories(normalizeCategories(categoriesResponse.data));
      } catch (requestError) {
        if (active) {
          setError(requestError.response?.data?.error || requestError.message || 'Не вдалося завантажити картку оголошення');
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    }

    loadDetails();

    return () => {
      active = false;
    };
  }, [id]);

  const gallery = useMemo(() => buildMockGallery(id), [id]);
  const categoryName = categories.find((category) => category.id === listing?.category_id)?.name;

  return (
    <main>
      <h1>Деталі оголошення</h1>

      <AsyncState loading={loading} error={error}>
        {listing ? (
          <article>
            <h2>{listing.title}</h2>
            <p>
              Статус: <strong>{listing.status}</strong> · Категорія: {categoryName || `#${listing.category_id}`}
            </p>
            <p>{listing.body || 'Опис відсутній.'}</p>

            <section aria-label="photo-gallery">
              <h3>Галерея фото</h3>
              <img src={gallery[activePhoto].url} alt={gallery[activePhoto].alt} width="600" />
              <div>
                {gallery.map((photo, index) => (
                  <button key={photo.id} type="button" onClick={() => setActivePhoto(index)}>
                    Фото {index + 1}
                  </button>
                ))}
              </div>
            </section>

            <section aria-label="contact-block">
              <h3>Контакти автора</h3>
              <p>Email: {author?.email || 'невідомо'}</p>
              <p>Роль: {author?.role || 'user'}</p>
              <p>
                <Link to={`/user/${listing.author_id}`}>Перейти в профіль автора</Link>
              </p>
            </section>
          </article>
        ) : null}
      </AsyncState>
    </main>
  );
}
