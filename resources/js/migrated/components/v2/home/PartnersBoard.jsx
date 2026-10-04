import {cmsCollection} from '@/adapters/cms.js';
import { jsx, jsxs } from "react/jsx-runtime";
import { Board, BoardHeader, RiseIn } from "@/components/v2/primitives";
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from "@/components/ui/carousel";
import Autoplay from "embla-carousel-autoplay";
import { useLanguage } from "@/contexts/LanguageContext";
import webSummitLogo from "@/assets/partners/web-summit-qatar.jpg.js";
import alexandriaUniLogo from "@/assets/partners/alexandria-university.png.js";
import aastmtLogo from "@/assets/partners/aastmt.png.js";
import incensePerfumeLogo from "@/assets/partners/incense-perfume.png.js";
import abhaChamberLogo from "@/assets/partners/abha-chamber.png.js";
import easyLogo from "@/assets/partners/easy.png.js";
import makkahTrainingLogo from "@/assets/partners/makkah-training.png.js";
import layhahCafeLogo from "@/assets/partners/layhah-cafe.png.js";
import alwazzabLogo from "@/assets/partners/alwazzab.png.js";
import almenhajLogo from "@/assets/partners/almenhaj.png.js";
import riyadahClubLogo from "@/assets/partners/riyadah-club.png.js";
import acflLogo from "@/assets/partners/acfl.png.js";
import fatoretAlkheirLogo from "@/assets/partners/fatoret-alkheir.png.js";
import tamkeenLogo from "@/assets/partners/tamkeen.png.js";
import businessWomenLogo from "@/assets/partners/business-women.png.js";
import ethaniaLogo from "@/assets/partners/ethania.png.js";
import literaryPartnerLogo from "@/assets/partners/literary-partner.png.js";
import womensCenterLogo from "@/assets/partners/womens-center.png.js";
import fairuzLogo from "@/assets/partners/fairuz.png.js";
import rosesLogo from "@/assets/partners/roses.png.js";
import fourLinesLogo from "@/assets/partners/four-lines.png.js";
import alwazabProductionLogo from "@/assets/partners/alwazab-production.png.js";
import ibnZuhaifaLogo from "@/assets/partners/ibn-zuhaifa.png.js";
import tanmiaAtawlaLogo from "@/assets/partners/tanmia-atawla.png.js";
import nfLogo from "@/assets/partners/nf-logo.png.js";
import makkahTraining2Logo from "@/assets/partners/makkah-training-2.png.js";
import englishFutureLogo from "@/assets/partners/english-future.png.js";
import kingdomLogo from "@/assets/partners/kingdom.png.js";
import incensePerfume2Logo from "@/assets/partners/incense-perfume-2.png.js";
import atharRizqahLogo from "@/assets/partners/athar-rizqah.png.js";
import maryamAlwazabLogo from "@/assets/partners/maryam-alwazab.png.js";
import culinaryAssociationLogo from "@/assets/partners/culinary-association.png.js";
import bakorakLogo from "@/assets/partners/bakorak.png.js";
import aszFitnessLogo from "@/assets/partners/asz-fitness.png.js";
import ovaGreenruyaLogo from "@/assets/partners/ova-greenruya.png.js";
const originalPartners = [
  { name: "Web Summit Qatar", logo: webSummitLogo },
  { name: "\u062C\u0627\u0645\u0639\u0629 \u0627\u0644\u0625\u0633\u0643\u0646\u062F\u0631\u064A\u0629", logo: alexandriaUniLogo },
  { name: "\u0627\u0644\u0623\u0643\u0627\u062F\u064A\u0645\u064A\u0629 \u0627\u0644\u0639\u0631\u0628\u064A\u0629 \u0644\u0644\u0639\u0644\u0648\u0645 \u0648\u0627\u0644\u062A\u0643\u0646\u0648\u0644\u0648\u062C\u064A\u0627", logo: aastmtLogo },
  { name: "Incense Perfume", logo: incensePerfumeLogo },
  { name: "\u063A\u0631\u0641\u0629 \u0623\u0628\u0647\u0627", logo: abhaChamberLogo },
  { name: "Easy", logo: easyLogo },
  { name: "Makkah Training", logo: makkahTrainingLogo },
  { name: "\u0644\u062A\u064A\u0647\u0647 \u0643\u0627\u0641\u064A\u0629", logo: layhahCafeLogo },
  { name: "Alwazzab", logo: alwazzabLogo },
  { name: "\u0627\u0644\u0645\u0646\u0647\u0627\u062C", logo: almenhajLogo },
  { name: "\u0646\u0627\u062F\u064A \u0631\u064A\u0627\u062F\u0629 \u0627\u0644\u0623\u0639\u0645\u0627\u0644", logo: riyadahClubLogo },
  { name: "\u0627\u0644\u0645\u0631\u0643\u0632 \u0627\u0644\u0639\u0631\u0628\u064A \u0644\u0644\u062A\u0639\u0644\u0645 \u0627\u0644\u0633\u0631\u064A\u0639", logo: acflLogo },
  { name: "\u0641\u0627\u062A\u0648\u0631\u0629 \u0627\u0644\u062E\u064A\u0631", logo: fatoretAlkheirLogo },
  { name: "Tamkeen Ten", logo: tamkeenLogo },
  { name: "Business Women", logo: businessWomenLogo },
  { name: "\u0627\u0644\u062B\u0627\u0646\u0648\u064A\u0629 \u0627\u0644\u062B\u0627\u0646\u064A\u0629 \u0628\u064A\u0646\u0628\u0639", logo: ethaniaLogo },
  { name: "\u0627\u0644\u0634\u0631\u064A\u0643 \u0627\u0644\u0623\u062F\u0628\u064A", logo: literaryPartnerLogo },
  { name: "\u0627\u0644\u0645\u0631\u0643\u0632 \u0627\u0644\u062B\u0642\u0627\u0641\u064A \u0627\u0644\u0646\u0633\u0627\u0626\u064A", logo: womensCenterLogo },
  { name: "\u0627\u0644\u0634\u064A\u0641 \u0641\u064A\u0631\u0648\u0632", logo: fairuzLogo },
  { name: "Roses", logo: rosesLogo },
  { name: "Four Lines", logo: fourLinesLogo },
  { name: "\u0627\u0644\u0648\u0632\u0627\u0628 \u0644\u0644\u0625\u0646\u062A\u0627\u062C \u0627\u0644\u0641\u0646\u064A", logo: alwazabProductionLogo },
  { name: "\u0623\u0643\u0627\u062F\u064A\u0645\u064A\u0629 \u0627\u0628\u0646 \u0632\u062D\u064A\u0641\u0629 \u0644\u0644\u0641\u0631\u0648\u0633\u064A\u0629", logo: ibnZuhaifaLogo },
  { name: "\u0644\u062C\u0646\u0629 \u0627\u0644\u062A\u0646\u0645\u064A\u0629 \u0627\u0644\u0625\u062C\u062A\u0645\u0627\u0639\u064A\u0629 \u0628\u0627\u0644\u0623\u0637\u0627\u0648\u0644\u0629", logo: tanmiaAtawlaLogo },
  { name: "NF", logo: nfLogo },
  { name: "\u0625\u062F\u0627\u0631\u0629 \u0627\u0644\u062A\u062F\u0631\u064A\u0628 \u0648\u0627\u0644\u0627\u0628\u062A\u0639\u0627\u062B \u0645\u0643\u0629", logo: makkahTraining2Logo },
  { name: "English Future", logo: englishFutureLogo },
  { name: "\u0627\u0644\u0645\u0645\u0644\u0643\u0629 Kingdom", logo: kingdomLogo },
  { name: "\u0639\u0637\u0631 \u0627\u0644\u0628\u062E\u0648\u0631", logo: incensePerfume2Logo },
  { name: "\u0623\u062B\u0631 \u0631\u0632\u0642\u0647 \u0627\u0644\u062A\u0637\u0648\u0639\u064A", logo: atharRizqahLogo },
  { name: "\u0645\u0631\u064A\u0645 \u0627\u0644\u0648\u0632\u0627\u0628 \u0644\u0644\u0645\u062D\u0627\u0645\u0627\u0629", logo: maryamAlwazabLogo },
  { name: "\u062C\u0645\u0639\u064A\u0629 \u0627\u0644\u0637\u0647\u064A", logo: culinaryAssociationLogo },
  { name: "\u0628\u062E\u0648\u0631\u0643 Bakorak", logo: bakorakLogo },
  { name: "ASZ Fitness", logo: aszFitnessLogo },
  { name: "OVA Greenruya", logo: ovaGreenruyaLogo }
];
const partners=cmsCollection("partners",originalPartners);
const PartnersBoard = () => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { band: true, dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index: "05",
        eyebrow: t("\u0634\u0631\u0643\u0627\u0621 \u0627\u0644\u0646\u062C\u0627\u062D", "Our Partners"),
        pre: t("\u0634\u0631\u0643\u0627\u0621", "Partners of"),
        accent: t("\u0627\u0644\u0646\u062C\u0627\u062D", "Success"),
        subtitle: t(
          "\u0641\u062E\u0648\u0631\u0648\u0646 \u0628\u062B\u0642\u0629 \u0634\u0631\u0643\u0627\u0626\u0646\u0627 \u062D\u0648\u0644 \u0627\u0644\u0639\u0627\u0644\u0645 \u0641\u064A \u0631\u062D\u0644\u0629 \u0627\u0644\u0627\u0628\u062A\u0643\u0627\u0631 \u0648\u0627\u0644\u062A\u0645\u064A\u0632",
          "Proud of the trust of our partners around the world in our journey of innovation and excellence"
        )
      }
    ),
    /* @__PURE__ */ jsx("div", { className: "rounded-3xl border border-border bg-popover px-4 py-8 shadow-card sm:px-8 md:px-12 md:py-10", children: /* @__PURE__ */ jsxs(
      Carousel,
      {
        opts: { align: "start", loop: true, direction: isRTL ? "rtl" : "ltr" },
        plugins: [Autoplay({ delay: 2500, stopOnInteraction: false })],
        className: "w-full",
        children: [
          /* @__PURE__ */ jsx(CarouselContent, { className: isRTL ? "-mr-4" : "-ml-4", children: partners.map((partner) => /* @__PURE__ */ jsx(
            CarouselItem,
            {
              className: `${isRTL ? "pr-4" : "pl-4"} basis-1/2 sm:basis-1/3 md:basis-1/4 lg:basis-1/5`,
              children: /* @__PURE__ */ jsx(
                "div",
                {
                  title: partner.name,
                  className: "group grid h-32 place-items-center overflow-hidden rounded-2xl border border-border bg-white/95 p-3 transition-all duration-200 ease-ds hover:-translate-y-0.5 hover:border-primary/50 md:h-44",
                  children: /* @__PURE__ */ jsx(
                    "img",
                    {
                      src: partner.logo,
                      alt: partner.name,
                      loading: "lazy",
                      decoding: "async",
                      className: "max-h-24 max-w-full object-contain transition-transform duration-500 group-hover:scale-125 md:max-h-36"
                    }
                  )
                }
              )
            },
            partner.name
          )) }),
          /* @__PURE__ */ jsx(CarouselPrevious, { className: "hidden md:flex -left-3 lg:-left-5 border-border bg-popover text-foreground hover:border-primary/50 hover:bg-primary/15 hover:text-primary" }),
          /* @__PURE__ */ jsx(CarouselNext, { className: "hidden md:flex -right-3 lg:-right-5 border-border bg-popover text-foreground hover:border-primary/50 hover:bg-primary/15 hover:text-primary" })
        ]
      }
    ) })
  ] }) });
};
export default PartnersBoard;
