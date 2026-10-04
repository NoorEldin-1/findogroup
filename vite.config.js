import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import {fileURLToPath,URL} from 'node:url';
export default defineConfig({resolve:{alias:{'react-router-dom':fileURLToPath(new URL('./resources/js/migrated/adapters/router.jsx',import.meta.url)),'@':fileURLToPath(new URL('./resources/js/migrated',import.meta.url))}},plugins:[laravel({input:['resources/css/app.css','resources/js/app.js'],refresh:true}),tailwindcss()],server:{watch:{ignored:['**/storage/framework/views/**']}}});
