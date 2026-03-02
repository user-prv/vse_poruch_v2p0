import { useState } from 'react';
import { Link } from 'react-router-dom';
import { resetPassword } from '../api/auth';

export function ResetPasswordPage() {
  const [email, setEmail] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
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
      setSuccess('');
      return;
    }

    setLoading(true);
    setError('');
    setSuccess('');

    try {
      const response = await resetPassword({ email: email.trim().toLowerCase() });
      setSuccess(response?.message || 'Лист для скидання пароля надіслано.');
    } catch (requestError) {
      setError(requestError.response?.data?.error || requestError.message || 'Не вдалося виконати запит.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <main>
      <h1>Скидання пароля</h1>
      <form onSubmit={handleSubmit} noValidate>
        <label htmlFor="reset-email">Email</label>
        <input id="reset-email" type="email" value={email} onChange={(event) => setEmail(event.target.value)} required />
        {error ? <p role="alert">{error}</p> : null}
        {success ? <p role="status">{success}</p> : null}
        <button type="submit" disabled={loading}>{loading ? 'Надсилаємо...' : 'Надіслати'}</button>
      </form>
      <p><Link to="/login">Повернутись до входу</Link></p>
    </main>
  );
}
