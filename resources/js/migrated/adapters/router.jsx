import { jsx } from "react/jsx-runtime";
import React, { forwardRef } from "react";
export const Link = forwardRef(function Link2({ to, children, replace, state, preventScrollReset, ...props }, ref) {
  return /* @__PURE__ */ jsx("a", { ref, href: typeof to === "string" ? to : to.pathname, ...props, children });
});
export const NavLink = Link;
export const useNavigate = () => (to) => {
  location.href = to;
};
export const useLocation = () => ({ pathname: location.pathname, search: location.search, hash: location.hash });
export const useParams = () => ({ id: decodeURIComponent(location.pathname.split("/").pop()), slug: decodeURIComponent(location.pathname.split("/").pop()) });
export const useSearchParams = () => [new URLSearchParams(location.search), (v) => {
  location.search = new URLSearchParams(v).toString();
}];
