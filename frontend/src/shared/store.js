import { create } from 'zustand';

const STORAGE_KEY = 'vseporuch.session';

function readSession() {
  if (typeof window === 'undefined') {
    return { token: null, user: null };
  }

  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) {
      return { token: null, user: null };
    }

    const parsed = JSON.parse(raw);
    return {
      token: parsed?.token || null,
      user: parsed?.user || null,
    };
  } catch {
    return { token: null, user: null };
  }
}

function writeSession(token, user) {
  if (typeof window === 'undefined') {
    return;
  }

  if (!token) {
    window.localStorage.removeItem(STORAGE_KEY);
    return;
  }

  window.localStorage.setItem(STORAGE_KEY, JSON.stringify({ token, user }));
}

const initialSession = readSession();

export const useAppStore = create((set) => ({
  user: initialSession.user,
  token: initialSession.token,
  isAuthenticated: Boolean(initialSession.token),
  setSession: ({ token, user }) =>
    set(() => {
      writeSession(token, user);
      return {
        token,
        user: user || null,
        isAuthenticated: Boolean(token),
      };
    }),
  clearSession: () =>
    set(() => {
      writeSession(null, null);
      return {
        token: null,
        user: null,
        isAuthenticated: false,
      };
    }),
}));
