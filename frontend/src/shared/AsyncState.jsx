export function AsyncState({ loading, error, children }) {
  if (loading) {
    return <p role="status" aria-live="polite">Loading…</p>;
  }

  if (error) {
    return <p role="alert">{error}</p>;
  }

  return children;
}
