import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { register } from '../api/auth';

export function RegisterPage() {
  const navigate = useNavigate();
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
      await register({ email: email.trim().toLowerCase() });
      navigate('/login', { replace: true });
    } catch (requestError) {
      setError(requestError.response?.data?.error || requestError.message || 'Не вдалося створити акаунт.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <main>
      <h1>Реєстрація</h1>
      <form onSubmit={handleSubmit} noValidate>
        <label htmlFor="register-email">Email</label>
        <input id="register-email" type="email" value={email} onChange={(event) => setEmail(event.target.value)} required />
        {error ? <p role="alert">{error}</p> : null}
        <button type="submit" disabled={loading}>{loading ? 'Реєструємо...' : 'Зареєструватись'}</button>
      </form>
      <p><Link to="/login">Вже маєте акаунт? Увійти</Link></p>
    </main>
  );
}
