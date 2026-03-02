import { AsyncState } from '../shared/AsyncState';
import { useApiStatus } from '../shared/useApiStatus';

export function HomePage() {
  const { loading, error, message } = useApiStatus();

  return (
    <main>
      <h1>Home</h1>
      <AsyncState loading={loading} error={error}>
        <p>Сторінка в процесі міграції з legacy PHP.</p>
        <p aria-label="api-status">API status: {message}</p>
      </AsyncState>
    </main>
  );
}
