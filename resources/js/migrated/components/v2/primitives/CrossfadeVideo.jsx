import { jsx, jsxs } from "react/jsx-runtime";
import { useEffect, useRef, useState } from "react";
import { cn } from "@/lib/utils";

const CrossfadeVideo = ({ src, poster, posterSrcSet, className, fade = 0.8 }) => {
  const aRef = useRef(null);
  const bRef = useRef(null);
  const posterRef = useRef(null);
  const containerRef = useRef(null);
  const [enabled, setEnabled] = useState(false);
  const [bufferReady, setBufferReady] = useState(false);
  const [active, setActive] = useState(0);
  const handingOff = useRef(false);
  const visible = useRef(true);

  useEffect(() => {
    let cancelled = false;
    let frame;
    const enable = async () => {
      try { await posterRef.current?.decode(); } catch { /* Keep the poster fallback if decoding fails. */ }
      if (cancelled) return;
      frame = requestAnimationFrame(() => {
        frame = requestAnimationFrame(() => { if (!cancelled) setEnabled(true); });
      });
    };
    if (document.readyState === "complete") enable();
    else window.addEventListener("load", enable, { once: true });
    return () => { cancelled = true; cancelAnimationFrame(frame); window.removeEventListener("load", enable); };
  }, [src]);

  useEffect(() => {
    if (!enabled) return;
    const observer = new IntersectionObserver(([entry]) => {
      visible.current = entry.isIntersecting;
      const current = active === 0 ? aRef.current : bRef.current;
      if (entry.isIntersecting && !document.hidden) current?.play().catch(() => {});
      else { aRef.current?.pause(); bRef.current?.pause(); }
    });
    observer.observe(containerRef.current);
    const onVisibility = () => {
      if (document.hidden) { aRef.current?.pause(); bRef.current?.pause(); }
      else if (visible.current) (active === 0 ? aRef.current : bRef.current)?.play().catch(() => {});
    };
    document.addEventListener("visibilitychange", onVisibility);
    return () => { observer.disconnect(); document.removeEventListener("visibilitychange", onVisibility); };
  }, [enabled, active]);

  const handleTimeUpdate = (idx) => (e) => {
    if (idx !== active || handingOff.current) return;
    const el = e.currentTarget;
    if (!Number.isFinite(el.duration)) return;
    if (el.duration - el.currentTime <= Math.max(4, fade * 3)) setBufferReady(true);
    if (el.duration - el.currentTime <= fade) {
      const other = idx === 0 ? bRef.current : aRef.current;
      if (!other || other.readyState < 2) return;
      handingOff.current = true;
      other.currentTime = 0;
      other.play().then(() => setActive(idx === 0 ? 1 : 0)).catch(() => { handingOff.current = false; });
    }
  };
  const handleEnded = (idx) => (e) => {
    e.currentTarget.currentTime = 0;
    if (!handingOff.current && idx === active && visible.current) e.currentTarget.play().catch(() => {});
    handingOff.current = false;
  };
  const videoClass = "absolute inset-0 h-full w-full object-cover transition-opacity ease-linear";
  const fadeStyle = { transitionDuration: `${Math.round(fade * 1e3)}ms` };
  return jsxs("div", { ref: containerRef, "data-crossfade-video": "", "data-video-src": src, className: cn("relative overflow-hidden", className), children: [
    jsx("img", { ref: posterRef, src: poster, srcSet: posterSrcSet, sizes: posterSrcSet ? "100vw" : undefined, width: /hero-ai-/.test(poster || '') ? 1920 : undefined, height: /hero-ai-/.test(poster || '') ? 1097 : undefined, alt: "", fetchPriority: "high", className: "absolute inset-0 h-full w-full object-cover" }),
    jsx("video", { ref: aRef, src: enabled ? src : undefined, muted: true, playsInline: true, preload: "none", onTimeUpdate: handleTimeUpdate(0), onEnded: handleEnded(0), style: fadeStyle, className: cn(videoClass, active === 0 ? "opacity-100" : "opacity-0") }),
    jsx("video", { ref: bRef, src: bufferReady ? src : undefined, muted: true, playsInline: true, preload: bufferReady ? "auto" : "none", onTimeUpdate: handleTimeUpdate(1), onEnded: handleEnded(1), style: fadeStyle, className: cn(videoClass, active === 1 ? "opacity-100" : "opacity-0") })
  ] });
};
export default CrossfadeVideo;
