import { useEffect, useState } from 'react';
import { apiClient } from '../api/client';

export function useApiStatus() {
  const [state, setState] = useState({ loading: true, error: '', message: '' });

  useEffect(() => {
    let active = true;
    apiClient
      .get('/ping')
      .then((res) => {
        if (!active) return;
        setState({ loading: false, error: '', message: res.data?.data?.message ?? 'ok' });
      })
      .catch(() => {
        if (!active) return;
        setState({ loading: false, error: 'API недоступне. Перевірте backend.', message: '' });
      });

    return () => {
      active = false;
    };
  }, []);

  return state;
}
