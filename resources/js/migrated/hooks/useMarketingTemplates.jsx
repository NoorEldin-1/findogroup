import { useEffect, useState } from "react";
import { FileText } from "lucide-react";
import { supabase } from "@/integrations/supabase/client";
import {
  marketingTemplates as staticTemplates,
  templateIconByName
} from "@/data/templates";
const rowToTemplate = (r) => ({
  id: r.slug,
  category: r.category,
  icon: templateIconByName[r.icon] ?? FileText,
  fileType: ["PDF", "Excel", "Word"].includes(r.file_type) ? r.file_type : "PDF",
  titleAr: r.title_ar,
  titleEn: r.title_en,
  descAr: r.desc_ar,
  descEn: r.desc_en,
  fileUrl: r.file_url,
  imageUrl: r.image_url
});
export function useMarketingTemplates() {
  const [templates, setTemplates] = useState(staticTemplates);
  useEffect(() => {
    let alive = true;
    supabase.from("marketing_templates").select("*").eq("is_published", true).order("sort_order", { ascending: true }).then(({ data, error }) => {
      if (!alive || error || !data || data.length === 0) return;
      setTemplates(data.map((r) => rowToTemplate(r)));
    });
    return () => {
      alive = false;
    };
  }, []);
  return templates;
}
