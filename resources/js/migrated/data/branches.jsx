import {cmsCollection} from '@/adapters/cms.js';
const original_branches = [
  {
    cityAr: "\u0627\u0644\u0625\u0633\u0643\u0646\u062F\u0631\u064A\u0629",
    cityEn: "Alexandria",
    addressAr: "\u0645\u0628\u0646\u0649 696\u060C \u0634\u0627\u0631\u0639 \u0627\u0644\u0634\u0639\u0631\u0627\u0648\u064A\u060C \u0628\u0627\u0644\u0642\u0631\u0628 \u0645\u0646 \u0634\u0627\u0631\u0639 \u0623\u0628\u0648 \u0642\u064A\u0631 \u0627\u0644\u0631\u0626\u064A\u0633\u064A (\u0641\u0648\u0642 \u0628\u064A \u062A\u0643 - \u0627\u0644\u0637\u0627\u0628\u0642 \u0627\u0644\u062A\u0627\u0633\u0639)\u060C \u0644\u0648\u0631\u0627\u0646",
    addressEn: "Building 696, El-Shaarawi St., Near Abu Qir Main St. (Above B.Tech - 9th Floor), Louran",
    mapQuery: "\u0645\u0628\u0646\u0649 696 \u0634\u0627\u0631\u0639 \u0627\u0644\u0634\u0639\u0631\u0627\u0648\u064A \u0644\u0648\u0631\u0627\u0646 \u0627\u0644\u0625\u0633\u0643\u0646\u062F\u0631\u064A\u0629"
  },
  {
    cityAr: "\u0627\u0644\u0642\u0627\u0647\u0631\u0629",
    cityEn: "Cairo",
    addressAr: "\u0645\u0628\u0646\u0649 63\u060C \u0634\u0627\u0631\u0639 \u0633\u0648\u0631\u064A\u0627\u060C \u0627\u0644\u0645\u0647\u0646\u062F\u0633\u064A\u0646",
    addressEn: "Building 63, Syria St., Mohandessin",
    mapQuery: "\u0645\u0628\u0646\u0649 63 \u0634\u0627\u0631\u0639 \u0633\u0648\u0631\u064A\u0627 \u0627\u0644\u0645\u0647\u0646\u062F\u0633\u064A\u0646 \u0627\u0644\u0642\u0627\u0647\u0631\u0629"
  }
];
export const branches = cmsCollection('branches',original_branches);
export const mapEmbedSrc = (mapQuery) => `https://www.google.com/maps?q=${encodeURIComponent(mapQuery)}&output=embed&hl=ar`;
export const mapDirectionsUrl = (mapQuery) => `https://www.google.com/maps?q=${encodeURIComponent(mapQuery)}`;
