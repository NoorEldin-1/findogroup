import { useCallback, useSyncExternalStore } from "react";
import { supabase } from "@/integrations/supabase/client";
const TOKEN_KEY = "findo_student_token";
let state = { status: "loading", data: null, error: "" };
const listeners = /* @__PURE__ */ new Set();
const emit = () => listeners.forEach((l) => l());
const setState = (partial) => {
  state = { ...state, ...partial };
  emit();
};
const subscribe = (cb) => {
  listeners.add(cb);
  return () => {
    listeners.delete(cb);
  };
};
const getSnapshot = () => state;
const getToken = () => {
  try {
    return localStorage.getItem(TOKEN_KEY);
  } catch {
    return null;
  }
};
async function loadSession() {
  const token = getToken();
  if (!token) {
    setState({ status: "guest", data: null });
    return;
  }
  const { data, error } = await supabase.rpc("student_session", { p_token: token });
  if (error || !data) {
    try {
      localStorage.removeItem(TOKEN_KEY);
    } catch {
    }
    setState({ status: "guest", data: null });
    return;
  }
  setState({ status: "authed", data });
}
let initialized = false;
function ensureInit() {
  if (!initialized) {
    initialized = true;
    void loadSession();
  }
}
export function useStudentAuth() {
  ensureInit();
  const s = useSyncExternalStore(subscribe, getSnapshot, getSnapshot);
  const login = useCallback(async (phone, password) => {
    setState({ error: "" });
    const { data, error } = await supabase.rpc("student_login", {
      p_phone: phone.trim(),
      p_password: password
    });
    if (error) {
      setState({ error: "\u062D\u0635\u0644 \u062E\u0637\u0623\u060C \u062D\u0627\u0648\u0644 \u062A\u0627\u0646\u064A" });
      return false;
    }
    if (!data) {
      setState({ error: "\u0631\u0642\u0645 \u0627\u0644\u062A\u0644\u064A\u0641\u0648\u0646 \u0623\u0648 \u0643\u0644\u0645\u0629 \u0627\u0644\u0633\u0631 \u063A\u064A\u0631 \u0635\u062D\u064A\u062D\u0629" });
      return false;
    }
    const token = data.token;
    try {
      localStorage.setItem(TOKEN_KEY, token);
    } catch {
    }
    await loadSession();
    return true;
  }, []);
  const logout = useCallback(async () => {
    const token = getToken();
    if (token) void supabase.rpc("student_logout", { p_token: token }).then(() => {
    });
    try {
      localStorage.removeItem(TOKEN_KEY);
    } catch {
    }
    setState({ status: "guest", data: null, error: "" });
  }, []);
  const refresh = useCallback(() => loadSession(), []);
  return { status: s.status, data: s.data, error: s.error, login, logout, refresh };
}
export default useStudentAuth;
