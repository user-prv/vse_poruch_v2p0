import { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { login } from '../api/auth';
import { apiClient } from '../api/client';
import { useAppStore } from '../shared/store';

function parseUserIdFromToken(token) {
  const matched = /^user-(\d+)$/.exec(String(token || ''));
  return matched ? Number(matched[1]) : null;
}

export function LoginPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const setSession = useAppStore((state) => state.setSession);

  const [email, setEmail] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const validate = () => {
    const normalized = email.trim().toLowerCase();
    if (!normalized) {
      return 'Вкажіть email.';
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalized)) {
      return 'Некоректний формат email.';
    }
    return '';
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    const validationError = validate();
    if (validationError) {
      setError(validationError);
      return;
    }

    setLoading(true);
    setError('');

    try {
      const normalizedEmail = email.trim().toLowerCase();
      const loginResponse = await login({ email: normalizedEmail });
      const token = loginResponse?.token;

      if (!token) {
        throw new Error('Токен не отримано');
      }

      const userId = parseUserIdFromToken(token);
      let profile = { email: normalizedEmail };

      if (userId) {
        const { data } = await apiClient.get(`/profile/${userId}`);
        profile = data?.data || data;
      }

      setSession({ token, user: profile });
      navigate(location.state?.from || '/dashboard', { replace: true });
    } catch (requestError) {
      setError(requestError.response?.data?.error || requestError.message || 'Не вдалося виконати вхід.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <main>
      <h1>Вхід</h1>
      <form onSubmit={handleSubmit} noValidate>
        <label htmlFor="login-email">Email</label>
        <input id="login-email" type="email" value={email} onChange={(event) => setEmail(event.target.value)} required />
        {error ? <p role="alert">{error}</p> : null}
        <button type="submit" disabled={loading}>{loading ? 'Входимо...' : 'Увійти'}</button>
      </form>
      <p><Link to="/register">Створити акаунт</Link></p>
      <p><Link to="/reset-password">Забули пароль?</Link></p>
    </main>
  );
}
