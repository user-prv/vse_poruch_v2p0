import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { apiClient } from '../api/client';
import { AsyncState } from '../shared/AsyncState';
import { unwrapApiData, unwrapApiItems } from '../shared/apiPayload';

const MAX_PROFILE_LISTINGS = 50;

export function UserProfilePage() {
  const { id } = useParams();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [profile, setProfile] = useState(null);
  const [listings, setListings] = useState([]);

  useEffect(() => {
    let active = true;

    async function loadProfile() {
      setLoading(true);
      setError('');

      try {
        const [profileResponse, listingsResponse] = await Promise.all([
          apiClient.get(`/profile/${id}`),
          apiClient.get('/listings', {
            params: {
              page: 1,
              limit: MAX_PROFILE_LISTINGS,
            },
          }),
        ]);

        if (!active) {
          return;
        }

        setProfile(unwrapApiData(profileResponse.data));
        const authorListings = unwrapApiItems(listingsResponse.data).filter((listing) => String(listing.author_id) === String(id));
        setListings(authorListings);
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
  }, [id]);

  return (
    <main>
      <h1>Профіль користувача</h1>

      <AsyncState loading={loading} error={error}>
        {profile ? (
          <section>
            <h2>Користувач #{profile.id}</h2>
            <p>Email: {profile.email}</p>
            <p>Роль: {profile.role}</p>
          </section>
        ) : null}

        <section>
          <h2>Оголошення користувача</h2>
          {listings.length === 0 ? <p>У цього користувача поки немає оголошень.</p> : null}
          <ul>
            {listings.map((listing) => (
              <li key={listing.id}>
                <Link to={`/item/${listing.id}`}>{listing.title}</Link> · {listing.status}
              </li>
            ))}
          </ul>
        </section>
      </AsyncState>
    </main>
  );
}
