import { insertLocal } from "./local-api.jsx";
const unavailable = () => Promise.resolve({ data: null, error: new Error("Account services will be connected in the backend phase.") });
export const supabase = { from(table) {
  const read = { select() {
    return read;
  }, eq() {
    return read;
  }, order() {
    return read;
  }, limit() {
    return read;
  }, single() {
    return Promise.resolve({ data: null, error: null });
  }, then(resolve, reject) {
    return Promise.resolve({ data: [], error: null }).then(resolve, reject);
  }, insert(row) {
    return insertLocal(table, row);
  }, upsert(row) {
    return insertLocal(table, row);
  } };
  return read;
}, rpc: unavailable, auth: { getSession: unavailable, onAuthStateChange() {
  return { data: { subscription: { unsubscribe() {
  } } } };
} } };
