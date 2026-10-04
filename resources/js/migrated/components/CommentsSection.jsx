import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { MessageCircle, Send, User, Loader2 } from "lucide-react";
import { useComments } from "@/hooks/useWordPress";
import { formatDate } from "@/lib/wordpress";
import { toast } from "@/hooks/use-toast";
const CommentsSection = ({ postId }) => {
  const { comments, isLoading, addComment } = useComments(postId);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formData, setFormData] = useState({
    author_name: "",
    author_email: "",
    content: ""
  });
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!formData.author_name || !formData.author_email || !formData.content) {
      toast({
        title: "\u062E\u0637\u0623",
        description: "\u064A\u0631\u062C\u0649 \u0645\u0644\u0621 \u062C\u0645\u064A\u0639 \u0627\u0644\u062D\u0642\u0648\u0644 \u0627\u0644\u0645\u0637\u0644\u0648\u0628\u0629",
        variant: "destructive"
      });
      return;
    }
    setIsSubmitting(true);
    try {
      await addComment(formData);
      setFormData({ author_name: "", author_email: "", content: "" });
      toast({
        title: "\u062A\u0645 \u0625\u0631\u0633\u0627\u0644 \u0627\u0644\u062A\u0639\u0644\u064A\u0642",
        description: "\u062A\u0639\u0644\u064A\u0642\u0643 \u0641\u064A \u0627\u0646\u062A\u0638\u0627\u0631 \u0627\u0644\u0645\u0648\u0627\u0641\u0642\u0629"
      });
    } catch (err) {
      toast({
        title: "\u062E\u0637\u0623",
        description: err.message || "\u0641\u0634\u0644 \u0641\u064A \u0625\u0631\u0633\u0627\u0644 \u0627\u0644\u062A\u0639\u0644\u064A\u0642",
        variant: "destructive"
      });
    } finally {
      setIsSubmitting(false);
    }
  };
  return /* @__PURE__ */ jsx("section", { className: "py-12", children: /* @__PURE__ */ jsxs("div", { className: "max-w-4xl mx-auto", children: [
    /* @__PURE__ */ jsxs(
      motion.div,
      {
        initial: { opacity: 0, y: 20 },
        whileInView: { opacity: 1, y: 0 },
        viewport: { once: true },
        className: "flex items-center gap-3 mb-8",
        children: [
          /* @__PURE__ */ jsx("div", { className: "w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center", children: /* @__PURE__ */ jsx(MessageCircle, { className: "w-6 h-6 text-primary" }) }),
          /* @__PURE__ */ jsxs("div", { children: [
            /* @__PURE__ */ jsx("h3", { className: "text-2xl font-bold text-foreground", children: "\u0627\u0644\u062A\u0639\u0644\u064A\u0642\u0627\u062A" }),
            /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm", children: comments.length > 0 ? `${comments.length} \u062A\u0639\u0644\u064A\u0642` : "\u0644\u0627 \u062A\u0648\u062C\u062F \u062A\u0639\u0644\u064A\u0642\u0627\u062A \u0628\u0639\u062F" })
          ] })
        ]
      }
    ),
    /* @__PURE__ */ jsxs(
      motion.form,
      {
        initial: { opacity: 0, y: 20 },
        whileInView: { opacity: 1, y: 0 },
        viewport: { once: true },
        onSubmit: handleSubmit,
        className: "bg-popover rounded-2xl p-6 border border-border mb-8",
        children: [
          /* @__PURE__ */ jsx("h4", { className: "text-lg font-bold text-foreground mb-4", children: "\u0623\u0636\u0641 \u062A\u0639\u0644\u064A\u0642\u0643" }),
          /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-1 md:grid-cols-2 gap-4 mb-4", children: [
            /* @__PURE__ */ jsx(
              "input",
              {
                type: "text",
                placeholder: "\u0627\u0644\u0627\u0633\u0645 *",
                value: formData.author_name,
                onChange: (e) => setFormData((prev) => ({ ...prev, author_name: e.target.value })),
                className: "w-full px-4 py-3 bg-background border border-input rounded-[15px] text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-primary focus:shadow-[0_0_0_3px_rgba(253,128,46,0.18)] transition-all duration-200 ease-ds",
                required: true
              }
            ),
            /* @__PURE__ */ jsx(
              "input",
              {
                type: "email",
                placeholder: "\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A *",
                value: formData.author_email,
                onChange: (e) => setFormData((prev) => ({ ...prev, author_email: e.target.value })),
                className: "w-full px-4 py-3 bg-background border border-input rounded-[15px] text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-primary focus:shadow-[0_0_0_3px_rgba(253,128,46,0.18)] transition-all duration-200 ease-ds",
                required: true
              }
            )
          ] }),
          /* @__PURE__ */ jsx(
            "textarea",
            {
              placeholder: "\u0627\u0643\u062A\u0628 \u062A\u0639\u0644\u064A\u0642\u0643 \u0647\u0646\u0627... *",
              value: formData.content,
              onChange: (e) => setFormData((prev) => ({ ...prev, content: e.target.value })),
              rows: 4,
              className: "w-full px-4 py-3 bg-background border border-input rounded-[15px] text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-primary focus:shadow-[0_0_0_3px_rgba(253,128,46,0.18)] transition-all duration-200 ease-ds resize-none mb-4",
              required: true
            }
          ),
          /* @__PURE__ */ jsx(
            motion.button,
            {
              type: "submit",
              disabled: isSubmitting,
              whileHover: { scale: 1.02 },
              whileTap: { scale: 0.98 },
              className: "inline-flex items-center gap-2 px-6 py-3 text-primary-foreground [background:var(--gradient-coral)] rounded-[15px] font-bold shadow-btn-primary hover:shadow-glow-coral-lg transition-all duration-200 ease-ds disabled:opacity-50 disabled:cursor-not-allowed",
              children: isSubmitting ? /* @__PURE__ */ jsxs(Fragment, { children: [
                /* @__PURE__ */ jsx(Loader2, { className: "w-4 h-4 animate-spin" }),
                "\u062C\u0627\u0631\u064A \u0627\u0644\u0625\u0631\u0633\u0627\u0644..."
              ] }) : /* @__PURE__ */ jsxs(Fragment, { children: [
                /* @__PURE__ */ jsx(Send, { className: "w-4 h-4" }),
                "\u0625\u0631\u0633\u0627\u0644 \u0627\u0644\u062A\u0639\u0644\u064A\u0642"
              ] })
            }
          )
        ]
      }
    ),
    isLoading ? /* @__PURE__ */ jsx("div", { className: "flex items-center justify-center py-12", children: /* @__PURE__ */ jsx(Loader2, { className: "w-8 h-8 text-primary animate-spin" }) }) : /* @__PURE__ */ jsxs("div", { className: "space-y-4", children: [
      /* @__PURE__ */ jsx(AnimatePresence, { children: comments.map((comment, index) => /* @__PURE__ */ jsx(
        motion.div,
        {
          initial: { opacity: 0, y: 20 },
          animate: { opacity: 1, y: 0 },
          exit: { opacity: 0, y: -20 },
          transition: { delay: index * 0.05 },
          className: "bg-popover rounded-2xl p-5 border border-border",
          children: /* @__PURE__ */ jsxs("div", { className: "flex items-start gap-4", children: [
            /* @__PURE__ */ jsx("div", { className: "flex-shrink-0", children: comment.author_avatar_urls?.["48"] ? /* @__PURE__ */ jsx(
              "img",
              {
                src: comment.author_avatar_urls["48"],
                alt: comment.author_name,
                className: "w-12 h-12 rounded-full object-cover"
              }
            ) : /* @__PURE__ */ jsx("div", { className: "w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center", children: /* @__PURE__ */ jsx(User, { className: "w-6 h-6 text-primary" }) }) }),
            /* @__PURE__ */ jsxs("div", { className: "flex-1 min-w-0", children: [
              /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2 mb-2", children: [
                /* @__PURE__ */ jsx("span", { className: "font-bold text-foreground", children: comment.author_name }),
                /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-sm", children: "\u2022" }),
                /* @__PURE__ */ jsx("time", { className: "text-muted-foreground text-sm", children: formatDate(comment.date) })
              ] }),
              /* @__PURE__ */ jsx(
                "div",
                {
                  className: "text-muted-foreground leading-relaxed",
                  dangerouslySetInnerHTML: { __html: comment.content.rendered }
                }
              )
            ] })
          ] })
        },
        comment.id
      )) }),
      comments.length === 0 && !isLoading && /* @__PURE__ */ jsxs(
        motion.div,
        {
          initial: { opacity: 0 },
          animate: { opacity: 1 },
          className: "text-center py-12",
          children: [
            /* @__PURE__ */ jsx("div", { className: "w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4", children: /* @__PURE__ */ jsx(MessageCircle, { className: "w-8 h-8 text-muted-foreground" }) }),
            /* @__PURE__ */ jsx("p", { className: "text-muted-foreground", children: "\u0643\u0646 \u0623\u0648\u0644 \u0645\u0646 \u064A\u0639\u0644\u0642 \u0639\u0644\u0649 \u0647\u0630\u0627 \u0627\u0644\u0645\u0642\u0627\u0644" })
          ]
        }
      )
    ] })
  ] }) });
};
export default CommentsSection;
