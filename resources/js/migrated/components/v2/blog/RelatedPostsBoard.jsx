import { jsx, jsxs } from "react/jsx-runtime";
import { Board, BoardHeader, RiseIn } from "@/components/v2/primitives";
import PostCard from "./PostCard";
const RelatedPostsBoard = ({ posts }) => /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { dir: "rtl", className: "mx-auto w-full max-w-5xl", children: [
  /* @__PURE__ */ jsx(BoardHeader, { index: "02", eyebrow: "\u062A\u0627\u0628\u0639 \u0627\u0644\u0642\u0631\u0627\u0621\u0629", pre: "\u0645\u0642\u0627\u0644\u0627\u062A", accent: "\u0630\u0627\u062A \u0635\u0644\u0629" }),
  /* @__PURE__ */ jsx("div", { className: "grid grid-cols-1 gap-4 md:grid-cols-3", children: posts.map((post) => /* @__PURE__ */ jsx(PostCard, { post }, post.id)) })
] }) });
export default RelatedPostsBoard;
